<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\Service;
use App\Services\PaymongoCheckoutService;
use App\Services\PaymongoRefundService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->assertVerifiedClient($request, 'view mobile bookings');
        $user = $request->user();

        $bookings = Booking::with(['service', 'cleanerApplication', 'staffAssignments:id,booking_id,staff_id', 'payment'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Booking $booking) => $this->bookingPayload($booking))
            ->values();

        return response()->json([
            'bookings' => $bookings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertVerifiedClient($request, 'create mobile bookings');
        $user = $request->user();

        $validated = $this->validateBookingRequest($request);
        $service = Service::where('slug', $validated['service_type'])
            ->where('is_active', true)
            ->firstOrFail();
        $serviceDurationMinutes = Service::durationForArea(
            $service->slug,
            (int) $validated['floor_area'],
            (int) ($service->duration_minutes ?: Service::durationForSlug($service->slug)),
        );

        $pricing = Booking::calculatePrice(
            $validated['service_type'],
            $validated['property_type'],
            $validated['rooms'],
            $validated['bathrooms'],
            $validated['floor_area'],
            $validated['add_ons'] ?? [],
            $validated['add_on_quantities'] ?? []
        );
        $servicePlan = $validated['service_plan'];
        $subscriptionFrequency = $servicePlan === 'subscription' ? $validated['subscription_frequency'] : null;
        $subscriptionOccurrences = $servicePlan === 'subscription' ? (int) $validated['subscription_occurrences'] : null;
        $schedulePlan = $this->buildSchedulePlan(
            $validated['scheduled_date'],
            $validated['scheduled_time'],
            $servicePlan,
            $subscriptionFrequency,
            $subscriptionOccurrences ?? 1
        );

        if ($this->schedulePlanHasConflict($user->id, $schedulePlan)) {
            throw ValidationException::withMessages([
                'scheduled_time' => ['You already have an active booking in this recurring schedule plan.'],
            ]);
        }
        $subscriptionGroupId = $servicePlan === 'subscription' ? (string) Str::uuid() : null;

        try {
            $createdBookings = $this->withScheduleLocks($schedulePlan, function () use (
                $user,
                $validated,
                $service,
                $serviceDurationMinutes,
                $pricing,
                $schedulePlan,
                $servicePlan,
                $subscriptionFrequency,
                $subscriptionOccurrences,
                $subscriptionGroupId,
            ) {
                if ($this->schedulePlanHasConflict($user->id, $schedulePlan)) {
                    throw ValidationException::withMessages([
                        'scheduled_time' => ['You already have an active booking in this recurring schedule plan.'],
                    ]);
                }

                return DB::transaction(function () use (
                    $user,
                    $validated,
                    $service,
                    $serviceDurationMinutes,
                    $pricing,
                    $schedulePlan,
                    $servicePlan,
                    $subscriptionFrequency,
                    $subscriptionOccurrences,
                    $subscriptionGroupId,
                ) {
                    return collect($schedulePlan)->map(function (array $schedule) use ($user, $validated, $service, $serviceDurationMinutes, $pricing, $servicePlan, $subscriptionFrequency, $subscriptionOccurrences, $subscriptionGroupId) {
                        $riskReasons = Booking::detectRiskReasons($user->id, $validated['street_address'], $validated['barangay'], $schedule['scheduled_date'], $schedule['scheduled_time']);
                        if ($service->requiresScopeManualReview((int) $validated['floor_area'])) $riskReasons[] = 'Requested floor area exceeds the provisional measurable limit for this service.';
                        if (Booking::staffingRequiresManualReview((int) $pricing['required_cleaners'])) $riskReasons[] = Booking::staffingManualReviewReason((int) $pricing['required_cleaners']);
                        $availableCleaners = Booking::availableCleanerCountForSchedule($schedule['scheduled_date'], $schedule['scheduled_time'], $serviceDurationMinutes);
                        if ($availableCleaners < max(1, (int) $pricing['required_cleaners'])) $riskReasons[] = Booking::capacityManualReviewReason(max(1, (int) $pricing['required_cleaners']), $availableCleaners);
                        $manualReviewStatus = empty($riskReasons) ? 'not_required' : 'pending';
                        $booking = Booking::create([
                            'user_id' => $user->id, 'service_id' => $service->id, 'service_type' => $validated['service_type'], 'property_type' => $validated['property_type'],
                            'rooms' => $validated['rooms'], 'bathrooms' => $validated['bathrooms'], 'floor_area' => $validated['floor_area'], 'required_cleaners' => $pricing['required_cleaners'],
                            'add_ons' => $pricing['add_ons'], 'add_on_quantities' => $pricing['add_on_quantities'], 'barangay' => $validated['barangay'], 'street_address' => $validated['street_address'],
                            'service_latitude' => $validated['service_latitude'] ?? null, 'service_longitude' => $validated['service_longitude'] ?? null,
                            'scheduled_date' => $schedule['scheduled_date'], 'scheduled_time' => $schedule['scheduled_time'], 'duration_minutes' => $serviceDurationMinutes, 'notes' => $validated['notes'] ?? null,
                            'service_plan' => $servicePlan, 'subscription_frequency' => $subscriptionFrequency, 'subscription_occurrences' => $subscriptionOccurrences, 'subscription_group_id' => $subscriptionGroupId, 'subscription_sequence' => $schedule['sequence'],
                            'risk_reasons' => empty($riskReasons) ? null : array_values(array_unique($riskReasons)), 'manual_review_status' => $manualReviewStatus,
                            'price' => $pricing['total'], 'base_price' => $pricing['base_price'], 'property_fee' => $pricing['property_fee'], 'rooms_fee' => $pricing['rooms_fee'], 'bathrooms_fee' => $pricing['bathrooms_fee'], 'floor_area_fee' => $pricing['floor_area_fee'], 'add_ons_fee' => $pricing['add_ons_fee'],
                            'payment_method' => $validated['payment_method'], 'payment_status' => 'pending', 'payment_reference' => null, 'paid_at' => null,
                            'status' => $manualReviewStatus === 'not_required' ? 'confirmed' : 'pending', 'preferred_staff_status' => 'none',
                        ]);
                        if ($booking->status === 'confirmed') { $booking->setExpectedServiceWindow(); $booking->save(); }
                        return $booking;
                    });
                });
            });
        } catch (LockTimeoutException) {
            return response()->json([
                'message' => 'Booking load is high right now. Please try again in a few seconds.',
            ], 423);
        }

        $booking = $createdBookings->first();
        $createdBookings->each->load(['service', 'cleanerApplication', 'payment']);

        $this->notifyClient(
            $booking,
            $servicePlan === 'subscription' ? 'Subscription plan created' : ($booking->status === 'confirmed' ? 'Booking confirmed' : 'Booking submitted'),
            $servicePlan === 'subscription'
                ? 'Your recurring cleaning plan has been created with '.$createdBookings->count().' scheduled visits.'
                : ($booking->status === 'confirmed'
                    ? 'Booking CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT).' is confirmed. A cleaner will be assigned before the service starts.'
                    : 'Booking CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT).' is pending manual review.'),
            $booking->status === 'confirmed' ? 'success' : 'info'
        );

        $checkoutUrl = null;
        $checkoutWarning = null;

        if (Booking::isDigitalPaymentMethod($booking->payment_method) && (bool) config('services.paymongo.checkout_redirect_enabled', true)) {
            try {
                $checkoutSession = app(PaymongoCheckoutService::class)->createCheckoutSession($createdBookings, $user);
                $createdBookings->each(fn (Booking $createdBooking) => $createdBooking->paymentOrCreate([
                    'method' => $createdBooking->payment_method, 'status' => 'pending', 'amount' => $createdBooking->price,
                ])->forceFill(['checkout_session_id' => $checkoutSession['id']])->save());
                $checkoutUrl = $checkoutSession['checkout_url'];
            } catch (\Throwable $exception) {
                Log::error('Mobile PayMongo checkout session could not be created.', [
                    'booking_id' => $booking->id,
                    'error' => $exception->getMessage(),
                ]);
                $checkoutWarning = 'Your booking was saved, but payment checkout could not be opened. Please use the web portal or contact support.';
            }
        }

        return response()->json([
            'message' => $servicePlan === 'subscription'
                ? 'Your subscription plan has been created with '.$createdBookings->count().' scheduled visits.'
                : ($booking->manual_review_status === 'pending'
                ? 'Your booking request has been submitted and is pending manual review.'
                : 'Your booking is confirmed. A cleaner will be assigned before the service starts.'),
            'booking' => $this->bookingPayload($booking),
            'pricing' => $pricing,
            'formatted_total' => 'P'.number_format((float) $pricing['total'], 2),
            'checkout_url' => $checkoutUrl,
            'checkout_warning' => $checkoutWarning,
            'subscription_booking_count' => $createdBookings->count(),
        ], 201);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $this->assertClientOwns($request, $booking);

        try {
            return $this->withScheduleLocks([
                ['scheduled_date' => $booking->scheduled_date, 'scheduled_time' => $booking->scheduled_time],
            ], function () use ($request, $booking): JsonResponse {
                $booking = Booking::with(['payment', 'staffAssignments:id,booking_id,staff_id'])->findOrFail($booking->id);
                $this->assertClientOwns($request, $booking);

                if ($booking->hasAcceptedProviderAssignment() || $booking->assignedStaffIds() !== []) {
                    return response()->json(['message' => 'This booking can no longer be cancelled because a cleaner has already been assigned.'], 422);
                }

                if (! $booking->clientCanCancel()) {
                    return response()->json(['message' => 'Only pending bookings or confirmed online bookings without an assigned cleaner can be cancelled.'], 422);
                }

                $fromStatus = $booking->status;

                [$canCancel, $refundMessage] = $this->prepareCancellationRefund($booking);

                if (! $canCancel) {
                    return response()->json(['message' => $refundMessage], 422);
                }

                $booking->update(['status' => 'cancelled']);
                $booking->logActivity($request->user(), 'status_updated', 'Client cancelled the booking.', [
                    'from_status' => $fromStatus, 'to_status' => 'cancelled',
                    'refund_status' => $booking->payment?->refund_status,
                ]);
                $this->notifyClient($booking, 'Booking cancelled', 'Your booking '.$this->bookingCode($booking).' has been cancelled.'.($refundMessage ? ' '.$refundMessage : ''), 'info');

                return response()->json([
                    'message' => 'Your '.($fromStatus === 'pending' ? 'booking request' : 'booking').' has been cancelled.'.($refundMessage ? ' '.$refundMessage : ''),
                    'booking' => $this->bookingPayload($booking->fresh(['service', 'payment'])),
                ]);
            });
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'This booking is being updated right now. Please try again in a few seconds.'], 423);
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
            Log::error('Mobile online payment refund blocked booking cancellation.', [
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

    public function reschedule(Request $request, Booking $booking): JsonResponse
    {
        $this->assertClientOwns($request, $booking);
        $bookingToday = Carbon::now(config('cleanflow.attendance_timezone', 'Asia/Manila'))->toDateString();
        $validated = $request->validate([
            'scheduled_date' => ['required', 'date_format:Y-m-d', 'after:'.$bookingToday],
            'scheduled_time' => ['required', Rule::in(Booking::bookingTimeSlots())],
        ]);

        try {
            return $this->withScheduleLocks([
                ['scheduled_date' => $booking->scheduled_date, 'scheduled_time' => $booking->scheduled_time],
                ['scheduled_date' => $validated['scheduled_date'], 'scheduled_time' => $validated['scheduled_time']],
            ], function () use ($request, $booking, $validated): JsonResponse {
                $booking = Booking::with(['cleanerApplication', 'staffAssignments'])->findOrFail($booking->id);
                $this->assertClientOwns($request, $booking);

                if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
                    return response()->json(['message' => 'Only pending or confirmed bookings can be rescheduled.'], 422);
                }
                if (Booking::clientHasScheduleConflict($request->user()->id, $validated['scheduled_date'], $validated['scheduled_time'], $booking->id)) {
                    return response()->json(['message' => 'You already have an active booking at that date and time.'], 422);
                }
                foreach ($booking->assignedStaffIds() as $staffId) {
                    if (Booking::staffHasScheduleConflict($staffId, $validated['scheduled_date'], $validated['scheduled_time'], $booking->id, (int) $booking->duration_minutes)) {
                        return response()->json(['message' => 'One of the assigned cleaners is not available at that date and time.'], 422);
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
                    return response()->json(['message' => 'That time slot is already fully booked.'], 422);
                }

                $booking->update($validated);
                $booking->logActivity($request->user(), 'rescheduled', 'Client rescheduled the booking.', $validated);
                $this->notifyClient($booking, 'Booking rescheduled', 'Your booking '.$this->bookingCode($booking).' has been rescheduled.', 'success');

                return response()->json(['message' => 'Your booking has been rescheduled successfully.', 'booking' => $this->bookingPayload($booking->fresh(['service', 'payment']))]);
            });
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'Booking schedules are being updated right now. Please try again in a few seconds.'], 423);
        }
    }

    public function rate(Request $request, Booking $booking): JsonResponse
    {
        $this->assertClientOwns($request, $booking);
        $validated = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($booking->status !== 'completed' || ! $booking->staff_id || $booking->rating) {
            return response()->json(['message' => 'This booking is not eligible for a new rating.'], 422);
        }

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('ratings', config('filesystems.proof_uploads_disk'))
            : null;

        Rating::create([
            'booking_id' => $booking->id,
            'client_id' => $request->user()->id,
            'staff_id' => $booking->staff_id,
            'stars' => $validated['stars'],
            'comment' => $validated['comment'] ?? null,
            'photo' => $photoPath,
        ]);
        $this->notifyClient($booking, 'Feedback received', 'Thanks for rating your cleaning service.', 'success');

        return response()->json(['message' => 'Thanks for sharing your feedback.', 'booking' => $this->bookingPayload($booking->fresh(['service', 'payment']))]);
    }

    public function dispute(Request $request, Booking $booking): JsonResponse
    {
        $this->assertClientOwns($request, $booking);
        if (! $booking->canClientOpenDispute($request->user())) {
            return response()->json(['message' => 'This booking cannot be disputed.'], 422);
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
            'provider_payout_status' => $booking->provider_gross_amount !== null ? 'held' : $booking->provider_payout_status,
        ])->save();
        $booking->logActivity($request->user(), 'dispute_opened', 'Client opened a booking dispute.', $validated);
        $this->notifyClient($booking, 'Dispute submitted', 'Your dispute for booking '.$this->bookingCode($booking).' has been submitted for review.', 'info');

        return response()->json(['message' => 'Your dispute has been submitted.', 'booking' => $this->bookingPayload($booking->fresh(['service', 'payment']))]);
    }

    private function assertClientOwns(Request $request, Booking $booking): void
    {
        $this->assertVerifiedClient($request, 'manage mobile bookings');
        abort_if((int) $booking->user_id !== (int) $request->user()->id, 403);
    }

    private function bookingCode(Booking $booking): string
    {
        return 'CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);
    }

    private function notifyClient(Booking $booking, string $title, string $message, string $type): void
    {
        Notification::create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => '/bookings/'.$booking->id,
        ]);
    }

    private function assertVerifiedClient(Request $request, string $action): void
    {
        $user = $request->user();

        if ($user->role !== 'client') {
            abort(response()->json([
                'message' => 'Only client accounts can '.$action.'.',
            ], 403));
        }

        if (! $user->hasVerifiedEmail()) {
            abort(response()->json([
                'message' => 'Please verify your email before you '.$action.'.',
                'requires_email_verification' => true,
            ], 403));
        }
    }

    private function validateBookingRequest(Request $request): array
    {
        $request->merge([
            'service_plan' => $request->input('service_plan', 'one_time'),
        ]);
        $bookingToday = Carbon::now(config('cleanflow.attendance_timezone', 'Asia/Manila'))->toDateString();
        $locationBounds = Booking::serviceLocationBounds();

        $validated = $request->validate([
            'service_type' => [
                'required',
                Rule::exists('services', 'slug')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'property_type' => ['required', Rule::in(array_keys(Booking::propertyTypeLabels()))],
            'rooms' => ['nullable', 'integer', 'min:1', 'max:20'],
            'bathrooms' => ['nullable', 'integer', 'min:1', 'max:10'],
            'floor_area' => ['required', 'integer', 'min:10', 'max:1000'],
            'add_ons' => ['nullable', 'array'],
            'add_ons.*' => ['string', Rule::in(array_keys(Booking::addOnCatalog()))],
            'add_on_quantities' => ['nullable', 'array'],
            'add_on_quantities.*' => ['nullable', 'integer', 'min:1', 'max:50'],
            'payment_method' => ['required', Rule::in(array_keys(Booking::paymentMethods()))],
            'service_plan' => ['required', Rule::in(array_keys(Booking::servicePlans()))],
            'subscription_frequency' => ['nullable', Rule::requiredIf(fn () => $request->input('service_plan') === 'subscription'), Rule::in(array_keys(Booking::subscriptionFrequencyLabels()))],
            'subscription_occurrences' => ['nullable', Rule::requiredIf(fn () => $request->input('service_plan') === 'subscription'), 'integer', 'min:2', 'max:12'],
            'barangay' => ['required', Rule::in(array_keys(config('cleanflow.barangays', [])))],
            'street_address' => ['required', 'string', 'max:255'],
            'service_latitude' => ['nullable', 'numeric', 'between:'.$locationBounds['min_latitude'].','.$locationBounds['max_latitude'], 'required_with:service_longitude'],
            'service_longitude' => ['nullable', 'numeric', 'between:'.$locationBounds['min_longitude'].','.$locationBounds['max_longitude'], 'required_with:service_latitude'],
            'scheduled_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$bookingToday],
            'scheduled_time' => ['required', Rule::in(Booking::bookingTimeSlots())],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'barangay.in' => 'The selected barangay is not within our service area.',
            'scheduled_date.after_or_equal' => 'Please select today or a future date.',
            'scheduled_date.date_format' => 'Please select a valid date.',
            'scheduled_time.in' => 'Please select one of the available booking times.',
            'service_latitude.required_with' => 'Latitude and longitude must be provided together.',
            'service_longitude.required_with' => 'Latitude and longitude must be provided together.',
            'service_latitude.between' => 'The selected location is outside our service area.',
            'service_longitude.between' => 'The selected location is outside our service area.',
        ]);

        $bookingTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $selectedStart = Carbon::parse($validated['scheduled_date'].' '.$validated['scheduled_time'], $bookingTimezone);

        if ($selectedStart->lessThanOrEqualTo(Carbon::now($bookingTimezone))) {
            throw ValidationException::withMessages([
                'scheduled_time' => ['Please select a time later than the current time for today.'],
            ]);
        }

        $validated['add_ons'] = $validated['add_ons'] ?? [];
        $validated['add_on_quantities'] = $validated['add_on_quantities'] ?? [];
        $validated['rooms'] = (int) ($validated['rooms'] ?? 1);
        $validated['bathrooms'] = (int) ($validated['bathrooms'] ?? 1);

        if (! Service::supportsPropertyType($validated['service_type'], $validated['property_type'])) {
            throw ValidationException::withMessages([
                'service_type' => ['Please choose a service that matches the selected property type.'],
            ]);
        }

        $service = Service::where('slug', $validated['service_type'])
            ->where('is_active', true)
            ->first();

        if (
            $service?->scopeIsApproved()
            && $service->scope_max_floor_area
            && (int) $validated['floor_area'] > (int) $service->scope_max_floor_area
        ) {
            throw ValidationException::withMessages([
                'floor_area' => ["This service is approved for up to {$service->scope_max_floor_area} sqm. Please request a manual quote for a larger property."],
            ]);
        }

        return $validated;
    }

    private function buildSchedulePlan(string $date, string $time, string $plan, ?string $frequency, int $occurrences): array
    {
        $start = Carbon::parse($date)->startOfDay();

        return collect(range(1, $plan === 'subscription' ? $occurrences : 1))
            ->map(function (int $sequence) use ($start, $time, $frequency): array {
                $scheduledDate = $start->copy();
                if ($sequence > 1) {
                    $scheduledDate = match ($frequency) {
                        'weekly' => $scheduledDate->addWeeks($sequence - 1),
                        'biweekly' => $scheduledDate->addWeeks(($sequence - 1) * 2),
                        'monthly' => $scheduledDate->addMonthsNoOverflow($sequence - 1),
                        default => $scheduledDate,
                    };
                }

                return ['sequence' => $sequence, 'scheduled_date' => $scheduledDate->toDateString(), 'scheduled_time' => $time];
            })->all();
    }

    private function schedulePlanHasConflict(int $userId, array $schedulePlan): bool
    {
        foreach ($schedulePlan as $schedule) {
            if (Booking::clientHasScheduleConflict($userId, $schedule['scheduled_date'], $schedule['scheduled_time'])) {
                return true;
            }
        }

        return false;
    }

    private function withScheduleLocks(array $schedulePlan, callable $callback, int $index = 0): mixed
    {
        $keys = collect($schedulePlan)
            ->flatMap(fn (array $schedule) => [
                'booking-capacity-date:'.Booking::normalizeScheduleDate($schedule['scheduled_date']),
                'booking-slot:'.Booking::scheduleSlotKey($schedule['scheduled_date'], $schedule['scheduled_time']),
            ])
            ->unique()->sort()->values()->all();

        $lock = function (int $position) use (&$lock, $keys, $callback): mixed {
            if ($position >= count($keys)) return $callback();
            return Cache::lock($keys[$position], 10)->block(5, fn () => $lock($position + 1));
        };

        return $lock($index);
    }

    private function bookingPayload(Booking $booking): array
    {
        $serviceLabel = $booking->service?->name ?? $booking->service_label;
        $date = $booking->scheduled_date?->toDateString();
        $time = $booking->scheduled_time
            ? Carbon::parse($booking->scheduled_time)->format('H:i')
            : null;

        return [
            'id' => $booking->id,
            'code' => 'CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
            'service' => [
                'slug' => $booking->service_type,
                'label' => $serviceLabel,
            ],
            'status' => $booking->status,
            'status_label' => ucfirst(str_replace('_', ' ', (string) $booking->status)),
            'payment_method' => $booking->payment_method,
            'payment_method_label' => Booking::paymentMethodLabel($booking->payment_method),
            'payment_status' => $booking->payment_status,
            'payment_status_label' => Booking::paymentStatusLabel($booking->payment_status),
            'online_payment_expiry_minutes' => Booking::isDigitalPaymentMethod($booking->payment_method)
                && $booking->payment_status === 'pending'
                ? (int) config('cleanflow.payments.unpaid_online_expiry_minutes', 30)
                : null,
            'refund_status' => $booking->payment?->refund_status ?? 'none',
            'refund_status_label' => $booking->payment?->refundStatusLabel() ?? 'No refund requested',
            'price' => (float) $booking->price,
            'formatted_price' => 'P'.number_format((float) $booking->price, 2),
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'schedule_label' => trim(($date ?? 'Unscheduled').' '.($time ?? '')),
            'barangay' => $booking->barangay,
            'street_address' => $booking->street_address,
            'rooms' => (int) $booking->rooms,
            'bathrooms' => (int) $booking->bathrooms,
            'add_ons' => Booking::addOnBreakdown($booking->add_ons ?? [], $booking->add_on_quantities ?? []),
            'add_ons_fee' => (float) $booking->add_ons_fee,
            'manual_review_status' => $booking->manual_review_status,
            'risk_reasons' => $booking->risk_reasons ?? [],
            'has_assigned_staff' => $booking->assignedStaffIds() !== [],
            'can_cancel' => $booking->clientCanCancel(),
            'dispute_status' => $booking->dispute_status,
            'dispute_reason' => $booking->dispute_reason,
            'provider_name' => $booking->cleanerApplication?->business_name,
            'created_at' => $booking->created_at?->toISOString(),
        ];
    }
}
