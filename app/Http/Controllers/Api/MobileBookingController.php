<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileBookingController extends Controller
{
    private const TIME_SLOTS = [
        '07:00',
        '08:00',
        '09:00',
        '10:00',
        '11:00',
        '12:00',
        '13:00',
        '14:00',
        '15:00',
        '16:00',
    ];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'client') {
            return response()->json([
                'message' => 'Only client accounts can view mobile bookings.',
            ], 403);
        }

        $bookings = Booking::with(['service', 'cleanerApplication'])
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
        $user = $request->user();

        if ($user->role !== 'client') {
            return response()->json([
                'message' => 'Only client accounts can create mobile bookings.',
            ], 403);
        }

        $validated = $this->validateBookingRequest($request);
        $service = Service::where('slug', $validated['service_type'])
            ->where('is_active', true)
            ->firstOrFail();
        $serviceDurationMinutes = (int) ($service->duration_minutes ?: Service::durationForSlug($service->slug));

        $pricing = Booking::calculatePrice(
            $validated['service_type'],
            $validated['property_type'],
            1,
            1,
            $validated['floor_area'],
            $validated['add_ons'] ?? []
        );

        $riskReasons = Booking::detectRiskReasons(
            $user->id,
            $validated['street_address'],
            $validated['barangay'],
            $validated['scheduled_date'],
            $validated['scheduled_time']
        );

        if ($service->requiresScopeManualReview((int) $validated['floor_area'])) {
            $riskReasons[] = 'Requested floor area exceeds the provisional measurable limit for this service.';
        }

        $manualReviewStatus = empty($riskReasons) ? 'not_required' : 'pending';

        try {
            $booking = Cache::lock(
                'booking-slot:'.Booking::scheduleSlotKey($validated['scheduled_date'], $validated['scheduled_time']),
                10
            )->block(5, function () use (
                $user,
                $validated,
                $service,
                $serviceDurationMinutes,
                $pricing,
                $riskReasons,
                $manualReviewStatus
            ) {
                if (Booking::clientHasScheduleConflict($user->id, $validated['scheduled_date'], $validated['scheduled_time'])) {
                    throw ValidationException::withMessages([
                        'scheduled_time' => ['You already have an active booking at this date and time.'],
                    ]);
                }

                if (! Booking::slotHasCapacity($validated['scheduled_date'], $validated['scheduled_time'])) {
                    throw ValidationException::withMessages([
                        'scheduled_time' => ['This schedule is already full. Please choose another time.'],
                    ]);
                }

                return DB::transaction(function () use (
                    $user,
                    $validated,
                    $service,
                    $serviceDurationMinutes,
                    $pricing,
                    $riskReasons,
                    $manualReviewStatus
                ) {
                    return Booking::create([
                        'user_id' => $user->id,
                        'service_id' => $service->id,
                        'service_type' => $validated['service_type'],
                        'property_type' => $validated['property_type'],
                        'rooms' => 1,
                        'bathrooms' => 1,
                        'floor_area' => $validated['floor_area'],
                        'add_ons' => $pricing['add_ons'],
                        'barangay' => $validated['barangay'],
                        'street_address' => $validated['street_address'],
                        'service_latitude' => $validated['service_latitude'] ?? null,
                        'service_longitude' => $validated['service_longitude'] ?? null,
                        'scheduled_date' => $validated['scheduled_date'],
                        'scheduled_time' => $validated['scheduled_time'],
                        'duration_minutes' => $serviceDurationMinutes,
                        'notes' => $validated['notes'] ?? null,
                        'service_plan' => 'one_time',
                        'risk_reasons' => empty($riskReasons) ? null : $riskReasons,
                        'manual_review_status' => $manualReviewStatus,
                        'price' => $pricing['total'],
                        'base_price' => $pricing['base_price'],
                        'property_fee' => $pricing['property_fee'],
                        'rooms_fee' => $pricing['rooms_fee'],
                        'bathrooms_fee' => $pricing['bathrooms_fee'],
                        'floor_area_fee' => $pricing['floor_area_fee'],
                        'add_ons_fee' => $pricing['add_ons_fee'],
                        'payment_method' => $validated['payment_method'],
                        'payment_status' => 'pending',
                        'payment_reference' => null,
                        'paid_at' => null,
                        'status' => 'pending',
                        'preferred_staff_status' => 'none',
                    ]);
                });
            });
        } catch (LockTimeoutException) {
            return response()->json([
                'message' => 'Booking load is high right now. Please try again in a few seconds.',
            ], 423);
        }

        $booking->load(['service', 'cleanerApplication']);

        return response()->json([
            'message' => $manualReviewStatus === 'pending'
                ? 'Your booking request has been submitted and is pending manual review.'
                : 'Your booking request has been received.',
            'booking' => $this->bookingPayload($booking),
            'pricing' => $pricing,
            'formatted_total' => 'P'.number_format((float) $pricing['total'], 0),
        ], 201);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $this->assertClientOwns($request, $booking);

        if ($booking->status !== 'pending' || $booking->staff_id) {
            return response()->json(['message' => 'Only pending bookings without an assigned cleaner can be cancelled.'], 422);
        }

        $booking->update(['status' => 'cancelled']);
        $booking->logActivity($request->user(), 'status_updated', 'Client cancelled the booking.', [
            'from_status' => 'pending', 'to_status' => 'cancelled',
        ]);

        return response()->json(['message' => 'Your booking request has been cancelled.', 'booking' => $this->bookingPayload($booking->fresh('service'))]);
    }

    public function reschedule(Request $request, Booking $booking): JsonResponse
    {
        $this->assertClientOwns($request, $booking);
        $validated = $request->validate([
            'scheduled_date' => ['required', 'date', 'after:today'],
            'scheduled_time' => ['required', Rule::in(self::TIME_SLOTS)],
        ]);

        if (! in_array($booking->status, ['pending', 'confirmed'], true)) {
            return response()->json(['message' => 'Only pending or confirmed bookings can be rescheduled.'], 422);
        }
        if (Booking::clientHasScheduleConflict($request->user()->id, $validated['scheduled_date'], $validated['scheduled_time'], $booking->id)) {
            return response()->json(['message' => 'You already have an active booking at that date and time.'], 422);
        }
        if ($booking->staff_id && Booking::staffHasScheduleConflict($booking->staff_id, $validated['scheduled_date'], $validated['scheduled_time'], $booking->id, (int) $booking->duration_minutes)) {
            return response()->json(['message' => 'The assigned cleaner is not available at that date and time.'], 422);
        }
        if (! Booking::slotHasCapacity($validated['scheduled_date'], $validated['scheduled_time'], $booking->id)) {
            return response()->json(['message' => 'That time slot is already fully booked.'], 422);
        }

        $booking->update($validated);
        $booking->logActivity($request->user(), 'rescheduled', 'Client rescheduled the booking.', $validated);

        return response()->json(['message' => 'Your booking has been rescheduled successfully.', 'booking' => $this->bookingPayload($booking->fresh('service'))]);
    }

    public function rate(Request $request, Booking $booking): JsonResponse
    {
        $this->assertClientOwns($request, $booking);
        $validated = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        if ($booking->status !== 'completed' || ! $booking->staff_id || $booking->rating) {
            return response()->json(['message' => 'This booking is not eligible for a new rating.'], 422);
        }

        \App\Models\Rating::create([
            'booking_id' => $booking->id,
            'client_id' => $request->user()->id,
            'staff_id' => $booking->staff_id,
            'stars' => $validated['stars'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json(['message' => 'Thanks for sharing your feedback.', 'booking' => $this->bookingPayload($booking->fresh('service'))]);
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

        return response()->json(['message' => 'Your dispute has been submitted.', 'booking' => $this->bookingPayload($booking->fresh('service'))]);
    }

    private function assertClientOwns(Request $request, Booking $booking): void
    {
        abort_if($request->user()->role !== 'client', 403);
        abort_if((int) $booking->user_id !== (int) $request->user()->id, 403);
    }

    private function validateBookingRequest(Request $request): array
    {
        $validated = $request->validate([
            'service_type' => [
                'required',
                Rule::exists('services', 'slug')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'property_type' => ['required', Rule::in(array_keys(Booking::propertyTypeLabels()))],
            'floor_area' => ['required', 'integer', 'min:10', 'max:1000'],
            'add_ons' => ['nullable', 'array'],
            'add_ons.*' => ['string', Rule::in(array_keys(Booking::addOnCatalog()))],
            'payment_method' => ['required', Rule::in(array_keys(Booking::paymentMethods()))],
            'barangay' => ['required', Rule::in(array_keys(config('cleanflow.barangays', [])))],
            'street_address' => ['required', 'string', 'max:255'],
            'service_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'service_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'scheduled_time' => ['required', Rule::in(self::TIME_SLOTS)],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'barangay.in' => 'The selected barangay is not within our service area.',
            'scheduled_date.after_or_equal' => 'Please select today or a future date.',
            'scheduled_time.in' => 'Please select one of the available booking times.',
        ]);

        $bookingTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $selectedStart = Carbon::parse($validated['scheduled_date'].' '.$validated['scheduled_time'], $bookingTimezone);

        if ($selectedStart->lessThanOrEqualTo(Carbon::now($bookingTimezone))) {
            throw ValidationException::withMessages([
                'scheduled_time' => ['Please select a time later than the current time for today.'],
            ]);
        }

        $validated['add_ons'] = $validated['add_ons'] ?? [];

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
            'price' => (float) $booking->price,
            'formatted_price' => 'P'.number_format((float) $booking->price, 0),
            'scheduled_date' => $date,
            'scheduled_time' => $time,
            'schedule_label' => trim(($date ?? 'Unscheduled').' '.($time ?? '')),
            'barangay' => $booking->barangay,
            'street_address' => $booking->street_address,
            'manual_review_status' => $booking->manual_review_status,
            'risk_reasons' => $booking->risk_reasons ?? [],
            'dispute_status' => $booking->dispute_status,
            'dispute_reason' => $booking->dispute_reason,
            'provider_name' => $booking->cleanerApplication?->business_name,
            'created_at' => $booking->created_at?->toISOString(),
        ];
    }
}
