<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculatePriceRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Jobs\SendBookingConfirmedEmail;
use App\Jobs\SendBookingSubmittedEmail;
use App\Models\AttendanceLog;
use App\Models\Booking;
use App\Models\BookingServiceProof;
use App\Models\CleanerApplication;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\Service;
use App\Models\User;
use App\Services\PaymongoCheckoutService;
use App\Services\PaymongoRefundService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index()
    {
        $user = $this->requireVerifiedClient();

        $bookings = Booking::where('user_id', $user->id)
            ->with(['staff', 'staffAssignments:id,booking_id,staff_id', 'service', 'preferredStaff', 'preferredCleanerApplication', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('bookings.index', compact('bookings'));
    }

    public function create()
    {
        $user = $this->requireVerifiedClient();

        if ($profileErrors = $this->bookingProfileErrors($user)) {
            return redirect()->route('client.profile.edit')->withErrors($profileErrors);
        }

        $barangays = array_keys(config('cleanflow.barangays'));
        $services = Service::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();
        $pricingConfig = Booking::pricingConfiguration();
        $servicePackages = Service::packageCatalog();
        $paymentMethods = Booking::paymentMethods();
        $servicePlans = Booking::servicePlans();
        $subscriptionFrequencies = Booking::subscriptionFrequencyLabels();
        $bookingTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $bookingNow = Carbon::now($bookingTimezone);
        $timeSlots = Booking::bookingTimeSlots();
        $profileAddress = [
            'barangay' => $user->barangay,
            'street_address' => $user->street,
        ];
        $preferredProviderApplications = CleanerApplication::with('user')
            ->where('status', CleanerApplication::STATUS_APPROVED)
            ->whereNotNull('user_id')
            ->whereNotNull('activated_at')
            ->orderBy('business_name')
            ->get();
        $preferredCleaners = User::where('role', 'staff')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $presentTodayStaffIds = $this->presentStaffIdsForDate($bookingNow->toDateString());
        $preferredCleanerAvailability = [
            'today' => $bookingNow->toDateString(),
            'now' => $bookingNow->format('H:i'),
            'timezone' => $bookingTimezone,
            'timeSlots' => $timeSlots,
            'staff' => $preferredCleaners->map(fn (User $staff) => [
                'id' => $staff->id,
                'name' => trim($staff->first_name.' '.$staff->last_name),
                'barangay' => $staff->barangay,
                'presentToday' => in_array((int) $staff->id, $presentTodayStaffIds, true),
            ])->values(),
            'providers' => $preferredProviderApplications->map(fn (CleanerApplication $provider) => [
                'id' => $provider->id,
                'name' => $provider->business_name ?: ($provider->user?->full_name ?: $provider->email),
                'coverage' => array_values($provider->coverage_barangays ?: []),
                'serviceArea' => $provider->service_area,
                'availableDays' => array_values($provider->available_days ?: []),
                'maxDailyBookings' => (int) ($provider->max_daily_bookings ?: 0),
                'teamSize' => $provider->isTeam() ? max(1, (int) ($provider->team_size ?: 1)) : 1,
            ])->values(),
            'assignments' => Booking::query()
                ->with(['service', 'staffAssignments:id,booking_id,staff_id'])
                ->whereIn('status', Booking::staffAssignmentConflictStatuses())
                ->whereDate('scheduled_date', '>=', $bookingNow->toDateString())
                ->get(['id', 'staff_id', 'scheduled_date', 'scheduled_time', 'duration_minutes', 'service_id', 'status'])
                ->flatMap(fn (Booking $booking) => collect($booking->assignedStaffIds())
                    ->map(fn (int $staffId) => [
                        'staffId' => $staffId,
                        'date' => Booking::normalizeScheduleDate($booking->scheduled_date),
                        'time' => Carbon::parse($booking->scheduled_time)->format('H:i'),
                        'duration' => (int) ($booking->duration_minutes ?: Service::durationForSlug($booking->service?->slug)),
                        'status' => $booking->status,
                    ]))
                ->values(),
            'providerAssignments' => Booking::query()
                ->with('service:id,slug')
                ->whereNotNull('cleaner_application_id')
                ->whereIn('status', Booking::ACTIVE_SCHEDULE_STATUSES)
                ->where(function ($query): void {
                    $query->whereNull('provider_assignment_status')
                        ->orWhereIn('provider_assignment_status', ['pending', 'accepted']);
                })
                ->whereDate('scheduled_date', '>=', $bookingNow->toDateString())
                ->get(['id', 'cleaner_application_id', 'scheduled_date', 'scheduled_time', 'duration_minutes', 'service_id', 'status'])
                ->map(fn (Booking $booking) => [
                    'providerId' => (int) $booking->cleaner_application_id,
                    'date' => Booking::normalizeScheduleDate($booking->scheduled_date),
                    'time' => Carbon::parse($booking->scheduled_time)->format('H:i'),
                    'duration' => (int) ($booking->duration_minutes ?: Service::durationForSlug($booking->service?->slug)),
                    'status' => $booking->status,
                ])
                ->values(),
            'serviceDurations' => $services->mapWithKeys(fn (Service $service) => [
                $service->slug => (int) ($service->duration_minutes ?: Service::durationForSlug($service->slug)),
            ]),
            'restMinutes' => Booking::STAFF_REST_MINUTES,
        ];

        return view('bookings.create', compact(
            'barangays',
            'services',
            'pricingConfig',
            'preferredCleaners',
            'preferredProviderApplications',
            'servicePackages',
            'paymentMethods',
            'servicePlans',
            'subscriptionFrequencies',
            'timeSlots',
            'bookingNow',
            'profileAddress',
            'preferredCleanerAvailability',
        ));
    }

    public function store(StoreBookingRequest $request)
    {
        $user = $this->requireVerifiedClient();

        if ($profileErrors = $this->bookingProfileErrors($user)) {
            return redirect()->route('client.profile.edit')->withErrors($profileErrors);
        }

        $request->merge([
            'payment_method' => $request->input('payment_method', 'on_site_cash'),
            'service_plan' => $request->input('service_plan', 'one_time'),
        ]);

        $servicePlan = $request->input('service_plan', 'one_time');
        $subscriptionFrequency = $servicePlan === 'subscription' ? $request->input('subscription_frequency') : null;
        $subscriptionOccurrences = $servicePlan === 'subscription'
            ? (int) $request->input('subscription_occurrences')
            : null;
        $occurrenceCount = $servicePlan === 'subscription' ? $subscriptionOccurrences : 1;

        $schedulePlan = $this->buildSchedulePlan(
            $request->scheduled_date,
            $request->scheduled_time,
            $servicePlan,
            $subscriptionFrequency,
            $occurrenceCount
        );

        if ($planConflictMessage = $this->schedulePlanConflictMessage($user->id, $schedulePlan)) {
            return back()->withErrors([
                'scheduled_time' => $planConflictMessage,
            ])->withInput();
        }

        $preferredStaff = null;
        $preferredStaffStatus = 'none';
        $preferredCleanerApplication = null;
        $preferredCleanerStatus = 'none';
        $service = Service::where('slug', $request->service_type)->where('is_active', true)->first();

        if (! $service) {
            return back()->withErrors([
                'service_type' => 'The selected service is no longer available. Please choose another service.',
            ])->withInput();
        }

        $serviceDurationMinutes = Service::durationForArea(
            $request->service_type,
            (int) $request->floor_area,
            (int) ($service?->duration_minutes ?: Service::durationForSlug($request->service_type)),
        );

        if ($request->filled('preferred_staff_id')) {
            $preferredStaff = User::where('role', 'staff')->find($request->preferred_staff_id);

            if ($preferredStaff) {
                $preferredStaffStatus = $this->preferredStaffIsAvailable(
                    $preferredStaff,
                    $request->scheduled_date,
                    $request->scheduled_time,
                    $serviceDurationMinutes
                ) ? 'requested' : 'unavailable';
            }
        }

        if ($request->filled('preferred_cleaner_application_id')) {
            $preferredCleanerApplication = CleanerApplication::with('user')
                ->where('status', CleanerApplication::STATUS_APPROVED)
                ->whereNotNull('user_id')
                ->whereNotNull('activated_at')
                ->find($request->preferred_cleaner_application_id);
        }

        $pricing = Booking::calculatePrice(
            $request->service_type,
            $request->property_type,
            $request->rooms,
            $request->bathrooms,
            $request->floor_area,
            $request->input('add_ons', []),
            $request->input('add_on_quantities', [])
        );

        if ($preferredCleanerApplication) {
            $preferredCleanerStatus = $this->preferredCleanerApplicationIsAvailable(
                $preferredCleanerApplication,
                $request->scheduled_date,
                $request->scheduled_time,
                $request->barangay,
                $serviceDurationMinutes,
                max(1, (int) $pricing['required_cleaners']),
            ) ? 'requested' : 'unavailable';
        }

        $subscriptionGroupId = $servicePlan === 'subscription' ? (string) Str::uuid() : null;

        try {
            $createdBookings = $this->withScheduleLocks($schedulePlan, function () use (
                $user,
                $request,
                $schedulePlan,
                $pricing,
                $service,
                $serviceDurationMinutes,
                $preferredStaff,
                $preferredStaffStatus,
                $preferredCleanerApplication,
                $preferredCleanerStatus,
                $servicePlan,
                $subscriptionFrequency,
                $subscriptionOccurrences,
                $subscriptionGroupId
            ) {
                if ($planConflictMessage = $this->schedulePlanConflictMessage($user->id, $schedulePlan)) {
                    throw ValidationException::withMessages([
                        'scheduled_time' => $planConflictMessage,
                    ]);
                }

                return DB::transaction(function () use (
                    $user,
                    $request,
                    $schedulePlan,
                    $pricing,
                    $service,
                    $serviceDurationMinutes,
                    $preferredStaff,
                    $preferredStaffStatus,
                    $preferredCleanerApplication,
                    $preferredCleanerStatus,
                    $servicePlan,
                    $subscriptionFrequency,
                    $subscriptionOccurrences,
                    $subscriptionGroupId
                ) {
                    return collect($schedulePlan)->map(function (array $schedule, int $index) use (
                        $user,
                        $request,
                        $pricing,
                        $service,
                        $serviceDurationMinutes,
                        $preferredStaff,
                        $preferredStaffStatus,
                        $preferredCleanerApplication,
                        $preferredCleanerStatus,
                        $servicePlan,
                        $subscriptionFrequency,
                        $subscriptionOccurrences,
                        $subscriptionGroupId
                    ) {
                        $paymentDetails = $this->resolvePaymentDetails($request->input('payment_method'));

                        $currentPreferredStaffStatus = $preferredStaff
                            ? ($this->preferredStaffIsAvailable(
                                $preferredStaff,
                                $schedule['scheduled_date'],
                                $schedule['scheduled_time'],
                                $serviceDurationMinutes
                            ) ? 'requested' : 'unavailable')
                            : $preferredStaffStatus;

                        $currentPreferredCleanerStatus = $preferredCleanerApplication
                            ? ($this->preferredCleanerApplicationIsAvailable(
                                $preferredCleanerApplication,
                                $schedule['scheduled_date'],
                                $schedule['scheduled_time'],
                                $request->barangay,
                                $serviceDurationMinutes,
                                max(1, (int) $pricing['required_cleaners']),
                            ) ? 'requested' : 'unavailable')
                            : $preferredCleanerStatus;

                        $currentRiskReasons = Booking::detectRiskReasons(
                            $user->id,
                            $request->street_address,
                            $request->barangay,
                            $schedule['scheduled_date'],
                            $schedule['scheduled_time']
                        );

                        if ($service?->requiresScopeManualReview((int) $request->floor_area)) {
                            $currentRiskReasons[] = 'Requested floor area exceeds the provisional measurable limit for this service.';
                        }

                        if (Booking::staffingRequiresManualReview((int) $pricing['required_cleaners'])) {
                            $currentRiskReasons[] = Booking::staffingManualReviewReason((int) $pricing['required_cleaners']);
                        }

                        $availableCleaners = Booking::availableCleanerCountForSchedule(
                            $schedule['scheduled_date'],
                            $schedule['scheduled_time'],
                            $serviceDurationMinutes
                        );

                        if ($availableCleaners < max(1, (int) $pricing['required_cleaners'])) {
                            $currentRiskReasons[] = Booking::capacityManualReviewReason(
                                max(1, (int) $pricing['required_cleaners']),
                                $availableCleaners
                            );
                        }

                        $currentManualReviewStatus = empty($currentRiskReasons) ? 'not_required' : 'pending';
                        $currentStatus = $currentManualReviewStatus === 'not_required' ? 'confirmed' : 'pending';

                        $booking = Booking::create([
                            'user_id' => $user->id,
                            'service_id' => $service?->id,
                            'service_type' => $request->service_type,
                            'property_type' => $request->property_type,
                            'rooms' => $request->rooms,
                            'bathrooms' => $request->bathrooms,
                            'floor_area' => $request->floor_area,
                            'required_cleaners' => $pricing['required_cleaners'],
                            'add_ons' => $pricing['add_ons'],
                            'add_on_quantities' => $pricing['add_on_quantities'],
                            'barangay' => $request->barangay,
                            'street_address' => $request->street_address,
                            'service_latitude' => $request->input('service_latitude'),
                            'service_longitude' => $request->input('service_longitude'),
                            'scheduled_date' => $schedule['scheduled_date'],
                            'scheduled_time' => $schedule['scheduled_time'],
                            'duration_minutes' => $serviceDurationMinutes,
                            'notes' => $request->notes,
                            'service_plan' => $servicePlan,
                            'subscription_frequency' => $servicePlan === 'subscription' ? $subscriptionFrequency : null,
                            'subscription_occurrences' => $servicePlan === 'subscription' ? $subscriptionOccurrences : null,
                            'subscription_group_id' => $subscriptionGroupId,
                            'subscription_sequence' => $schedule['sequence'],
                            'risk_reasons' => empty($currentRiskReasons) ? null : array_values(array_unique($currentRiskReasons)),
                            'manual_review_status' => $currentManualReviewStatus,
                            'price' => $pricing['total'],
                            'base_price' => $pricing['base_price'],
                            'property_fee' => $pricing['property_fee'],
                            'rooms_fee' => $pricing['rooms_fee'],
                            'bathrooms_fee' => $pricing['bathrooms_fee'],
                            'floor_area_fee' => $pricing['floor_area_fee'],
                            'add_ons_fee' => $pricing['add_ons_fee'],
                            'payment_method' => $paymentDetails['payment_method'],
                            'payment_status' => $paymentDetails['payment_status'],
                            'payment_reference' => $paymentDetails['payment_reference'],
                            'paid_at' => $paymentDetails['paid_at'],
                            'status' => $currentStatus,
                            'preferred_staff_id' => $preferredStaff?->id,
                            'preferred_staff_status' => $currentPreferredStaffStatus,
                            'preferred_cleaner_application_id' => $preferredCleanerApplication?->id,
                            'preferred_cleaner_status' => $currentPreferredCleanerStatus,
                        ]);

                        if ($currentStatus === 'confirmed') {
                            $booking->setExpectedServiceWindow();
                            $booking->save();
                        }

                        return $booking;
                    });
                });
            });
        } catch (LockTimeoutException) {
            return back()->withErrors([
                'scheduled_time' => 'Booking load is high right now. Please try submitting again in a few seconds.',
            ])->withInput();
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        /** @var Booking $booking */
        $booking = $createdBookings->first();
        $booking->load(['user', 'service', 'preferredStaff', 'preferredCleanerApplication.user']);
        $this->createPreferredCleanerRequestNotification($booking);

        if ($servicePlan === 'subscription') {
            $this->createSubscriptionPlanNotification($booking, $createdBookings->count());
        }

        $planRequiresManualReview = $createdBookings->contains(
            fn (Booking $createdBooking): bool => $createdBooking->manual_review_status === 'pending'
        );

        if ($booking->status === 'confirmed' && ! $planRequiresManualReview) {
            SendBookingConfirmedEmail::dispatch($booking->id);
            $this->createNotification([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'title' => 'Booking confirmed',
                'message' => 'Booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' is confirmed for '.Carbon::parse($booking->scheduled_date)->format('F d, Y').' at '.Carbon::parse($booking->scheduled_time)->format('h:i A').'. Our admin team will assign your cleaner before the service starts.',
                'type' => 'success',
                'link' => route('bookings.show', $booking->id),
            ]);
        } else {
            SendBookingSubmittedEmail::dispatch($booking->id);
        }

        $successMessage = $planRequiresManualReview
            ? 'Your booking request has been submitted and is pending manual review before confirmation.'
            : 'Your booking is confirmed. Our admin team will assign a cleaner before the service starts.';

        if ($servicePlan === 'subscription') {
            $successMessage = $planRequiresManualReview
                ? 'Your subscription cleaning plan has been created. The first booking is pending manual review before confirmation.'
                : 'Your subscription cleaning plan is active. '.$createdBookings->count().' visits were scheduled on a '.strtolower(Booking::subscriptionFrequencyLabel($subscriptionFrequency)).' plan.';
        }

        $redirect = redirect()->route('bookings.index')->with('success', $successMessage);

        if ($preferredStaffStatus === 'requested' && $preferredStaff) {
            $redirect->with('info', 'We received your preferred cleaner request for '.$preferredStaff->full_name.'. We will prioritize this request during confirmation if the schedule remains available.');
        }

        if ($preferredStaffStatus === 'unavailable' && $preferredStaff) {
            $redirect->with('warning', 'Your preferred cleaner '.$preferredStaff->full_name.' is already booked for that schedule. Another available cleaner will be assigned during confirmation.');
        }

        if ($booking->preferredCleanerApplication && $booking->preferred_cleaner_status === 'requested') {
            $redirect->with('info', 'We received your preferred cleaner request for '.$this->preferredCleanerName($booking->preferredCleanerApplication).'. We will prioritize this request during confirmation if the schedule remains available.');
        }

        if ($booking->preferredCleanerApplication && $booking->preferred_cleaner_status === 'unavailable') {
            $redirect->with('warning', 'Your preferred cleaner '.$this->preferredCleanerName($booking->preferredCleanerApplication).' is no longer available for that schedule. Another available cleaner will be assigned during confirmation.');
        }

        if (Booking::isDigitalPaymentMethod($booking->payment?->method ?? 'on_site_cash') && (bool) config('services.paymongo.checkout_redirect_enabled', true)) {
            try {
                $checkoutSession = app(PaymongoCheckoutService::class)->createCheckoutSession($createdBookings, $user);

                Payment::whereIn('booking_id', $createdBookings->pluck('id'))->update([
                    'checkout_session_id' => $checkoutSession['id'],
                ]);

                return redirect()->away($checkoutSession['checkout_url']);
            } catch (\Throwable $exception) {
                Log::error('PayMongo checkout session could not be created.', [
                    'booking_id' => $booking->id,
                    'error' => $exception->getMessage(),
                ]);

                return $redirect->with('warning', 'Your booking was saved, but the payment checkout could not be opened. Please contact support or wait for admin payment instructions.');
            }
        }

        return $redirect;
    }

    public function calculatePrice(CalculatePriceRequest $request)
    {
        $user = $this->requireVerifiedClient();

        if ($profileErrors = $this->bookingProfileErrors($user)) {
            return response()->json([
                'message' => collect($profileErrors)->first(),
            ], 422);
        }

        $pricing = Booking::calculatePrice(
            $request->service_type,
            $request->property_type,
            $request->rooms,
            $request->bathrooms,
            $request->floor_area,
            $request->input('add_ons', []),
            $request->input('add_on_quantities', [])
        );

        return response()->json($pricing);
    }

    public function paymentReturn($id)
    {
        $user = $this->requireVerifiedClient();

        $booking = Booking::with('payment')->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $payment = $booking->payment;

        if (! Booking::isDigitalPaymentMethod($payment?->method ?? 'on_site_cash')) {
            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('info', 'This booking is not using an online PayMongo payment method.');
        }

        if ($payment?->status === 'refunded') {
            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('success', 'Your online payment has already been refunded.');
        }

        if ($payment?->status === 'paid' && $booking->status !== 'cancelled') {
            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('success', 'Your payment is already confirmed.');
        }

        if ($payment?->status === 'paid' && $booking->status === 'cancelled') {
            try {
                $payment = app(PaymongoRefundService::class)->refund($payment);
            } catch (\Throwable $exception) {
                Log::error('Cancelled booking payment refund could not be completed after customer return.', [
                    'booking_id' => $booking->id,
                    'payment_id' => $payment->id,
                    'error' => $exception->getMessage(),
                ]);

                return redirect()
                    ->route('bookings.show', $booking->id)
                    ->with('warning', 'The booking is cancelled, but the online refund needs admin review. Please do not pay again.');
            }

            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('success', $payment->refund_status === 'succeeded'
                    ? 'The cancelled booking payment has been refunded through PayMongo.'
                    : 'The cancelled booking payment refund has been requested and is being processed.');
        }

        $checkoutSessionId = $payment?->checkout_session_id
            ?: (is_string($payment?->reference) && str_starts_with($payment->reference, 'cs_') ? $payment->reference : null);

        if (! $checkoutSessionId) {
            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('warning', 'We could not verify this PayMongo checkout automatically because the booking has no checkout session ID. Check the PayMongo dashboard and mark this booking as paid from admin if the online payment succeeded.');
        }

        try {
            $paymongo = app(PaymongoCheckoutService::class);
            $checkoutSession = $paymongo->retrieveCheckoutSession($checkoutSessionId);
        } catch (\Throwable $exception) {
            Log::warning('PayMongo checkout session could not be verified after return.', [
                'booking_id' => $booking->id,
                'checkout_session_id' => $checkoutSessionId,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('warning', 'Your booking was saved, but PayMongo payment verification is not available right now. If the online payment was charged, check the PayMongo dashboard before asking the customer to pay again.');
        }

        if (! $paymongo->checkoutSessionIsPaid($checkoutSession)) {
            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('warning', 'PayMongo has not confirmed this checkout as paid yet. If the online payment was already charged, wait a moment and refresh before paying again.');
        }

        $paymentReference = $paymongo->paymentReferenceFromCheckoutSession($checkoutSession);
        $providerPaymentId = $paymongo->paymentIdFromCheckoutSession($checkoutSession);
        $metadataBookingIds = collect(explode(',', (string) data_get($checkoutSession, 'data.attributes.metadata.booking_ids')))
            ->filter(fn (string $id): bool => ctype_digit($id))
            ->map(fn (string $id): int => (int) $id)
            ->values();

        $bookingIds = $metadataBookingIds->isNotEmpty() ? $metadataBookingIds : collect([$booking->id]);
        $paidBookings = Booking::with('payment')->whereIn('id', $bookingIds)
            ->where('user_id', $user->id)
            ->get();

        if (! $paidBookings->contains(fn (Booking $paidBooking): bool => (int) $paidBooking->id === (int) $booking->id)) {
            abort(403);
        }

        $refundFailure = null;
        $paidBookings->each(function (Booking $paidBooking) use ($user, $paymentReference, $providerPaymentId, &$refundFailure): void {
            $payment = $paidBooking->paymentOrCreate([
                'method' => 'gcash',
                'status' => 'pending',
                'amount' => $paidBooking->price ?? 0,
                'currency' => 'PHP',
                'provider' => 'paymongo',
            ]);
            $wasPending = ! in_array($payment->status, ['paid', 'refunded'], true);
            $shouldReplaceReference = ! $payment->reference || str_starts_with((string) $payment->reference, 'cs_');

            $payment->forceFill([
                'status' => $payment->status === 'refunded' ? 'refunded' : 'paid',
                'reference' => $shouldReplaceReference ? $paymentReference : $payment->reference,
                'paid_at' => $payment->paid_at ?: now(),
                'provider_payment_id' => $payment->provider_payment_id ?: $providerPaymentId,
            ])->save();
            $paidBooking->setRelation('payment', $payment);

            if ($wasPending) {
                $paidBooking->logActivity($user, 'payment_updated', 'Payment confirmed through PayMongo return verification.', [
                    'from_payment_status' => 'pending',
                    'to_payment_status' => 'paid',
                    'payment_reference' => $payment->reference,
                ]);
            }

            if ($paidBooking->status === 'cancelled') {
                try {
                    $paidBooking->setRelation('payment', app(PaymongoRefundService::class)->refund($payment));
                } catch (\Throwable $exception) {
                    $refundFailure = $exception->getMessage();
                    Log::error('Cancelled booking payment refund failed after PayMongo return verification.', [
                        'booking_id' => $paidBooking->id,
                        'payment_id' => $payment->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        });

        if ($refundFailure !== null) {
            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('warning', 'Payment was received for a cancelled booking, but the refund needs admin review. Please do not pay again.');
        }

        if ($booking->status === 'cancelled') {
            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('success', 'Payment was received after cancellation and has been sent for refund through PayMongo.');
        }

        return redirect()
            ->route('bookings.show', $booking->id)
            ->with('success', 'Payment confirmed. Your booking is now marked as paid.');
    }

    public function show($id)
    {
        $user = auth()->user();

        if ($user->role === 'client' && ! $user->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice')
                ->with('error', 'Please verify your email before viewing booking details.');
        }

        $booking = Booking::with([
            'staff',
            'staffAssignments.staff',
            'user',
            'rating',
            'service',
            'payment',
            'preferredStaff',
            'preferredCleanerApplication.user',
            'serviceProofs.uploader',
            'activityLogs.actor',
            'messages.sender',
        ])
            ->findOrFail($id);

        if ($user->role === 'client' && $booking->user_id !== $user->id) {
            abort(403);
        }

        if ($user->role === 'staff' && ! $booking->isAssignedToStaff((int) $user->id)) {
            abort(403);
        }

        if (! in_array($user->role, ['client', 'admin', 'staff'], true)) {
            abort(403);
        }

        return view('bookings.show', compact('booking'));
    }

    public function uploadCashPaymentProof(Request $request, $id)
    {
        $user = $this->requireVerifiedClient();
        $booking = Booking::with('payment')->where('user_id', $user->id)->findOrFail($id);
        $payment = $booking->payment;

        if ($payment?->method !== 'on_site_cash') {
            return back()->withErrors(['cash_payment_proof' => 'A cash receipt can only be uploaded for cash bookings.']);
        }

        if ($payment->status === 'paid') {
            return back()->withErrors(['cash_payment_proof' => 'This booking is already marked as paid.']);
        }

        if ($booking->status !== 'completed') {
            return back()->withErrors(['cash_payment_proof' => 'Cash payment proof can be uploaded after the cleaner marks the service as completed.']);
        }

        if ($payment->cash_proof_status === 'pending') {
            return back()->withErrors(['cash_payment_proof' => 'Your cash receipt is already waiting for admin review.']);
        }

        $request->validate([
            'cash_payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $disk = config('filesystems.private_uploads_disk');
        $oldPath = $payment->cash_proof_path;
        $file = $request->file('cash_payment_proof');
        $path = $file->store('cash-payment-proofs', $disk);

        $payment->forceFill([
            'cash_proof_path' => $path,
            'cash_proof_original_name' => $file->getClientOriginalName(),
            'cash_proof_mime_type' => $file->getMimeType(),
            'cash_proof_size' => $file->getSize(),
            'cash_proof_status' => 'pending',
            'cash_proof_submitted_at' => now(),
            'cash_proof_reviewed_at' => null,
            'cash_proof_reviewed_by' => null,
            'cash_proof_rejection_reason' => null,
            'status' => 'pending',
        ])->save();

        if ($oldPath && $oldPath !== $path) {
            Storage::disk($disk)->delete($oldPath);
        }

        $bookingCode = 'CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT);
        foreach (User::where('role', 'admin')->get() as $admin) {
            $this->createNotification([
                'user_id' => $admin->id,
                'booking_id' => $booking->id,
                'title' => 'Cash payment proof submitted',
                'message' => 'The client uploaded cash payment proof for booking '.$bookingCode.'. Review it before confirming payment.',
                'type' => 'warning',
                'link' => route('bookings.show', $booking->id),
            ]);
        }

        $booking->logActivity($user, 'cash_payment_proof_submitted', 'Client uploaded cash payment proof for admin review.', [
            'filename' => $payment->cash_proof_original_name,
            'size' => $payment->cash_proof_size,
        ]);

        return back()->with('success', 'Your cash receipt was uploaded securely and sent to admin for review. Payment remains pending until it is approved.');
    }

    public function downloadCashPaymentProof($id)
    {
        $user = auth()->user();

        if ($user->role === 'client' && ! $user->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice')
                ->with('error', 'Please verify your email before viewing payment documents.');
        }

        $booking = Booking::with('payment')->findOrFail($id);

        if ($user->role === 'client' && (int) $booking->user_id !== (int) $user->id) {
            abort(403);
        }

        if (! in_array($user->role, ['client', 'admin'], true)) {
            abort(403);
        }

        $payment = $booking->payment;
        abort_unless($payment?->method === 'on_site_cash' && $payment?->cash_proof_path, 404);

        $disk = config('filesystems.private_uploads_disk');
        abort_unless(Storage::disk($disk)->exists($payment->cash_proof_path), 404);

        return Storage::disk($disk)->download(
            $payment->cash_proof_path,
            $payment->cash_proof_original_name ?: 'cash-payment-proof'
        );
    }

    public function serviceProof(Booking $booking, BookingServiceProof $proof)
    {
        $user = auth()->user();

        abort_unless((int) $proof->booking_id === (int) $booking->id, 404);
        abort_unless($this->canAccessBookingMedia($booking, $user), 403);

        return $this->privateBookingMediaResponse(
            $proof->file_path,
            $proof->original_name ?: basename($proof->file_path)
        );
    }

    public function ratingPhoto(Booking $booking)
    {
        $user = auth()->user();

        abort_unless($this->canAccessBookingMedia($booking, $user), 403);
        abort_unless($booking->rating?->photo, 404);

        return $this->privateBookingMediaResponse(
            $booking->rating->photo,
            basename($booking->rating->photo)
        );
    }

    public function receipt($id)
    {
        $user = auth()->user();

        if ($user->role === 'client' && ! $user->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice')
                ->with('error', 'Please verify your email before viewing booking receipts.');
        }

        $booking = Booking::with(['staff', 'staffAssignments', 'user', 'service', 'payment.collector'])->findOrFail($id);

        if ($user->role === 'client' && $booking->user_id !== $user->id) {
            abort(403);
        }

        if ($user->role === 'staff' && ! $booking->isAssignedToStaff((int) $user->id)) {
            abort(403);
        }

        if (! in_array($user->role, ['client', 'admin', 'staff'], true)) {
            abort(403);
        }

        $payment = $booking->payment;
        abort_unless($payment?->status === 'paid' && $payment->reference, 404);

        if ($payment->method === 'on_site_cash') {
            abort_unless($payment->receipt_number && $payment->collected_amount && $payment->collected_at, 404);
        }

        return view('bookings.receipt', compact('booking'));
    }

    public function rate(Request $request, $id)
    {
        $user = $this->requireVerifiedClient();
        $booking = Booking::with(['user', 'staff', 'rating'])->findOrFail($id);

        if ($user->id !== $booking->user_id) {
            abort(403);
        }

        if ($booking->status !== 'completed') {
            return back()->withErrors([
                'rating' => 'You can only leave feedback after the booking is completed.',
            ]);
        }

        if (! $booking->staff_id) {
            return back()->withErrors([
                'rating' => 'This booking cannot be rated until a staff member has been assigned.',
            ]);
        }

        if ($booking->rating) {
            return back()->withErrors([
                'rating' => 'You have already submitted feedback for this booking.',
            ]);
        }

        $request->validate([
            'stars' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('ratings', config('filesystems.proof_uploads_disk'));
        }

        Rating::create([
            'booking_id' => $booking->id,
            'client_id' => $user->id,
            'staff_id' => $booking->staff_id,
            'stars' => $request->stars,
            'comment' => $request->comment,
            'photo' => $photoPath,
        ]);

        return back()->with('success', 'Thanks for sharing your feedback. Your rating has been saved.');
    }

    public function openDispute(Request $request, $id)
    {
        $user = $this->requireVerifiedClient();
        $booking = Booking::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        if (! $booking->canClientOpenDispute($user)) {
            return back()->withErrors([
                'dispute' => 'This booking cannot be disputed. Only completed unpaid-provider bookings without an existing dispute can be disputed.',
            ]);
        }

        $validated = $request->validate([
            'dispute_reason' => ['required', 'string', 'in:'.implode(',', array_keys(Booking::disputeReasons()))],
            'dispute_description' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $booking->forceFill([
            'dispute_status' => 'open',
            'dispute_reason' => $validated['dispute_reason'],
            'dispute_description' => $validated['dispute_description'],
            'disputed_at' => now(),
            'dispute_resolution' => null,
            'dispute_admin_notes' => null,
            'dispute_reviewed_by' => null,
            'dispute_resolved_at' => null,
            'provider_payout_status' => $booking->provider_gross_amount !== null ? 'held' : $booking->provider_payout_status,
        ])->save();

        $booking->logActivity($user, 'dispute_opened', 'Client opened a booking dispute.', [
            'dispute_reason' => $validated['dispute_reason'],
            'provider_payout_status' => $booking->provider_payout_status,
        ]);

        return back()->with('success', 'Your dispute has been submitted. Provider payout is held while admin reviews it.');
    }

    public function cancel($id)
    {
        $user = $this->requireVerifiedClient();
        $requestedBooking = Booking::findOrFail($id);

        if ((int) $requestedBooking->user_id !== (int) $user->id) {
            abort(403);
        }

        try {
            return $this->withScheduleLocks([
                ['scheduled_date' => $requestedBooking->scheduled_date, 'scheduled_time' => $requestedBooking->scheduled_time],
            ], function () use ($id, $user): mixed {
                $booking = Booking::with('payment')->where('id', $id)->where('user_id', $user->id)->firstOrFail();

                if ($booking->hasAcceptedProviderAssignment() || $booking->assignedStaffIds() !== []) {
                    return back()->with('error', 'This booking can no longer be cancelled because a cleaner has already been assigned.');
                }

                if (! $booking->clientCanCancel()) {
                    return back()->with('error', 'Only pending bookings or confirmed online bookings without an assigned cleaner can be cancelled from your dashboard.');
                }

                $fromStatus = $booking->status;

                [$canCancel, $refundMessage] = $this->prepareCancellationRefund($booking);

                if (! $canCancel) {
                    return back()->withErrors(['cancel' => $refundMessage]);
                }

                $booking->update(['status' => 'cancelled']);

                $booking->logActivity($user, 'status_updated', 'Client cancelled the booking.', [
                    'from_status' => $fromStatus,
                    'to_status' => 'cancelled',
                    'refund_status' => $booking->payment?->refund_status,
                ]);

                $this->createNotification([
                    'user_id' => $user->id,
                    'title' => 'Booking cancelled',
                    'message' => 'Booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' has been cancelled successfully.'.($refundMessage ? ' '.$refundMessage : ''),
                    'type' => 'info',
                    'link' => route('bookings.index'),
                ]);

                return back()->with('success', 'Your '.($fromStatus === 'pending' ? 'booking request' : 'booking').' has been cancelled.'.($refundMessage ? ' '.$refundMessage : ''));
            });
        } catch (LockTimeoutException) {
            return back()->withErrors(['cancel' => 'This booking is being updated right now. Please try again in a few seconds.']);
        }
    }

    private function prepareCancellationRefund(Booking $booking): array
    {
        $payment = $booking->payment;

        if ($payment?->status !== 'paid') {
            return [true, null];
        }

        if (! Booking::isDigitalPaymentMethod($payment->method)) {
            return [false, 'This booking has a paid cash payment. Please contact support so an administrator can handle the cash refund.'];
        }

        try {
            $booking->setRelation('payment', app(PaymongoRefundService::class)->refund($payment));
        } catch (\Throwable $exception) {
            Log::error('Online payment refund blocked booking cancellation.', [
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'error' => $exception->getMessage(),
            ]);

            return [false, 'Your online payment could not be refunded right now, so the booking was not cancelled. Please try again or contact support.'];
        }

        return [true, $booking->payment?->refund_status === 'succeeded'
            ? 'Your online payment has been refunded.'
            : 'Your online payment refund has been requested and is being processed.'];
    }

    public function reschedule(Request $request, $id)
    {
        $user = $this->requireVerifiedClient();
        $booking = Booking::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
            return back()->with('error', 'Only pending or confirmed bookings can be rescheduled.');
        }

        $bookingTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $bookingToday = Carbon::now($bookingTimezone)->toDateString();
        $validated = $request->validate([
            'scheduled_date' => ['required', 'date_format:Y-m-d', 'after:'.$bookingToday],
            'scheduled_time' => ['required', Rule::in(Booking::bookingTimeSlots())],
        ], [
            'scheduled_date.date_format' => 'Please select a valid date.',
            'scheduled_time.in' => 'Please select one of the available booking times.',
        ]);

        try {
            return $this->withScheduleLocks([
                ['scheduled_date' => $booking->scheduled_date, 'scheduled_time' => $booking->scheduled_time],
                ['scheduled_date' => $validated['scheduled_date'], 'scheduled_time' => $validated['scheduled_time']],
            ], function () use ($user, $id, $validated, $bookingTimezone): mixed {
                $booking = Booking::with('staffAssignments')
                    ->where('id', $id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();

                if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
                    return back()->with('error', 'Only pending or confirmed bookings can be rescheduled.');
                }

                if (Booking::clientHasScheduleConflict($user->id, $validated['scheduled_date'], $validated['scheduled_time'], $booking->id)) {
                    return back()->withErrors([
                        'scheduled_time' => 'You already have an active booking on that date and time.',
                    ]);
                }

                foreach ($booking->assignedStaffIds() as $staffId) {
                    if (Booking::staffHasScheduleConflict($staffId, $validated['scheduled_date'], $validated['scheduled_time'], $booking->id, (int) $booking->duration_minutes)) {
                        return back()->withErrors([
                            'scheduled_time' => 'One of the assigned cleaners is not available on that date and time.',
                        ]);
                    }
                }

                $hasCapacity = $booking->hasAcceptedProviderAssignment()
                    ? ($booking->cleanerApplication?->hasDailyCapacityFor($validated['scheduled_date'], $booking->id) ?? false)
                    : Booking::slotHasCapacity(
                        $validated['scheduled_date'],
                        $validated['scheduled_time'],
                        $booking->id,
                        max(1, (int) ($booking->required_cleaners ?: 1)),
                        (int) ($booking->duration_minutes ?: Service::DEFAULT_DURATION_MINUTES)
                    );

                if (! $hasCapacity) {
                    return back()->withErrors([
                        'scheduled_time' => 'That time slot is already fully booked.',
                    ]);
                }

                $oldDate = $booking->scheduled_date;
                $oldTime = $booking->scheduled_time;

                $booking->update([
                    'scheduled_date' => $validated['scheduled_date'],
                    'scheduled_time' => $validated['scheduled_time'],
                ]);

                $booking->logActivity($user, 'rescheduled', 'Client rescheduled the booking.', [
                    'from_date' => $oldDate,
                    'from_time' => $oldTime,
                    'to_date' => $validated['scheduled_date'],
                    'to_time' => $validated['scheduled_time'],
                ]);

                $this->createNotification([
                    'user_id' => $user->id,
                    'title' => 'Booking rescheduled',
                    'message' => 'Booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' has been rescheduled to '.Carbon::parse($validated['scheduled_date'], $bookingTimezone)->format('F d, Y').' at '.Carbon::parse($validated['scheduled_time'], $bookingTimezone)->format('h:i A').'.',
                    'type' => 'success',
                    'link' => route('bookings.show', $booking->id),
                ]);

                return back()->with('success', 'Your booking has been rescheduled successfully.');
            });
        } catch (LockTimeoutException) {
            return back()->withErrors([
                'scheduled_time' => 'Booking schedules are being updated right now. Please try again in a few seconds.',
            ])->withInput();
        }
    }

    private function requireVerifiedClient(): User
    {
        $user = auth()->user();

        abort_if(
            ! $user || $user->role !== 'client' || ! $user->hasVerifiedEmail(),
            403
        );

        return $user;
    }

    private function createPreferredCleanerRequestNotification(Booking $booking): void
    {
        if (! $booking->preferredStaff && ! $booking->preferredCleanerApplication) {
            return;
        }

        $preferredName = $booking->preferredCleanerApplication
            ? $this->preferredCleanerName($booking->preferredCleanerApplication)
            : $booking->preferredStaff->full_name;
        $preferredStatus = $booking->preferredCleanerApplication
            ? $booking->preferred_cleaner_status
            : $booking->preferred_staff_status;

        if ($preferredStatus === 'requested') {
            $this->createNotification([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'title' => 'Preferred cleaner request received',
                'message' => 'We noted your preferred cleaner request for '.$preferredName.' on booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).'. We will try to honor it during confirmation if the schedule remains open.',
                'type' => 'info',
                'link' => route('bookings.show', $booking->id),
            ]);
        }

        if ($preferredStatus === 'unavailable') {
            $this->createNotification([
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'title' => 'Preferred cleaner unavailable',
                'message' => $preferredName.' is not available for booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' at your chosen schedule. Another available cleaner will be assigned during confirmation.',
                'type' => 'warning',
                'link' => route('bookings.show', $booking->id),
            ]);
        }
    }

    private function createSubscriptionPlanNotification(Booking $booking, int $bookingCount): void
    {
        if (! $booking->isSubscription()) {
            return;
        }

        $this->createNotification([
            'user_id' => $booking->user_id,
            'title' => 'Subscription plan created',
            'message' => 'Your '.strtolower(Booking::subscriptionFrequencyLabel($booking->subscription_frequency)).' cleaning plan for booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' scheduled '.$bookingCount.' visit'.($bookingCount === 1 ? '' : 's').'.',
            'type' => 'success',
            'link' => route('bookings.index'),
        ]);
    }

    private function resolvePaymentDetails(string $paymentMethod): array
    {
        return [
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'payment_reference' => null,
            'paid_at' => null,
        ];
    }

    private function withScheduleLocks(array $schedulePlan, callable $callback): mixed
    {
        $lockKeys = collect($schedulePlan)
            ->flatMap(fn (array $schedule) => [
                'booking-capacity-date:'.Booking::normalizeScheduleDate($schedule['scheduled_date']),
                'booking-slot:'.Booking::scheduleSlotKey($schedule['scheduled_date'], $schedule['scheduled_time']),
            ])
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $this->withSequentialLocks($lockKeys, $callback);
    }

    private function canAccessBookingMedia(Booking $booking, User $user): bool
    {
        return match ($user->role) {
            'admin' => true,
            'client' => $user->hasVerifiedEmail()
                && (int) $booking->user_id === (int) $user->id,
            'staff' => $booking->isAssignedToStaff((int) $user->id),
            'provider' => $user->cleanerApplication
                && $user->cleanerApplication->status === CleanerApplication::STATUS_APPROVED
                && $user->cleanerApplication->activated_at
                && (int) $booking->cleaner_application_id === (int) $user->cleanerApplication->id,
            default => false,
        };
    }

    private function privateBookingMediaResponse(string $path, string $name)
    {
        $storage = Storage::disk(config('filesystems.proof_uploads_disk'));

        // Existing media may still be on the legacy public disk until the
        // migration command is run. New uploads never use this fallback.
        if (! $storage->exists($path)) {
            $legacyStorage = Storage::disk(config('filesystems.public_uploads_disk'));
            abort_unless($legacyStorage->exists($path), 404);
            $storage = $legacyStorage;
        }

        return $storage->response($path, $name, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function withSequentialLocks(array $lockKeys, callable $callback, int $index = 0): mixed
    {
        if ($index >= count($lockKeys)) {
            return $callback();
        }

        return Cache::lock($lockKeys[$index], 10)->block(5, function () use ($lockKeys, $callback, $index) {
            return $this->withSequentialLocks($lockKeys, $callback, $index + 1);
        });
    }

    private function buildSchedulePlan(
        string $scheduledDate,
        string $scheduledTime,
        string $servicePlan,
        ?string $subscriptionFrequency,
        int $occurrenceCount
    ): array {
        $startDate = Carbon::parse($scheduledDate)->startOfDay();

        if ($servicePlan !== 'subscription') {
            return [[
                'sequence' => 1,
                'scheduled_date' => $startDate->toDateString(),
                'scheduled_time' => $scheduledTime,
            ]];
        }

        return collect(range(1, $occurrenceCount))
            ->map(function (int $sequence) use ($startDate, $scheduledTime, $subscriptionFrequency) {
                $date = $startDate->copy();

                if ($sequence > 1) {
                    $date = match ($subscriptionFrequency) {
                        'weekly' => $date->addWeeks($sequence - 1),
                        'biweekly' => $date->addWeeks(($sequence - 1) * 2),
                        'monthly' => $date->addMonthsNoOverflow($sequence - 1),
                        default => $date,
                    };
                }

                return [
                    'sequence' => $sequence,
                    'scheduled_date' => $date->toDateString(),
                    'scheduled_time' => $scheduledTime,
                ];
            })
            ->all();
    }

    private function preferredStaffIsAvailable(
        User $staff,
        mixed $scheduledDate,
        mixed $scheduledTime,
        int $serviceDurationMinutes
    ): bool {
        $bookingTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $scheduleDate = Carbon::parse($scheduledDate, $bookingTimezone)->toDateString();

        if ($scheduleDate === Carbon::now($bookingTimezone)->toDateString()
            && ! in_array((int) $staff->id, $this->presentStaffIdsForDate($scheduleDate), true)) {
            return false;
        }

        return ! Booking::staffHasScheduleConflict(
            $staff->id,
            $scheduledDate,
            $scheduledTime,
            null,
            $serviceDurationMinutes
        );
    }

    private function preferredCleanerApplicationIsAvailable(
        CleanerApplication $provider,
        mixed $scheduledDate,
        mixed $scheduledTime,
        string $barangay,
        int $serviceDurationMinutes,
        int $requiredCleaners = 1,
    ): bool {
        if ($provider->status !== CleanerApplication::STATUS_APPROVED
            || ! $provider->user_id
            || ! $provider->activated_at
            || ! $provider->isAvailableForAssignment()
            || ! $provider->coversBarangay($barangay)
        ) {
            return false;
        }

        $bookingTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $scheduleDate = Carbon::parse($scheduledDate, $bookingTimezone);
        $availableDays = collect($provider->available_days ?: [])
            ->map(fn ($day): string => strtolower((string) $day))
            ->filter()
            ->values();

        if ($availableDays->isNotEmpty() && ! $availableDays->contains(strtolower($scheduleDate->format('l')))) {
            return false;
        }

        $providerCapacity = $provider->isTeam()
            ? max(1, (int) ($provider->team_size ?: 1))
            : 1;

        if ($providerCapacity < max(1, $requiredCleaners)
            || ! $provider->hasDailyCapacityFor($scheduleDate->toDateString())
            || $provider->hasScheduleConflictFor($scheduleDate->toDateString(), $scheduledTime, $serviceDurationMinutes)
        ) {
            return false;
        }

        return true;
    }

    private function preferredCleanerName(CleanerApplication $provider): string
    {
        return $provider->business_name ?: ($provider->user?->full_name ?: $provider->email);
    }

    private function presentStaffIdsForDate(string $localDate): array
    {
        $bookingTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $localDay = Carbon::parse($localDate, $bookingTimezone);

        return AttendanceLog::query()
            ->where('punch_type', 'in')
            ->whereBetween('logged_at', [
                $localDay->copy()->startOfDay()->utc(),
                $localDay->copy()->endOfDay()->utc(),
            ])
            ->pluck('user_id')
            ->map(fn ($staffId) => (int) $staffId)
            ->unique()
            ->values()
            ->all();
    }

    private function schedulePlanConflictMessage(int $userId, array $schedulePlan): ?string
    {
        foreach ($schedulePlan as $schedule) {
            $formattedDate = Carbon::parse($schedule['scheduled_date'])->format('F d, Y');
            $formattedTime = Carbon::parse($schedule['scheduled_time'])->format('h:i A');

            if (Booking::clientHasScheduleConflict($userId, $schedule['scheduled_date'], $schedule['scheduled_time'])) {
                return 'You already have an active booking on '.$formattedDate.' at '.$formattedTime.'. Please choose a different schedule plan.';
            }

        }

        if (count($schedulePlan) > 1) {
            $firstSchedule = collect($schedulePlan)->sortBy('scheduled_date')->first();
            $lastSchedule = collect($schedulePlan)->sortBy('scheduled_date')->last();

            $sameTimeConflict = Booking::query()
                ->where('user_id', $userId)
                ->whereIn('status', Booking::ACTIVE_SCHEDULE_STATUSES)
                ->where('scheduled_time', $firstSchedule['scheduled_time'])
                ->whereBetween('scheduled_date', [$firstSchedule['scheduled_date'], $lastSchedule['scheduled_date']])
                ->exists();

            if ($sameTimeConflict) {
                return 'You already have an active booking during this recurring schedule window. Please choose a different schedule plan.';
            }
        }

        return null;
    }

    private function bookingProfileErrors(User $user): array
    {
        $errors = [];

        if (blank($user->phone)) {
            $errors['phone'] = 'Add your phone number in your profile before creating a booking.';
        }

        if (! $user->date_of_birth) {
            $errors['date_of_birth'] = 'Add your date of birth in your profile before creating a booking.';
        } elseif ($user->date_of_birth->isAfter(now(config('cleanflow.attendance_timezone', config('app.timezone')))->subYears(18)->endOfDay())) {
            $errors['date_of_birth'] = 'You must be at least 18 years old to book a cleaning service.';
        }

        return $errors;
    }
}
