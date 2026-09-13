<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculatePriceRequest;
use App\Models\AttendanceLog;
use App\Models\Booking;
use App\Models\CleanerApplication;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $services = Service::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->map(fn (Service $service) => $this->servicePayload($service))
            ->values();

        return response()->json([
            'services' => $services,
            'barangays' => array_keys(config('cleanflow.barangays', [])),
            'property_types' => collect(Booking::propertyTypeLabels())
                ->map(fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => $label,
                ])
                ->values(),
            'add_ons' => collect(Booking::addOnCatalog())
                ->map(fn (array $addOn, string $key) => [
                    'key' => $key,
                    'label' => $addOn['label'],
                    'price' => (float) $addOn['price'],
                    'description' => $addOn['description'] ?? '',
                    'pricing_unit' => $addOn['pricing_unit'] ?? Booking::ADD_ON_PRICING_UNIT,
                    'quantity_supported' => ($addOn['pricing_unit'] ?? Booking::ADD_ON_PRICING_UNIT) !== Booking::ADD_ON_PRICING_UNIT,
                ])
                ->values(),
            'payment_methods' => collect(Booking::paymentMethods())
                ->map(fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => $label,
                    'is_digital' => Booking::isDigitalPaymentMethod($key),
                ])
                ->values(),
            'service_plans' => collect(Booking::servicePlans())
                ->map(fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => $label,
                ])
                ->values(),
            'subscription_frequencies' => collect(Booking::subscriptionFrequencyLabels())
                ->map(fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => $label,
                ])
                ->values(),
            'pricing' => Booking::pricingConfiguration(),
        ]);
    }

    public function calculate(CalculatePriceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $pricing = Booking::calculatePrice(
            $validated['service_type'],
            $validated['property_type'],
            $validated['rooms'] ?? 1,
            $validated['bathrooms'] ?? 1,
            $validated['floor_area'],
            $validated['add_ons'] ?? [],
            $validated['add_on_quantities'] ?? []
        );

        return response()->json([
            'pricing' => $pricing,
            'formatted_total' => 'P'.number_format((float) $pricing['total'], 2),
        ]);
    }

    public function bookingAvailability(Request $request): JsonResponse
    {
        $this->assertVerifiedClient($request, 'view booking availability');
        $bookingTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $bookingToday = Carbon::now($bookingTimezone)->toDateString();
        $validated = $request->validate([
            'service_type' => [
                'required',
                Rule::exists('services', 'slug')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'property_type' => ['required', Rule::in(array_keys(Booking::propertyTypeLabels()))],
            'floor_area' => ['required', 'integer', 'min:10', 'max:1000'],
            'scheduled_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$bookingToday],
        ]);

        if (! Service::supportsPropertyType($validated['service_type'], $validated['property_type'])) {
            throw ValidationException::withMessages([
                'service_type' => ['Please choose a service that matches the selected property type.'],
            ]);
        }

        $service = Service::where('slug', $validated['service_type'])
            ->where('is_active', true)
            ->firstOrFail();
        $now = Carbon::now($bookingTimezone);
        $durationMinutes = Service::durationForArea(
            $service->slug,
            (int) $validated['floor_area'],
            (int) ($service->duration_minutes ?: Service::durationForSlug($service->slug)),
        );
        $pricing = Booking::calculatePrice(
            $validated['service_type'],
            $validated['property_type'],
            1,
            1,
            (int) $validated['floor_area'],
            [],
            []
        );
        $requiredCleaners = max(1, (int) ($pricing['required_cleaners'] ?? 1));
        $timeSlots = collect(Booking::bookingTimeSlots())->map(function (string $time) use (
            $now,
            $validated,
            $durationMinutes,
            $requiredCleaners,
            $bookingTimezone
        ): array {
            $slotStart = Carbon::parse($validated['scheduled_date'].' '.$time, $bookingTimezone);
            $isFuture = $slotStart->greaterThan($now);
            $capacityAvailable = Booking::slotHasCapacity(
                $validated['scheduled_date'],
                $time,
                null,
                $requiredCleaners,
                $durationMinutes
            );

            return [
                'time' => $time,
                'is_future' => $isFuture,
                'capacity_available' => $capacityAvailable,
                'available' => $isFuture,
                'reason' => $isFuture
                    ? ($capacityAvailable ? null : 'Capacity is currently full; this booking may require manual review.')
                    : 'Please select a later time for today.',
            ];
        })->values();

        return response()->json([
            'today' => $bookingToday,
            'now' => $now->format('H:i'),
            'timezone' => $bookingTimezone,
            'time_slots' => $timeSlots,
            'required_cleaners' => $requiredCleaners,
            'duration_minutes' => $durationMinutes,
        ]);
    }

    public function preferredCleaners(Request $request): JsonResponse
    {
        $this->assertVerifiedClient($request, 'view preferred cleaners');
        $validated = $request->validate([
            'barangay' => ['required', 'string'],
            'floor_area' => ['required', 'integer', 'min:10', 'max:1000'],
            'property_type' => ['nullable', 'string'],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'scheduled_time' => ['required', 'string'],
            'service_type' => ['required', 'string'],
        ]);

        $service = Service::where('slug', $validated['service_type'])
            ->where('is_active', true)
            ->firstOrFail();

        if (! empty($validated['property_type'])
            && ! Service::supportsPropertyType($validated['service_type'], $validated['property_type'])) {
            return response()->json([
                'message' => 'Please choose a service that matches the selected property type.',
            ], 422);
        }

        abort_unless(
            in_array($validated['barangay'], array_keys(config('cleanflow.barangays', [])), true),
            422,
            'The selected barangay is not within our service area.'
        );
        abort_unless(
            in_array($validated['scheduled_time'], Booking::bookingTimeSlots(), true),
            422,
            'Please select one of the available booking times.'
        );

        $bookingTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $scheduledDate = Carbon::parse($validated['scheduled_date'], $bookingTimezone);
        $serviceDurationMinutes = Service::durationForArea(
            $service->slug,
            (int) $validated['floor_area'],
            (int) ($service->duration_minutes ?: Service::durationForSlug($service->slug)),
        );
        $pricing = Booking::calculatePrice(
            $validated['service_type'],
            $validated['property_type'] ?? 'house',
            1,
            1,
            (int) $validated['floor_area'],
            [],
            []
        );
        $requiredCleaners = max(1, (int) ($pricing['required_cleaners'] ?? 1));
        $isToday = $scheduledDate->toDateString() === Carbon::now($bookingTimezone)->toDateString();
        $presentStaffIds = $isToday
            ? $this->presentStaffIdsForDate($scheduledDate->toDateString(), $bookingTimezone)
            : [];

        $staff = User::query()
            ->where('role', 'staff')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->reject(fn (User $staff): bool => $isToday
                && ! in_array((int) $staff->id, $presentStaffIds, true))
            ->reject(fn (User $staff): bool => Booking::staffHasScheduleConflict(
                (int) $staff->id,
                $validated['scheduled_date'],
                $validated['scheduled_time'],
                null,
                $serviceDurationMinutes
            ))
            ->map(fn (User $staff): array => [
                'id' => (int) $staff->id,
                'name' => $staff->full_name,
                'available' => true,
            ])
            ->values();

        $providers = CleanerApplication::query()
            ->with('user')
            ->where('status', CleanerApplication::STATUS_APPROVED)
            ->whereNotNull('user_id')
            ->whereNotNull('activated_at')
            ->orderBy('business_name')
            ->get()
            ->filter(function (CleanerApplication $provider) use (
                $isToday,
                $requiredCleaners,
                $scheduledDate,
                $serviceDurationMinutes,
                $validated
            ): bool {
                $availableDays = collect($provider->available_days ?: [])
                    ->map(fn ($day): string => strtolower((string) $day))
                    ->filter()
                    ->values();

                return $provider->isAvailableForAssignment()
                    && $provider->coversBarangay($validated['barangay'])
                    && $provider->offersService($validated['service_type'])
                    && (! $availableDays->isNotEmpty()
                        || $availableDays->contains(strtolower($scheduledDate->format('l'))))
                    && $provider->effectiveTeamCapacity() >= $requiredCleaners
                    && $provider->hasDailyCapacityFor($scheduledDate->toDateString())
                    && ! $provider->hasScheduleConflictFor(
                        $validated['scheduled_date'],
                        $validated['scheduled_time'],
                        $serviceDurationMinutes,
                        null,
                        $requiredCleaners
                    );
            })
            ->map(fn (CleanerApplication $provider): array => [
                'id' => (int) $provider->id,
                'name' => $provider->business_name ?: ($provider->user?->full_name ?: $provider->email),
                'team_size' => $provider->effectiveTeamCapacity(),
                'available' => true,
            ])
            ->values();

        return response()->json([
            'staff' => $staff,
            'providers' => $providers,
            'meta' => [
                'required_cleaners' => $requiredCleaners,
                'duration_minutes' => $serviceDurationMinutes,
                'timezone' => $bookingTimezone,
            ],
        ]);
    }

    private function presentStaffIdsForDate(string $localDate, string $timezone): array
    {
        $localDay = Carbon::parse($localDate, $timezone);

        return AttendanceLog::query()
            ->where('punch_type', 'in')
            ->whereBetween('logged_at', [
                $localDay->copy()->startOfDay()->utc(),
                $localDay->copy()->endOfDay()->utc(),
            ])
            ->pluck('user_id')
            ->map(fn ($staffId): int => (int) $staffId)
            ->unique()
            ->values()
            ->all();
    }

    private function assertVerifiedClient(Request $request, string $action): void
    {
        $user = $request->user();

        if ($user?->role !== 'client') {
            abort(response()->json(['message' => 'Only client accounts can '.$action.'.'], 403));
        }

        if (! $user->hasVerifiedEmail()) {
            abort(response()->json([
                'message' => 'Please verify your email before you '.$action.'.',
                'requires_email_verification' => true,
            ], 403));
        }
    }

    private function servicePayload(Service $service): array
    {
        $metadata = Service::packageMetadataFor($service->slug) ?? [];
        $priceRange = Service::priceRangeForSlug($service->slug);

        return [
            'id' => $service->id,
            'slug' => $service->slug,
            'name' => $service->name,
            'description' => $service->description,
            'image_url' => $service->image_url,
            'image_alt' => $service->image_alt,
            'price' => (float) $service->price,
            'duration_minutes' => (int) ($service->duration_minutes ?: Service::durationForSlug($service->slug)),
            'badge' => $metadata['badge'] ?? null,
            'icon' => $metadata['icon'] ?? null,
            'summary' => $metadata['summary'] ?? $service->description,
            'highlight' => $metadata['highlight'] ?? null,
            'features' => $metadata['features'] ?? [],
            'pricing_unit' => $metadata['pricing_unit'] ?? 'fixed',
            'price_range' => $priceRange,
            'uses_per_square_meter_pricing' => Service::usesPerSquareMeterPricing($service->slug),
            'uses_flat_rate_range_pricing' => Service::usesFlatRateRangePricing($service->slug),
            'scope' => $service->scopeSummary(),
            'scope_definition' => $service->scopeDefinition(),
        ];
    }
}
