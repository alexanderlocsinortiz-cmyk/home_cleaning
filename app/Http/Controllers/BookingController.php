<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculatePriceRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Mail\BookingSubmitted;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function index()
    {
        $user = $this->requireVerifiedClient();

        $bookings = Booking::where('user_id', $user->id)
            ->with(['staff', 'service', 'preferredStaff'])
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
        $services = Service::where('is_active', true)->get();
        $pricingConfig = Booking::pricingConfiguration();
        $servicePackages = Service::packageCatalog();
        $paymentMethods = Booking::paymentMethods();
        $servicePlans = Booking::servicePlans();
        $subscriptionFrequencies = Booking::subscriptionFrequencyLabels();
        $preferredCleaners = User::where('role', 'staff')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('bookings.create', compact(
            'barangays',
            'services',
            'pricingConfig',
            'preferredCleaners',
            'servicePackages',
            'paymentMethods',
            'servicePlans',
            'subscriptionFrequencies',
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

        if ($request->filled('preferred_staff_id')) {
            $preferredStaff = User::where('role', 'staff')->find($request->preferred_staff_id);

            if ($preferredStaff) {
                $preferredStaffStatus = Booking::staffHasScheduleConflict(
                    $preferredStaff->id,
                    $request->scheduled_date,
                    $request->scheduled_time
                ) ? 'unavailable' : 'requested';
            }
        }

        $service = Service::where('slug', $request->service_type)->where('is_active', true)->first();

        $pricing = Booking::calculatePrice(
            $request->service_type,
            $request->property_type,
            $request->rooms,
            $request->bathrooms,
            $request->floor_area,
            $request->input('add_ons', [])
        );

        $riskReasons = Booking::detectRiskReasons(
            $user->id,
            $request->street_address,
            $request->barangay,
            $request->scheduled_date,
            $request->scheduled_time
        );

        $manualReviewStatus = empty($riskReasons) ? 'not_required' : 'pending';

        $subscriptionGroupId = $servicePlan === 'subscription' ? (string) Str::uuid() : null;

        try {
            $createdBookings = $this->withScheduleLocks($schedulePlan, function () use (
                $user,
                $request,
                $schedulePlan,
                $pricing,
                $service,
                $riskReasons,
                $manualReviewStatus,
                $preferredStaff,
                $preferredStaffStatus,
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
                    $riskReasons,
                    $manualReviewStatus,
                    $preferredStaff,
                    $preferredStaffStatus,
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
                        $riskReasons,
                        $manualReviewStatus,
                        $preferredStaff,
                        $preferredStaffStatus,
                        $servicePlan,
                        $subscriptionFrequency,
                        $subscriptionOccurrences,
                        $subscriptionGroupId
                    ) {
                        $paymentDetails = $this->resolvePaymentDetails($request->input('payment_method'));

                        $currentPreferredStaffStatus = $preferredStaff
                            ? (
                                Booking::staffHasScheduleConflict(
                                    $preferredStaff->id,
                                    $schedule['scheduled_date'],
                                    $schedule['scheduled_time']
                                )
                                    ? 'unavailable'
                                    : 'requested'
                            )
                            : $preferredStaffStatus;

                        return Booking::create([
                            'user_id' => $user->id,
                            'service_id' => $service?->id,
                            'service_type' => $request->service_type,
                            'property_type' => $request->property_type,
                            'rooms' => $request->rooms,
                            'bathrooms' => $request->bathrooms,
                            'floor_area' => $request->floor_area,
                            'add_ons' => $pricing['add_ons'],
                            'barangay' => $request->barangay,
                            'street_address' => $request->street_address,
                            'scheduled_date' => $schedule['scheduled_date'],
                            'scheduled_time' => $schedule['scheduled_time'],
                            'notes' => $request->notes,
                            'service_plan' => $servicePlan,
                            'subscription_frequency' => $servicePlan === 'subscription' ? $subscriptionFrequency : null,
                            'subscription_occurrences' => $servicePlan === 'subscription' ? $subscriptionOccurrences : null,
                            'subscription_group_id' => $subscriptionGroupId,
                            'subscription_sequence' => $schedule['sequence'],
                            'risk_reasons' => $index === 0 && ! empty($riskReasons) ? $riskReasons : null,
                            'manual_review_status' => $index === 0 ? $manualReviewStatus : 'not_required',
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
                            'status' => 'pending',
                            'preferred_staff_id' => $preferredStaff?->id,
                            'preferred_staff_status' => $currentPreferredStaffStatus,
                        ]);
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

        /** @var \App\Models\Booking $booking */
        $booking = $createdBookings->first();
        $booking->load(['user', 'service', 'preferredStaff']);
        $this->createPreferredCleanerRequestNotification($booking);

        if ($servicePlan === 'subscription') {
            $this->createSubscriptionPlanNotification($booking, $createdBookings->count());
        }

        try {
            Mail::to($booking->user->email)->send(new BookingSubmitted($booking));
        } catch (\Exception $e) {
            // silently ignore mail failures
        }

        $successMessage = $manualReviewStatus === 'pending'
            ? 'Your booking request has been submitted and is pending manual review before confirmation.'
            : 'Your booking request has been received. We will review your schedule and confirm it shortly.';

        if ($servicePlan === 'subscription') {
            $successMessage = $manualReviewStatus === 'pending'
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
            $request->input('add_ons', [])
        );

        return response()->json($pricing);
    }

    public function show($id)
    {
        $booking = Booking::with([
            'staff',
            'user',
            'rating',
            'service',
            'preferredStaff',
            'serviceProofs.uploader',
            'activityLogs.actor',
            'messages.sender',
        ])
            ->findOrFail($id);

        $user = auth()->user();

        if ($user->role === 'client' && $booking->user_id !== $user->id) {
            abort(403);
        }

        if ($user->role === 'staff' && $booking->staff_id !== $user->id) {
            abort(403);
        }

        if (! in_array($user->role, ['client', 'admin', 'staff'], true)) {
            abort(403);
        }

        return view('bookings.show', compact('booking'));
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
            $photoPath = $request->file('photo')->store('ratings', 'public');
        }

        \App\Models\Rating::create([
            'booking_id' => $booking->id,
            'client_id' => $user->id,
            'staff_id' => $booking->staff_id,
            'stars' => $request->stars,
            'comment' => $request->comment,
            'photo' => $photoPath,
        ]);

        return back()->with('success', 'Thanks for sharing your feedback. Your rating has been saved.');
    }

    public function cancel($id)
    {
        $user = $this->requireVerifiedClient();
        $booking = Booking::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        if ($booking->status !== 'pending') {
            return back()->with('error', 'Only pending bookings can be cancelled from your dashboard.');
        }

        if ($booking->staff_id) {
            return back()->with('error', 'This booking can no longer be cancelled because a cleaner has already been assigned.');
        }

        $booking->update(['status' => 'cancelled']);

        $booking->logActivity($user, 'status_updated', 'Client cancelled the booking.', [
            'from_status' => 'pending',
            'to_status' => 'cancelled',
        ]);

        $this->createNotification([
            'user_id' => $user->id,
            'title' => 'Booking cancelled',
            'message' => 'Booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' has been cancelled successfully.',
            'type' => 'info',
            'link' => route('bookings.index'),
        ]);

        return back()->with('success', 'Your booking request has been cancelled.');
    }

    public function reschedule(Request $request, $id)
    {
        $user = $this->requireVerifiedClient();
        $booking = Booking::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
            return back()->with('error', 'Only pending or confirmed bookings can be rescheduled.');
        }

        $request->validate([
            'scheduled_date' => 'required|date|after:today',
            'scheduled_time' => 'required',
        ]);

        if (Booking::clientHasScheduleConflict($user->id, $request->scheduled_date, $request->scheduled_time, $booking->id)) {
            return back()->withErrors([
                'scheduled_time' => 'You already have an active booking on that date and time.',
            ]);
        }

        if ($booking->staff_id && Booking::staffHasScheduleConflict($booking->staff_id, $request->scheduled_date, $request->scheduled_time, $booking->id)) {
            return back()->withErrors([
                'scheduled_time' => 'The assigned cleaner is not available on that date and time.',
            ]);
        }

        if (! Booking::slotHasCapacity($request->scheduled_date, $request->scheduled_time, $booking->id)) {
            return back()->withErrors([
                'scheduled_time' => 'That time slot is already fully booked.',
            ]);
        }

        $oldDate = $booking->scheduled_date;
        $oldTime = $booking->scheduled_time;

        $booking->update([
            'scheduled_date' => $request->scheduled_date,
            'scheduled_time' => $request->scheduled_time,
        ]);

        $booking->logActivity($user, 'rescheduled', 'Client rescheduled the booking.', [
            'from_date' => $oldDate,
            'from_time' => $oldTime,
            'to_date' => $request->scheduled_date,
            'to_time' => $request->scheduled_time,
        ]);

        $this->createNotification([
            'user_id' => $user->id,
            'title' => 'Booking rescheduled',
            'message' => 'Booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' has been rescheduled to '.\Carbon\Carbon::parse($request->scheduled_date)->format('F d, Y').' at '.\Carbon\Carbon::parse($request->scheduled_time)->format('h:i A').'.',
            'type' => 'success',
            'link' => route('bookings.show', $booking->id),
        ]);

        return back()->with('success', 'Your booking has been rescheduled successfully.');
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
        if (! $booking->preferredStaff) {
            return;
        }

        if ($booking->preferred_staff_status === 'requested') {
            $this->createNotification([
                'user_id' => $booking->user_id,
                'title' => 'Preferred cleaner request received',
                'message' => 'We noted your preferred cleaner request for '.$booking->preferredStaff->full_name.' on booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).'. We will try to honor it during confirmation if the schedule remains open.',
                'type' => 'info',
                'link' => route('bookings.show', $booking->id),
            ]);
        }

        if ($booking->preferred_staff_status === 'unavailable') {
            $this->createNotification([
                'user_id' => $booking->user_id,
                'title' => 'Preferred cleaner unavailable',
                'message' => $booking->preferredStaff->full_name.' is not available for booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).' at your chosen schedule. Another available cleaner will be assigned during confirmation.',
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
            ->map(fn (array $schedule) => 'booking-slot:'.Booking::scheduleSlotKey($schedule['scheduled_date'], $schedule['scheduled_time']))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $this->withSequentialLocks($lockKeys, $callback);
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

    private function schedulePlanConflictMessage(int $userId, array $schedulePlan): ?string
    {
        foreach ($schedulePlan as $schedule) {
            $formattedDate = Carbon::parse($schedule['scheduled_date'])->format('F d, Y');
            $formattedTime = Carbon::parse($schedule['scheduled_time'])->format('h:i A');

            if (Booking::clientHasScheduleConflict($userId, $schedule['scheduled_date'], $schedule['scheduled_time'])) {
                return 'You already have an active booking on '.$formattedDate.' at '.$formattedTime.'. Please choose a different schedule plan.';
            }

            if (! Booking::slotHasCapacity($schedule['scheduled_date'], $schedule['scheduled_time'])) {
                return 'The selected schedule plan cannot be created because '.$formattedDate.' at '.$formattedTime.' is already fully booked.';
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
        } elseif ($user->date_of_birth->isAfter(now()->subYears(18)->endOfDay())) {
            $errors['date_of_birth'] = 'You must be at least 18 years old to book a cleaning service.';
        }

        return $errors;
    }
}
