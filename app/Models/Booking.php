<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;
    public const STATUS_TRANSITIONS = [
        'pending' => ['pending', 'confirmed', 'cancelled'],
        'confirmed' => ['confirmed', 'in_progress', 'cancelled'],
        'in_progress' => ['in_progress', 'completed'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled'],
    ];

    public const STAFF_REQUIRED_STATUSES = [
        'confirmed',
        'in_progress',
        'completed',
    ];

    public const ACTIVE_SCHEDULE_STATUSES = [
        'pending',
        'confirmed',
        'in_progress',
    ];

    public const MANUAL_REVIEW_STATUSES = [
        'not_required',
        'pending',
        'approved',
        'blocked',
    ];

    public const PREFERRED_STAFF_STATUSES = [
        'none',
        'requested',
        'unavailable',
        'assigned',
        'alternate_assigned',
    ];

    public const PAYMENT_METHOD_LABELS = [
        'on_site_cash' => 'Cash on Service Day',
        'gcash' => 'GCash',
        'maya' => 'Maya',
    ];

    public const PAYMENT_STATUS_LABELS = [
        'pending' => 'Pending Payment',
        'paid' => 'Paid',
    ];

    public const SERVICE_PLAN_LABELS = [
        'one_time' => 'One-Time Booking',
        'subscription' => 'Subscription Plan',
    ];

    public const SUBSCRIPTION_FREQUENCY_LABELS = [
        'weekly' => 'Weekly',
        'biweekly' => 'Bi-Weekly',
        'monthly' => 'Monthly',
    ];

    public const PROPERTY_FEES = [
        'house' => 0.0,
        'apartment' => 200.0,
        'boarding_house' => 300.0,
    ];

    public const PROPERTY_TYPE_LABELS = [
        'house' => 'House',
        'apartment' => 'Apartment',
        'boarding_house' => 'Boarding House',
    ];

    public const INCLUDED_FLOOR_AREA = 30;

    public const FLOOR_AREA_RATES = [
        'basic' => 8.0,
        'deep' => 12.0,
        'moveinout' => 15.0,
        'postconstruction' => 14.0,
        'commercial' => 13.0,
        'weeklymaintenance' => 9.0,
    ];

    public const ADD_ON_CATALOG = [
        'window_glass' => [
            'label' => 'Window Glass Cleaning',
            'price' => 180.0,
            'description' => 'Interior glass panels and reachable windows.',
        ],
        'refrigerator' => [
            'label' => 'Refrigerator Cleaning',
            'price' => 250.0,
            'description' => 'Deep wipe-down for the inside of the refrigerator.',
        ],
        'inside_cabinets' => [
            'label' => 'Inside Cabinet Cleaning',
            'price' => 220.0,
            'description' => 'Interior shelf and cabinet surface cleaning.',
        ],
        'sofa_vacuum' => [
            'label' => 'Sofa Vacuuming',
            'price' => 300.0,
            'description' => 'Dust and crumb removal for fabric seating.',
        ],
        'pet_hair_removal' => [
            'label' => 'Pet Hair Removal',
            'price' => 200.0,
            'description' => 'Extra removal for fur on floors, rugs, and furniture.',
        ],
        'eco_friendly_supplies' => [
            'label' => 'Eco-Friendly Supplies',
            'price' => 150.0,
            'description' => 'Use greener, lower-residue cleaning products when available.',
        ],
    ];

    protected $fillable = [
        'user_id',
        'service_id',
        'service_type',
        'property_type',
        'rooms',
        'bathrooms',
        'floor_area',
        'add_ons',
        'barangay',
        'street_address',
        'scheduled_date',
        'scheduled_time',
        'notes',
        'risk_reasons',
        'manual_review_status',
        'reviewed_by',
        'reviewed_at',
        'price',
        'base_price',
        'property_adjustment',
        'property_fee',
        'room_bathroom_fees',
        'rooms_fee',
        'bathrooms_fee',
        'floor_area_fees',
        'floor_area_fee',
        'add_on_fees',
        'add_ons_fee',
        'payment_method',
        'payment_status',
        'payment_reference',
        'paid_at',
        'service_plan',
        'subscription_frequency',
        'subscription_occurrences',
        'subscription_group_id',
        'subscription_sequence',
        'status',
        'staff_id',
        'preferred_staff_id',
        'preferred_staff_status',
        'address',
        'current_latitude',
        'current_longitude',
        'location_updated_at',
    ];

    protected $casts = [
        'add_ons' => 'array',
        'risk_reasons' => 'array',
        'reviewed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function preferredStaff()
    {
        return $this->belongsTo(User::class, 'preferred_staff_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function rating()
    {
        return $this->hasOne(Rating::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function serviceProofs()
    {
        return $this->hasMany(BookingServiceProof::class)->orderBy('created_at');
    }

    public function activityLogs()
    {
        return $this->hasMany(BookingActivityLog::class)->latest();
    }

    public function messages()
    {
        return $this->hasMany(BookingMessage::class)->oldest();
    }

    public function locations()
    {
        return $this->hasMany(BookingLocation::class);
    }

    public function beforeServiceProofs()
    {
        return $this->serviceProofs()->where('stage', 'before')->where('media_type', 'image');
    }

    public function afterServiceProofs()
    {
        return $this->serviceProofs()->where('stage', 'after')->where('media_type', 'image');
    }

    public function completionVideos()
    {
        return $this->serviceProofs()->where('stage', 'after')->where('media_type', 'video');
    }

    public function getServiceLabelAttribute(): string
    {
        if ($this->relationLoaded('service') && $this->service) {
            return $this->service->name;
        }

        return Service::displayNameForSlug($this->service_type);
    }

    public static function statuses(): array
    {
        return array_keys(self::STATUS_TRANSITIONS);
    }

    public static function requiresAssignedStaffForStatus(string $status): bool
    {
        return in_array($status, self::STAFF_REQUIRED_STATUSES, true);
    }

    public static function scheduleConflictStatuses(): array
    {
        return self::ACTIVE_SCHEDULE_STATUSES;
    }

    public static function manualReviewStatuses(): array
    {
        return self::MANUAL_REVIEW_STATUSES;
    }

    public static function preferredStaffStatuses(): array
    {
        return self::PREFERRED_STAFF_STATUSES;
    }

    public static function paymentMethods(): array
    {
        return self::PAYMENT_METHOD_LABELS;
    }

    public static function paymentStatuses(): array
    {
        return array_keys(self::PAYMENT_STATUS_LABELS);
    }

    public static function paymentMethodLabel(?string $paymentMethod): string
    {
        return self::PAYMENT_METHOD_LABELS[$paymentMethod] ?? Str::of((string) $paymentMethod)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public static function paymentStatusLabel(?string $paymentStatus): string
    {
        return self::PAYMENT_STATUS_LABELS[$paymentStatus] ?? Str::of((string) $paymentStatus)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public static function isDigitalPaymentMethod(?string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['gcash', 'maya'], true);
    }

    public static function generatePaymentReference(?string $paymentMethod = null): string
    {
        $prefix = match ($paymentMethod) {
            'on_site_cash' => 'CASH',
            'gcash' => 'GCASH',
            'maya' => 'MAYA',
            default => 'PAY',
        };

        return $prefix.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
    }

    public static function servicePlans(): array
    {
        return self::SERVICE_PLAN_LABELS;
    }

    public static function servicePlanLabel(?string $servicePlan): string
    {
        return self::SERVICE_PLAN_LABELS[$servicePlan] ?? Str::of((string) $servicePlan)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public static function subscriptionFrequencyLabels(): array
    {
        return self::SUBSCRIPTION_FREQUENCY_LABELS;
    }

    public static function subscriptionFrequencyLabel(?string $frequency): string
    {
        return self::SUBSCRIPTION_FREQUENCY_LABELS[$frequency] ?? Str::of((string) $frequency)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public function isSubscription(): bool
    {
        return $this->service_plan === 'subscription';
    }

    public function subscriptionSummary(): ?string
    {
        if (! $this->isSubscription()) {
            return null;
        }

        $frequencyLabel = self::subscriptionFrequencyLabel($this->subscription_frequency);
        $occurrences = (int) ($this->subscription_occurrences ?? 0);

        if ($occurrences <= 1) {
            return $frequencyLabel;
        }

        return $frequencyLabel.' - '.$occurrences.' scheduled visit'.($occurrences === 1 ? '' : 's');
    }

    public static function propertyTypeLabels(): array
    {
        return self::PROPERTY_TYPE_LABELS;
    }

    public static function propertyTypeLabel(?string $propertyType): string
    {
        return self::PROPERTY_TYPE_LABELS[$propertyType] ?? Str::of((string) $propertyType)
            ->replace('_', ' ')
            ->title()
            ->value();
    }

    public static function includedFloorArea(): int
    {
        return self::INCLUDED_FLOOR_AREA;
    }

    public static function floorAreaRates(): array
    {
        return self::FLOOR_AREA_RATES;
    }

    public static function floorAreaRateForService(?string $serviceType): float
    {
        return (float) (self::FLOOR_AREA_RATES[$serviceType] ?? 0.0);
    }

    public static function addOnCatalog(): array
    {
        return self::ADD_ON_CATALOG;
    }

    public static function addOnLabel(string $key): string
    {
        return self::ADD_ON_CATALOG[$key]['label'] ?? Str::of($key)->replace('_', ' ')->title()->value();
    }

    public static function normalizeAddOns(mixed $addOns): array
    {
        if (! is_array($addOns)) {
            return [];
        }

        return collect($addOns)
            ->filter(fn ($key) => is_string($key) && array_key_exists($key, self::ADD_ON_CATALOG))
            ->unique()
            ->values()
            ->all();
    }

    public static function addOnBreakdown(mixed $addOns): array
    {
        return collect(self::normalizeAddOns($addOns))
            ->map(function (string $key) {
                return [
                    'key' => $key,
                    'label' => self::addOnLabel($key),
                    'price' => (float) self::ADD_ON_CATALOG[$key]['price'],
                    'description' => self::ADD_ON_CATALOG[$key]['description'] ?? '',
                ];
            })
            ->values()
            ->all();
    }

    public static function pricingConfiguration(): array
    {
        return [
            'property_fees' => self::PROPERTY_FEES,
            'property_type_labels' => self::PROPERTY_TYPE_LABELS,
            'included_floor_area' => self::includedFloorArea(),
            'floor_area_rates' => self::floorAreaRates(),
            'add_ons' => self::addOnCatalog(),
        ];
    }

    public function allowedTransitions(): array
    {
        return self::STATUS_TRANSITIONS[$this->status] ?? [$this->status];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function canBeUpdatedByStaffTo(string $status): bool
    {
        return match ($status) {
            'in_progress' => in_array($this->status, ['confirmed', 'in_progress'], true),
            'completed' => in_array($this->status, ['in_progress', 'completed'], true),
            default => false,
        };
    }

    public static function normalizeScheduleDate(mixed $scheduledDate): string
    {
        if ($scheduledDate instanceof Carbon) {
            return $scheduledDate->toDateString();
        }

        return Carbon::parse((string) $scheduledDate)->toDateString();
    }

    public static function normalizeScheduleTime(mixed $scheduledTime): string
    {
        if ($scheduledTime instanceof Carbon) {
            return $scheduledTime->format('H:i:s');
        }

        return Carbon::parse((string) $scheduledTime)->format('H:i:s');
    }

    public static function scheduleSlotKey(mixed $scheduledDate, mixed $scheduledTime): string
    {
        return self::normalizeScheduleDate($scheduledDate).'|'.self::normalizeScheduleTime($scheduledTime);
    }

    public static function scheduleConflictQuery(
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $exceptBookingId = null
    ): Builder {
        return self::query()
            ->whereIn('status', self::scheduleConflictStatuses())
            ->where(function (Builder $query) {
                $query
                    ->whereNull('manual_review_status')
                    ->orWhere('manual_review_status', '!=', 'blocked');
            })
            ->whereDate('scheduled_date', self::normalizeScheduleDate($scheduledDate))
            ->whereTime('scheduled_time', self::normalizeScheduleTime($scheduledTime))
            ->when(
                $exceptBookingId !== null,
                fn (Builder $query) => $query->where('id', '!=', $exceptBookingId)
            );
    }

    public function requiresManualReview(): bool
    {
        return $this->manual_review_status === 'pending';
    }

    public function isReviewBlocked(): bool
    {
        return $this->manual_review_status === 'blocked';
    }

    public function hasPreferredStaffRequest(): bool
    {
        return $this->preferred_staff_id !== null && $this->preferred_staff_status !== 'none';
    }

    public function hasBeforeServiceProof(): bool
    {
        return $this->beforeServiceProofs()->exists();
    }

    public function hasAfterServiceProof(): bool
    {
        return $this->afterServiceProofs()->exists();
    }

    public function logActivity(?User $actor, string $action, string $description, array $metadata = []): BookingActivityLog
    {
        return $this->activityLogs()->create([
            'actor_id' => $actor?->id,
            'actor_role' => $actor?->role,
            'actor_name' => $actor?->full_name,
            'action' => $action,
            'description' => $description,
            'metadata' => empty($metadata) ? null : $metadata,
        ]);
    }

    public static function normalizeStreetAddress(?string $streetAddress): string
    {
        return (string) Str::of((string) $streetAddress)->squish()->lower();
    }

    public static function detectRiskReasons(
        int $userId,
        string $streetAddress,
        string $barangay,
        mixed $scheduledDate,
        mixed $scheduledTime
    ): array {
        $riskReasons = [];
        $normalizedStreetAddress = self::normalizeStreetAddress($streetAddress);

        $sameAddressSameScheduleExists = self::scheduleConflictQuery($scheduledDate, $scheduledTime)
            ->where('user_id', '!=', $userId)
            ->where('barangay', $barangay)
            ->get(['street_address'])
            ->contains(fn (Booking $booking) => self::normalizeStreetAddress($booking->street_address) === $normalizedStreetAddress);

        if ($sameAddressSameScheduleExists) {
            $riskReasons[] = 'Another client already requested this exact address and schedule.';
        }

        $recentBookingCount = self::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($recentBookingCount >= 2) {
            $riskReasons[] = 'This client has created multiple booking requests within the last 24 hours.';
        }

        return $riskReasons;
    }

    public static function scheduleCapacity(): int
    {
        return max(1, User::where('role', 'staff')->count());
    }

    public static function slotHasCapacity(
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $exceptBookingId = null
    ): bool {
        return self::scheduleConflictQuery($scheduledDate, $scheduledTime, $exceptBookingId)->count() < self::scheduleCapacity();
    }

    public static function clientHasScheduleConflict(
        int $userId,
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $exceptBookingId = null
    ): bool {
        return self::scheduleConflictQuery($scheduledDate, $scheduledTime, $exceptBookingId)
            ->where('user_id', $userId)
            ->exists();
    }

    public static function staffHasScheduleConflict(
        int $staffId,
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $exceptBookingId = null
    ): bool {
        return self::scheduleConflictQuery($scheduledDate, $scheduledTime, $exceptBookingId)
            ->where('staff_id', $staffId)
            ->exists();
    }

    public static function busyStaffIdsForSchedule(
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $exceptBookingId = null
    ): array {
        return self::scheduleConflictQuery($scheduledDate, $scheduledTime, $exceptBookingId)
            ->whereNotNull('staff_id')
            ->pluck('staff_id')
            ->map(fn ($staffId) => (int) $staffId)
            ->unique()
            ->values()
            ->all();
    }

    public static function calculatePrice(
        $serviceType,
        $propertyType,
        $rooms,
        $bathrooms,
        $floorArea = 0,
        $addOns = []
    ) {
        $basePrice = Service::where('slug', $serviceType)->value('price');
        $basePrice = $basePrice !== null
            ? (float) $basePrice
            : match ($serviceType) {
                'basic' => 500.0,
                'deep' => 1200.0,
                'moveinout' => 2000.0,
                'postconstruction' => 1800.0,
                'commercial' => 1600.0,
                'weeklymaintenance' => 900.0,
                default => 0.0,
            };
        $propertyFee = (float) (self::PROPERTY_FEES[$propertyType] ?? 0.0);
        $roomsFee = max(0, ((int) $rooms) - 1) * 50;
        $bathroomsFee = max(0, ((int) $bathrooms) - 1) * 100;
        $floorArea = max(0, (int) $floorArea);
        $includedFloorArea = self::includedFloorArea();
        $billableFloorArea = max(0, $floorArea - $includedFloorArea);
        $floorAreaRate = self::floorAreaRateForService($serviceType);
        $floorAreaFee = $billableFloorArea * $floorAreaRate;
        $addOnBreakdown = self::addOnBreakdown($addOns);
        $addOnsFee = (float) collect($addOnBreakdown)->sum('price');
        $totalPrice = $basePrice + $propertyFee + $roomsFee + $bathroomsFee + $floorAreaFee + $addOnsFee;

        return [
            'base_price' => round($basePrice, 2),
            'property_fee' => round($propertyFee, 2),
            'rooms_fee' => round((float) $roomsFee, 2),
            'bathrooms_fee' => round((float) $bathroomsFee, 2),
            'floor_area' => $floorArea,
            'included_floor_area' => $includedFloorArea,
            'billable_floor_area' => $billableFloorArea,
            'floor_area_rate' => round($floorAreaRate, 2),
            'floor_area_fee' => round((float) $floorAreaFee, 2),
            'add_ons' => collect($addOnBreakdown)->pluck('key')->all(),
            'add_on_breakdown' => $addOnBreakdown,
            'add_ons_fee' => round($addOnsFee, 2),
            'total' => round($totalPrice, 2),
        ];
    }
}
