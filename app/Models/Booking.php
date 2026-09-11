<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    public const PAYMENT_ATTRIBUTES = [
        'payment_method',
        'payment_status',
        'payment_reference',
        'payment_checkout_session_id',
        'paid_at',
        'cash_receipt_number',
        'payment_collected_amount',
        'payment_collected_at',
        'payment_collected_by',
        'payment_receipt_notes',
    ];

    public const DISPUTE_ATTRIBUTES = [
        'dispute_status',
        'dispute_reason',
        'dispute_description',
        'disputed_at',
        'dispute_resolution',
        'dispute_admin_notes',
        'dispute_reviewed_by',
        'dispute_resolved_at',
    ];

    public const PAYOUT_ATTRIBUTES = [
        'provider_gross_amount',
        'platform_commission_rate',
        'platform_commission_amount',
        'provider_payout_amount',
        'provider_payout_status',
        'provider_payout_reference',
        'provider_payout_paid_at',
        'provider_payout_processed_by',
        'provider_payout_proof_path',
        'provider_payout_proof_original_filename',
        'cash_collected_amount',
        'provider_commission_due',
        'provider_commission_status',
        'provider_commission_reference',
        'provider_commission_paid_at',
        'provider_commission_collected_by',
        'provider_commission_proof_path',
        'provider_commission_proof_original_filename',
    ];

    public const VIDEO_ATTRIBUTES = [
        'daily_room_name',
        'daily_room_url',
        'daily_room_expires_at',
        'live_video_started_at',
        'live_video_ended_at',
    ];

    /** @var array<string, mixed> */
    protected array $pendingPaymentAttributes = [];

    protected mixed $pendingServiceType = null;

    protected bool $hasPendingServiceType = false;

    protected bool $priceChangedForPaymentSync = false;

    /** @var array<string, mixed> */
    protected array $pendingDisputeAttributes = [];

    /** @var array<string, mixed> */
    protected array $pendingPayoutAttributes = [];

    /** @var array<string, mixed> */
    protected array $pendingVideoAttributes = [];

    public const STATUS_TRANSITIONS = [
        'pending' => ['pending', 'confirmed', 'cancelled'],
        'confirmed' => ['confirmed', 'in_progress', 'cancelled'],
        'in_progress' => ['in_progress', 'completed'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled'],
    ];

    public const BOOKING_TIME_SLOTS = [
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

    public const STAFF_REQUIRED_STATUSES = [
        'in_progress',
        'completed',
    ];

    public const ACTIVE_SCHEDULE_STATUSES = [
        'pending',
        'confirmed',
        'in_progress',
    ];

    // Pending requests may wait in the queue without reserving a cleaner.
    // Only operationally accepted bookings consume staffing capacity.
    public const CAPACITY_SCHEDULE_STATUSES = [
        'confirmed',
        'in_progress',
    ];

    public const STAFF_ASSIGNMENT_CONFLICT_STATUSES = [
        'pending',
        'confirmed',
        'in_progress',
        'completed',
    ];

    public const STAFF_REST_MINUTES = 60;

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

    public const PROVIDER_ASSIGNMENT_STATUSES = [
        'pending',
        'accepted',
        'declined',
    ];

    public const PROVIDER_ASSIGNMENT_STATUS_LABELS = [
        'pending' => 'Pending response',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
    ];

    public const PROVIDER_PAYOUT_STATUSES = [
        'pending',
        'ready',
        'paid',
        'held',
        'cash_collected',
    ];

    public const PROVIDER_PAYOUT_STATUS_LABELS = [
        'pending' => 'Pending payout',
        'ready' => 'Ready for payout',
        'paid' => 'Paid',
        'held' => 'Held',
        'cash_collected' => 'Cash collected by provider',
    ];

    public const PROVIDER_COMMISSION_STATUSES = [
        'not_applicable',
        'unpaid',
        'paid',
        'held',
        'waived',
    ];

    public const PROVIDER_COMMISSION_STATUS_LABELS = [
        'not_applicable' => 'Not applicable',
        'unpaid' => 'Commission unpaid',
        'paid' => 'Commission paid',
        'held' => 'Commission held',
        'waived' => 'Commission waived',
    ];

    public const DISPUTE_STATUSES = [
        'open',
        'resolved',
        'rejected',
    ];

    public const DISPUTE_STATUS_LABELS = [
        'open' => 'Open dispute',
        'resolved' => 'Resolved',
        'rejected' => 'Rejected',
    ];

    public const DISPUTE_REASONS = [
        'poor_quality' => 'Poor service quality',
        'incomplete_service' => 'Incomplete service',
        'late_or_no_show' => 'Late or no-show',
        'damage_or_missing_item' => 'Damage or missing item',
        'payment_or_refund' => 'Payment or refund issue',
        'other' => 'Other',
    ];

    public const DISPUTE_RESOLUTIONS = [
        'release_payout' => 'Release payout',
        'refund_customer' => 'Refund customer',
        'partial_refund' => 'Partial refund',
        'reject_dispute' => 'Reject dispute',
    ];

    protected static function booted(): void
    {
        static::saving(function (Booking $booking): void {
            $booking->priceChangedForPaymentSync = $booking->exists && $booking->isDirty('price');
            $booking->resolveCanonicalServiceId();

            if ($booking->shouldRefreshMarketplaceAmountsForDirtyMoneyFields()) {
                $booking->refreshMarketplaceAmounts();
            }
        });

        static::saved(function (Booking $booking): void {
            $booking->syncPaymentAttributes();
            $booking->syncDisputeAttributes();
            $booking->syncPayoutAttributes();
            $booking->syncVideoAttributes();
        });
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, self::PAYMENT_ATTRIBUTES, true)) {
            $this->pendingPaymentAttributes[$key] = $value;

            return $this;
        }

        if (in_array($key, self::DISPUTE_ATTRIBUTES, true)) {
            $this->pendingDisputeAttributes[$key] = $value;

            return $this;
        }

        if (in_array($key, self::PAYOUT_ATTRIBUTES, true)) {
            $this->pendingPayoutAttributes[$key] = $value;

            return $this;
        }

        if (in_array($key, self::VIDEO_ATTRIBUTES, true)) {
            $this->pendingVideoAttributes[$key] = $value;

            return $this;
        }

        if ($key === 'service_type') {
            $this->pendingServiceType = $value;
            $this->hasPendingServiceType = true;

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public const PAYMENT_METHOD_LABELS = [
        'on_site_cash' => 'Cash on Service Day',
        'gcash' => 'GCash',
        'maya' => 'Maya',
    ];

    public const PAYMENT_STATUS_LABELS = [
        'pending' => 'Pending Payment',
        'paid' => 'Paid',
        'refunded' => 'Refunded',
    ];

    public const MANUAL_PAYMENT_STATUS_LABELS = [
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
        'apartment' => 0.0,
        'boarding_house' => 0.0,
        'office' => 0.0,
    ];

    public const PROPERTY_TYPE_LABELS = [
        'house' => 'House',
        'apartment' => 'Apartment',
        'boarding_house' => 'Boarding House',
        'office' => 'Office',
    ];

    public const INCLUDED_FLOOR_AREA = 30;

    public const FLOOR_AREA_RATES = [
        'basic' => 35.0,
        'basic-clean' => 35.0,
        'deep' => 95.0,
        'moveinout' => 80.0,
        'postconstruction' => 105.0,
        'commercial' => 35.0,
        'office-basic' => 30.0,
        'office-deep' => 60.0,
        'weeklymaintenance' => 9.0,
    ];

    public const ADD_ON_CATALOG = [
        'window_glass' => [
            'label' => 'Window Glass Cleaning',
            'price' => 200.0,
            'description' => 'Interior glass panels and reachable windows.',
        ],
        'refrigerator' => [
            'label' => 'Refrigerator Interior Cleaning – Small',
            'price' => 350.0,
            'description' => 'Interior cleaning for a small refrigerator.',
            'pricing_unit' => 'per unit',
        ],
        'inside_cabinets' => [
            'label' => 'Inside Cabinet Cleaning',
            'price' => 300.0,
            'description' => 'Interior shelf and cabinet surface cleaning.',
        ],
        'sofa_vacuum' => [
            'label' => 'Sofa Vacuuming',
            'price' => 400.0,
            'description' => 'Dust and crumb removal for fabric seating.',
        ],
        'sofa_deep_cleaning' => [
            'label' => 'Sofa Deep Cleaning',
            'price' => 300.0,
            'description' => 'Deep cleaning for fabric sofa seats.',
            'pricing_unit' => 'per seat',
        ],
        'mattress_single' => [
            'label' => 'Mattress Cleaning – Single',
            'price' => 900.0,
            'description' => 'Deep cleaning for one single mattress.',
            'pricing_unit' => 'per mattress',
        ],
        'mattress_double' => [
            'label' => 'Mattress Cleaning – Double',
            'price' => 1200.0,
            'description' => 'Deep cleaning for one double mattress.',
            'pricing_unit' => 'per mattress',
        ],
        'mattress_queen' => [
            'label' => 'Mattress Cleaning – Queen',
            'price' => 1500.0,
            'description' => 'Deep cleaning for one queen mattress.',
            'pricing_unit' => 'per mattress',
        ],
        'mattress_king' => [
            'label' => 'Mattress Cleaning – King',
            'price' => 1800.0,
            'description' => 'Deep cleaning for one king mattress.',
            'pricing_unit' => 'per mattress',
        ],
        'refrigerator_regular' => [
            'label' => 'Refrigerator Interior Cleaning – Regular',
            'price' => 650.0,
            'description' => 'Interior cleaning for a regular two-door refrigerator.',
            'pricing_unit' => 'per unit',
        ],
        'carpet_small' => [
            'label' => 'Carpet Cleaning – Small',
            'price' => 800.0,
            'description' => 'Cleaning for a carpet below 2×3 meters.',
            'pricing_unit' => 'per carpet',
        ],
        'closet_cleaning' => [
            'label' => 'Closet Cleaning & Arrangement',
            'price' => 150.0,
            'description' => 'Cleaning and arrangement for one cabinet or closet.',
            'pricing_unit' => 'per cabinet/closet',
        ],
        'pet_hair_removal' => [
            'label' => 'Pet Hair Removal',
            'price' => 300.0,
            'description' => 'Extra removal for fur on floors, rugs, and furniture.',
        ],
        'yard_sweeping' => [
            'label' => 'Yard Sweeping',
            'price' => 250.0,
            'description' => 'Sweeping for walkways, patios, and accessible yard areas.',
        ],
    ];

    public const ADD_ON_PRICING_UNIT = 'per booking';

    protected $fillable = [
        'user_id',
        'service_id',
        'service_label',
        'service_type',
        'property_type',
        'rooms',
        'bathrooms',
        'floor_area',
        'add_ons',
        'add_on_quantities',
        'barangay',
        'street_address',
        'service_latitude',
        'service_longitude',
        'scheduled_date',
        'scheduled_time',
        'duration_minutes',
        'expected_started_at',
        'expected_completed_at',
        'started_at',
        'completed_at',
        'started_late_minutes',
        'completed_late_minutes',
        'on_time_status',
        'on_time_notes',
        'notes',
        'risk_reasons',
        'manual_review_status',
        'reviewed_by',
        'reviewed_at',
        'price',
        'base_price',
        'property_fee',
        'rooms_fee',
        'bathrooms_fee',
        'floor_area_fee',
        'add_ons_fee',
        'required_cleaners',
        'payment_method',
        'payment_status',
        'payment_reference',
        'payment_checkout_session_id',
        'paid_at',
        'cash_receipt_number',
        'payment_collected_amount',
        'payment_collected_at',
        'payment_collected_by',
        'payment_receipt_notes',
        'service_plan',
        'subscription_frequency',
        'subscription_occurrences',
        'subscription_group_id',
        'subscription_sequence',
        'status',
        'staff_id',
        'cleaner_application_id',
        'provider_assignment_status',
        'provider_assignment_responded_at',
        'provider_assignment_notes',
        'provider_gross_amount',
        'platform_commission_rate',
        'platform_commission_amount',
        'provider_payout_amount',
        'provider_payout_status',
        'provider_payout_reference',
        'provider_payout_paid_at',
        'provider_payout_processed_by',
        'provider_payout_proof_path',
        'provider_payout_proof_original_filename',
        'cash_collected_amount',
        'provider_commission_due',
        'provider_commission_status',
        'provider_commission_reference',
        'provider_commission_paid_at',
        'provider_commission_collected_by',
        'provider_commission_proof_path',
        'provider_commission_proof_original_filename',
        'dispute_status',
        'dispute_reason',
        'dispute_description',
        'disputed_at',
        'dispute_resolution',
        'dispute_admin_notes',
        'dispute_reviewed_by',
        'dispute_resolved_at',
        'preferred_staff_id',
        'preferred_staff_status',
        'preferred_cleaner_application_id',
        'preferred_cleaner_status',
        'current_latitude',
        'current_longitude',
        'location_updated_at',
        'daily_room_name',
        'daily_room_url',
        'daily_room_expires_at',
        'live_video_started_at',
        'live_video_ended_at',
    ];

    protected $casts = [
        'add_ons' => 'array',
        'add_on_quantities' => 'array',
        'risk_reasons' => 'array',
        'scheduled_date' => 'date',
        'location_updated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'expected_started_at' => 'datetime',
        'expected_completed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'required_cleaners' => 'integer',
        'provider_assignment_responded_at' => 'datetime',
        'provider_gross_amount' => 'decimal:2',
        'platform_commission_rate' => 'decimal:4',
        'platform_commission_amount' => 'decimal:2',
        'provider_payout_amount' => 'decimal:2',
        'provider_payout_paid_at' => 'datetime',
        'cash_collected_amount' => 'decimal:2',
        'provider_commission_due' => 'decimal:2',
        'provider_commission_paid_at' => 'datetime',
        'disputed_at' => 'datetime',
        'dispute_resolved_at' => 'datetime',
        'daily_room_expires_at' => 'datetime',
        'live_video_started_at' => 'datetime',
        'live_video_ended_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany('id');
    }

    public function paymentOrCreate(array $defaults = []): Payment
    {
        try {
            return $this->payment()->firstOrCreate([], $defaults);
        } catch (QueryException $exception) {
            $payment = $this->payment()->first();

            if ($payment) {
                return $payment;
            }

            throw $exception;
        }
    }

    public static function staffingRequiresManualReview(int $requiredCleaners): bool
    {
        return $requiredCleaners > (int) config('cleanflow.staffing.max_cleaners_per_booking', 20);
    }

    public static function staffingManualReviewReason(int $requiredCleaners): string
    {
        $maximum = (int) config('cleanflow.staffing.max_cleaners_per_booking', 20);

        return "This booking requires {$requiredCleaners} cleaners, exceeding the automatic staffing limit of {$maximum}.";
    }

    public static function capacityManualReviewReason(int $requiredCleaners, int $availableCleaners): string
    {
        return "Only {$availableCleaners} qualified cleaners are available for this schedule, but this booking requires {$requiredCleaners}.";
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(BookingDispute::class);
    }

    public function payout(): HasOne
    {
        return $this->hasOne(BookingPayout::class);
    }

    public function videoSession(): HasOne
    {
        return $this->hasOne(BookingVideoSession::class);
    }

    public function getServiceTypeAttribute($value): ?string
    {
        if ($this->hasPendingServiceType) {
            return $this->pendingServiceType;
        }

        if ($this->relationLoaded('service') && $this->service) {
            return $this->service->slug;
        }

        return $this->service?->slug ?? $this->getRawOriginal('service_label') ?? $value;
    }

    public function getPaymentMethodAttribute($value): string
    {
        return (string) $this->paymentValue('payment_method', 'on_site_cash');
    }

    public function getPaymentStatusAttribute($value): string
    {
        return (string) $this->paymentValue('payment_status', 'pending');
    }

    public function getPaymentReferenceAttribute($value): ?string
    {
        return $this->paymentValue('payment_reference');
    }

    public function getPaymentCheckoutSessionIdAttribute($value): ?string
    {
        return $this->paymentValue('payment_checkout_session_id');
    }

    public function getPaidAtAttribute($value): mixed
    {
        return $this->paymentValue('paid_at');
    }

    public function getCashReceiptNumberAttribute($value): ?string
    {
        return $this->paymentValue('cash_receipt_number');
    }

    public function getPaymentCollectedAmountAttribute($value): mixed
    {
        return $this->paymentValue('payment_collected_amount');
    }

    public function getPaymentCollectedAtAttribute($value): mixed
    {
        return $this->paymentValue('payment_collected_at');
    }

    public function getPaymentCollectedByAttribute($value): ?int
    {
        $collectedBy = $this->paymentValue('payment_collected_by');

        return $collectedBy !== null ? (int) $collectedBy : null;
    }

    public function getPaymentReceiptNotesAttribute($value): ?string
    {
        return $this->paymentValue('payment_receipt_notes');
    }

    public function getDisputeStatusAttribute($value): ?string
    {
        return $this->disputeValue('dispute_status');
    }

    public function getDisputeReasonAttribute($value): ?string
    {
        return $this->disputeValue('dispute_reason');
    }

    public function getDisputeDescriptionAttribute($value): ?string
    {
        return $this->disputeValue('dispute_description');
    }

    public function getDisputedAtAttribute($value): mixed
    {
        return $this->disputeValue('disputed_at');
    }

    public function getDisputeResolutionAttribute($value): ?string
    {
        return $this->disputeValue('dispute_resolution');
    }

    public function getDisputeAdminNotesAttribute($value): ?string
    {
        return $this->disputeValue('dispute_admin_notes');
    }

    public function getDisputeReviewedByAttribute($value): ?int
    {
        $reviewedBy = $this->disputeValue('dispute_reviewed_by');

        return $reviewedBy !== null ? (int) $reviewedBy : null;
    }

    public function getDisputeResolvedAtAttribute($value): mixed
    {
        return $this->disputeValue('dispute_resolved_at');
    }

    public function getProviderGrossAmountAttribute($value): mixed
    {
        return $this->payoutValue('provider_gross_amount');
    }

    public function getPlatformCommissionRateAttribute($value): mixed
    {
        return $this->payoutValue('platform_commission_rate');
    }

    public function getPlatformCommissionAmountAttribute($value): mixed
    {
        return $this->payoutValue('platform_commission_amount');
    }

    public function getProviderPayoutAmountAttribute($value): mixed
    {
        return $this->payoutValue('provider_payout_amount');
    }

    public function getProviderPayoutStatusAttribute($value): ?string
    {
        return $this->payoutValue('provider_payout_status');
    }

    public function getProviderPayoutReferenceAttribute($value): ?string
    {
        return $this->payoutValue('provider_payout_reference');
    }

    public function getProviderPayoutPaidAtAttribute($value): mixed
    {
        return $this->payoutValue('provider_payout_paid_at');
    }

    public function getProviderPayoutProcessedByAttribute($value): ?int
    {
        $processedBy = $this->payoutValue('provider_payout_processed_by');

        return $processedBy !== null ? (int) $processedBy : null;
    }

    public function getProviderPayoutProofPathAttribute($value): ?string
    {
        return $this->payoutValue('provider_payout_proof_path');
    }

    public function getProviderPayoutProofOriginalFilenameAttribute($value): ?string
    {
        return $this->payoutValue('provider_payout_proof_original_filename');
    }

    public function getCashCollectedAmountAttribute($value): mixed
    {
        return $this->payoutValue('cash_collected_amount');
    }

    public function getProviderCommissionDueAttribute($value): mixed
    {
        return $this->payoutValue('provider_commission_due');
    }

    public function getProviderCommissionStatusAttribute($value): ?string
    {
        return $this->payoutValue('provider_commission_status');
    }

    public function getProviderCommissionReferenceAttribute($value): ?string
    {
        return $this->payoutValue('provider_commission_reference');
    }

    public function getProviderCommissionPaidAtAttribute($value): mixed
    {
        return $this->payoutValue('provider_commission_paid_at');
    }

    public function getProviderCommissionCollectedByAttribute($value): ?int
    {
        $collectedBy = $this->payoutValue('provider_commission_collected_by');

        return $collectedBy !== null ? (int) $collectedBy : null;
    }

    public function getProviderCommissionProofPathAttribute($value): ?string
    {
        return $this->payoutValue('provider_commission_proof_path');
    }

    public function getProviderCommissionProofOriginalFilenameAttribute($value): ?string
    {
        return $this->payoutValue('provider_commission_proof_original_filename');
    }

    public function getDailyRoomNameAttribute($value): ?string
    {
        return $this->videoValue('daily_room_name');
    }

    public function getDailyRoomUrlAttribute($value): ?string
    {
        return $this->videoValue('daily_room_url');
    }

    public function getDailyRoomExpiresAtAttribute($value): mixed
    {
        return $this->videoValue('daily_room_expires_at');
    }

    public function getLiveVideoStartedAtAttribute($value): mixed
    {
        return $this->videoValue('live_video_started_at');
    }

    public function getLiveVideoEndedAtAttribute($value): mixed
    {
        return $this->videoValue('live_video_ended_at');
    }

    public function getPaymentCollectorAttribute(): ?User
    {
        return $this->payment?->collector;
    }

    private function paymentValue(string $attribute, mixed $default = null): mixed
    {
        if (array_key_exists($attribute, $this->pendingPaymentAttributes)) {
            return $this->pendingPaymentAttributes[$attribute];
        }

        $payment = $this->exists ? $this->payment : null;

        if (! $payment) {
            return $default;
        }

        return match ($attribute) {
            'payment_method' => $payment->method,
            'payment_status' => $payment->status,
            'payment_reference' => $payment->reference,
            'payment_checkout_session_id' => $payment->checkout_session_id,
            'paid_at' => $payment->paid_at,
            'cash_receipt_number' => $payment->receipt_number,
            'payment_collected_amount' => $payment->method === 'on_site_cash' ? $payment->collected_amount : null,
            'payment_collected_at' => $payment->collected_at,
            'payment_collected_by' => $payment->collected_by,
            'payment_receipt_notes' => $payment->receipt_notes,
            default => $default,
        };
    }

    private function disputeValue(string $attribute, mixed $default = null): mixed
    {
        if (array_key_exists($attribute, $this->pendingDisputeAttributes)) {
            return $this->pendingDisputeAttributes[$attribute];
        }

        $dispute = $this->exists ? $this->dispute : null;

        if (! $dispute) {
            return $default;
        }

        return match ($attribute) {
            'dispute_status' => $dispute->status,
            'dispute_reason' => $dispute->reason,
            'dispute_description' => $dispute->description,
            'disputed_at' => $dispute->disputed_at,
            'dispute_resolution' => $dispute->resolution,
            'dispute_admin_notes' => $dispute->admin_notes,
            'dispute_reviewed_by' => $dispute->reviewed_by,
            'dispute_resolved_at' => $dispute->resolved_at,
            default => $default,
        };
    }

    private function syncDisputeAttributes(): void
    {
        if (! $this->exists || $this->pendingDisputeAttributes === []) {
            return;
        }

        $pending = $this->pendingDisputeAttributes;
        $dispute = $this->relationLoaded('dispute')
            ? $this->getRelation('dispute')
            : $this->dispute()->first();

        $attributes = [
            'status' => $pending['dispute_status'] ?? $dispute?->status,
            'reason' => $pending['dispute_reason'] ?? $dispute?->reason,
            'description' => $pending['dispute_description'] ?? $dispute?->description,
            'disputed_at' => $pending['disputed_at'] ?? $dispute?->disputed_at,
            'resolution' => $pending['dispute_resolution'] ?? $dispute?->resolution,
            'admin_notes' => $pending['dispute_admin_notes'] ?? $dispute?->admin_notes,
            'reviewed_by' => $pending['dispute_reviewed_by'] ?? $dispute?->reviewed_by,
            'resolved_at' => $pending['dispute_resolved_at'] ?? $dispute?->resolved_at,
        ];

        if (! $dispute) {
            $dispute = $this->dispute()->create($attributes);
        } else {
            $dispute->forceFill($attributes)->save();
        }

        $this->setRelation('dispute', $dispute);
        $this->pendingDisputeAttributes = [];
    }

    private function payoutValue(string $attribute, mixed $default = null): mixed
    {
        if (array_key_exists($attribute, $this->pendingPayoutAttributes)) {
            return $this->pendingPayoutAttributes[$attribute];
        }

        $payout = $this->exists ? $this->payout : null;

        if (! $payout) {
            return $default;
        }

        return $payout->getAttribute($attribute);
    }

    private function syncPayoutAttributes(): void
    {
        if (! $this->exists || $this->pendingPayoutAttributes === []) {
            return;
        }

        $pending = $this->pendingPayoutAttributes;
        $payout = $this->relationLoaded('payout')
            ? $this->getRelation('payout')
            : $this->payout()->first();
        $attributes = [];

        foreach (self::PAYOUT_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $pending)) {
                $attributes[$attribute] = $pending[$attribute];
            } elseif ($payout) {
                $attributes[$attribute] = $payout->getAttribute($attribute);
            }
        }

        if (! $payout) {
            $payout = $this->payout()->create($attributes);
        } else {
            $payout->forceFill($attributes)->save();
        }

        $this->setRelation('payout', $payout);
        $this->pendingPayoutAttributes = [];
    }

    private function videoValue(string $attribute, mixed $default = null): mixed
    {
        if (array_key_exists($attribute, $this->pendingVideoAttributes)) {
            return $this->pendingVideoAttributes[$attribute];
        }

        $session = $this->exists ? $this->videoSession : null;

        return $session?->getAttribute($attribute) ?? $default;
    }

    private function syncVideoAttributes(): void
    {
        if (! $this->exists || $this->pendingVideoAttributes === []) {
            return;
        }

        $pending = $this->pendingVideoAttributes;
        $session = $this->relationLoaded('videoSession')
            ? $this->getRelation('videoSession')
            : $this->videoSession()->first();
        $attributes = [];

        foreach (self::VIDEO_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $pending)) {
                $attributes[$attribute] = $pending[$attribute];
            } elseif ($session) {
                $attributes[$attribute] = $session->getAttribute($attribute);
            }
        }

        if (! $session) {
            $session = $this->videoSession()->create($attributes);
        } else {
            $session->forceFill($attributes)->save();
        }

        $this->setRelation('videoSession', $session);
        $this->pendingVideoAttributes = [];
    }

    private function resolveCanonicalServiceId(): void
    {
        if (! $this->hasPendingServiceType || $this->getRawOriginal('service_id')) {
            return;
        }

        $slug = Service::catalogSlug($this->pendingServiceType) ?: $this->pendingServiceType;
        $serviceId = Service::query()->where('slug', $slug)->value('id');

        if ($serviceId) {
            parent::setAttribute('service_id', $serviceId);
        } else {
            parent::setAttribute('service_label', $this->pendingServiceType);
        }
    }

    private function syncPaymentAttributes(): void
    {
        if (! $this->exists) {
            return;
        }

        $pending = $this->pendingPaymentAttributes;

        if ($pending === [] && ! $this->wasRecentlyCreated && ! $this->priceChangedForPaymentSync) {
            $this->hasPendingServiceType = false;
            $this->pendingServiceType = null;

            return;
        }

        $payment = $this->relationLoaded('payment')
            ? $this->getRelation('payment')
            : $this->payments()->latest('id')->first();

        $method = $pending['payment_method'] ?? $payment?->method ?? 'on_site_cash';
        $status = $pending['payment_status'] ?? $payment?->status ?? 'pending';
        $attributes = [
            'method' => $method,
            'status' => $status,
            'amount' => $payment?->status === 'paid'
                ? $payment->amount
                : ($this->price ?? 0),
            'collected_amount' => $method === 'on_site_cash'
                ? (array_key_exists('payment_collected_amount', $pending)
                    ? $pending['payment_collected_amount']
                    : $payment?->collected_amount)
                : null,
            'currency' => 'PHP',
            'provider' => $method === 'on_site_cash' ? 'manual' : 'paymongo',
        ];

        $attributeMap = [
            'payment_reference' => 'reference',
            'payment_checkout_session_id' => 'checkout_session_id',
            'paid_at' => 'paid_at',
            'cash_receipt_number' => 'receipt_number',
            'payment_collected_at' => 'collected_at',
            'payment_collected_by' => 'collected_by',
            'payment_receipt_notes' => 'receipt_notes',
        ];

        foreach ($attributeMap as $bookingAttribute => $paymentAttribute) {
            if (array_key_exists($bookingAttribute, $pending)) {
                $attributes[$paymentAttribute] = $pending[$bookingAttribute];
            }
        }

        if (! $payment) {
            $payment = $this->paymentOrCreate($attributes);
        } elseif ($pending !== [] || $this->priceChangedForPaymentSync) {
            $payment->forceFill($attributes)->save();
        }

        $this->setRelation('payment', $payment);
        $this->pendingPaymentAttributes = [];
        $this->priceChangedForPaymentSync = false;
        $this->hasPendingServiceType = false;
        $this->pendingServiceType = null;
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function staffAssignments()
    {
        return $this->hasMany(BookingStaffAssignment::class)->orderBy('id');
    }

    public function isAssignedToStaff(int $staffId): bool
    {
        return (int) $this->staff_id === $staffId
            || ($this->relationLoaded('staffAssignments')
                ? $this->staffAssignments->contains(fn ($assignment) => (int) $assignment->staff_id === $staffId)
                : $this->staffAssignments()->where('staff_id', $staffId)->exists());
    }

    public function assignedStaffIds(): array
    {
        $assignmentIds = $this->relationLoaded('staffAssignments')
            ? $this->staffAssignments->pluck('staff_id')
            : $this->staffAssignments()->pluck('staff_id');

        return collect([$this->staff_id])
            ->merge($assignmentIds)
            ->filter()
            ->map(fn ($staffId): int => (int) $staffId)
            ->unique()
            ->values()
            ->all();
    }

    public function scopeAssignedToStaff(Builder $query, int $staffId): Builder
    {
        return $query->where(function (Builder $query) use ($staffId) {
            $query->where('staff_id', $staffId)
                ->orWhereHas('staffAssignments', fn (Builder $assignments) => $assignments->where('staff_id', $staffId));
        });
    }

    public function cleanerApplication()
    {
        return $this->belongsTo(CleanerApplication::class);
    }

    public function preferredStaff()
    {
        return $this->belongsTo(User::class, 'preferred_staff_id');
    }

    public function preferredCleanerApplication()
    {
        return $this->belongsTo(CleanerApplication::class, 'preferred_cleaner_application_id');
    }

    public function providerPayoutProcessor()
    {
        return $this->belongsTo(User::class, 'provider_payout_processed_by');
    }

    public function providerPayoutTransactions()
    {
        return $this->hasMany(ProviderPayoutTransaction::class)->latest();
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

    public static function capacityScheduleStatuses(): array
    {
        return self::CAPACITY_SCHEDULE_STATUSES;
    }

    public static function staffAssignmentConflictStatuses(): array
    {
        return self::STAFF_ASSIGNMENT_CONFLICT_STATUSES;
    }

    public static function manualReviewStatuses(): array
    {
        return self::MANUAL_REVIEW_STATUSES;
    }

    public static function preferredStaffStatuses(): array
    {
        return self::PREFERRED_STAFF_STATUSES;
    }

    public static function providerAssignmentStatuses(): array
    {
        return self::PROVIDER_ASSIGNMENT_STATUSES;
    }

    public static function providerAssignmentStatusLabel(?string $status): string
    {
        return self::PROVIDER_ASSIGNMENT_STATUS_LABELS[$status ?: 'pending'] ?? Str::of((string) $status)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public function effectiveProviderAssignmentStatus(): ?string
    {
        if (! $this->cleaner_application_id) {
            return null;
        }

        return $this->provider_assignment_status ?: 'pending';
    }

    public function hasAcceptedProviderAssignment(): bool
    {
        return $this->cleaner_application_id !== null
            && $this->effectiveProviderAssignmentStatus() === 'accepted';
    }

    public function clientCanCancel(): bool
    {
        if (! in_array($this->status, ['pending', 'confirmed'], true)) {
            return false;
        }

        // A secondary cleaner assignment or an accepted marketplace provider
        // is also an operational assignment, even when staff_id is null.
        if ($this->assignedStaffIds() !== [] || $this->hasAcceptedProviderAssignment()) {
            return false;
        }

        // Cash bookings keep the existing policy: only pending requests can
        // be cancelled by the client. Online bookings may be confirmed before
        // payment settles, so they must remain cancellable while unassigned.
        return $this->status === 'pending'
            || self::isDigitalPaymentMethod($this->payment?->method ?? $this->getRawOriginal('payment_method'));
    }

    public function providerAssignmentBadgeClass(): string
    {
        return match ($this->effectiveProviderAssignmentStatus()) {
            'accepted' => 'bg-emerald-100 text-emerald-700',
            'declined' => 'bg-red-100 text-red-700',
            'pending' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-500',
        };
    }

    public function canProviderRespondToAssignment(): bool
    {
        return $this->cleaner_application_id !== null
            && $this->effectiveProviderAssignmentStatus() === 'pending'
            && ! in_array($this->status, ['completed', 'cancelled'], true);
    }

    public static function providerPayoutStatusLabel(?string $status): string
    {
        return self::PROVIDER_PAYOUT_STATUS_LABELS[$status ?: 'pending'] ?? Str::of((string) $status)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public static function providerPayoutStatuses(): array
    {
        return self::PROVIDER_PAYOUT_STATUSES;
    }

    public static function providerCommissionStatuses(): array
    {
        return self::PROVIDER_COMMISSION_STATUSES;
    }

    public static function providerCommissionStatusLabel(?string $status): string
    {
        return self::PROVIDER_COMMISSION_STATUS_LABELS[$status ?: 'not_applicable'] ?? Str::of((string) $status)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public static function disputeStatuses(): array
    {
        return self::DISPUTE_STATUSES;
    }

    public static function disputeReasons(): array
    {
        return self::DISPUTE_REASONS;
    }

    public static function disputeResolutions(): array
    {
        return self::DISPUTE_RESOLUTIONS;
    }

    public static function disputeStatusLabel(?string $status): string
    {
        return self::DISPUTE_STATUS_LABELS[$status ?: ''] ?? Str::of((string) $status)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public function disputeReasonLabel(): string
    {
        return self::DISPUTE_REASONS[$this->dispute_reason] ?? Str::of((string) $this->dispute_reason)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public function disputeResolutionLabel(): ?string
    {
        if (! $this->dispute_resolution) {
            return null;
        }

        return self::DISPUTE_RESOLUTIONS[$this->dispute_resolution] ?? Str::of((string) $this->dispute_resolution)
            ->replace(['_', '-'], ' ')
            ->title()
            ->value();
    }

    public function hasOpenDispute(): bool
    {
        return $this->dispute_status === 'open';
    }

    public function canClientOpenDispute(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id
            && $this->status === 'completed'
            && ! $this->dispute_status
            && $this->provider_payout_status !== 'paid';
    }

    public function calculateMarketplaceCommission(?float $commissionRate = null): array
    {
        $gross = round((float) ($this->price ?? 0), 2);
        $rate = round((float) ($commissionRate ?? config('cleanflow.marketplace.default_commission_rate', 0.15)), 4);
        $commission = round($gross * $rate, 2);
        $payout = round(max(0, $gross - $commission), 2);
        $isCash = $this->payment_method === 'on_site_cash';

        return [
            'provider_gross_amount' => $gross,
            'platform_commission_rate' => $rate,
            'platform_commission_amount' => $commission,
            'provider_payout_amount' => $payout,
            'provider_payout_status' => $isCash ? 'cash_collected' : 'pending',
            'cash_collected_amount' => $isCash ? $gross : null,
            'provider_commission_due' => $isCash ? $commission : null,
            'provider_commission_status' => $isCash ? 'unpaid' : 'not_applicable',
        ];
    }

    public function shouldRefreshMarketplaceAmountsForDirtyMoneyFields(): bool
    {
        return $this->exists
            && $this->cleaner_application_id !== null
            && $this->provider_gross_amount !== null
            && ($this->isDirty('price') || array_key_exists('payment_method', $this->pendingPaymentAttributes))
            && ! $this->marketplaceAmountsAreSettled();
    }

    public function marketplaceAmountsAreSettled(): bool
    {
        return $this->provider_payout_status === 'paid'
            || in_array($this->provider_commission_status, ['paid', 'waived'], true);
    }

    public function refreshMarketplaceAmounts(?float $commissionRate = null): bool
    {
        if (! $this->cleaner_application_id || $this->provider_gross_amount === null || $this->marketplaceAmountsAreSettled()) {
            return false;
        }

        $this->forceFill($this->calculateMarketplaceCommission($commissionRate));

        if ($this->payment_method === 'on_site_cash') {
            $this->provider_payout_reference = null;
            $this->provider_payout_paid_at = null;
            $this->provider_payout_processed_by = null;
            $this->provider_payout_proof_path = null;
            $this->provider_payout_proof_original_filename = null;
        } else {
            $this->provider_commission_reference = null;
            $this->provider_commission_paid_at = null;
            $this->provider_commission_collected_by = null;
            $this->provider_commission_proof_path = null;
            $this->provider_commission_proof_original_filename = null;
        }

        return true;
    }

    public function clearMarketplaceCommission(): void
    {
        $this->provider_gross_amount = null;
        $this->platform_commission_rate = null;
        $this->platform_commission_amount = null;
        $this->provider_payout_amount = null;
        $this->provider_payout_status = null;
        $this->provider_payout_reference = null;
        $this->provider_payout_paid_at = null;
        $this->provider_payout_processed_by = null;
        $this->provider_payout_proof_path = null;
        $this->provider_payout_proof_original_filename = null;
        $this->cash_collected_amount = null;
        $this->provider_commission_due = null;
        $this->provider_commission_status = null;
        $this->provider_commission_reference = null;
        $this->provider_commission_paid_at = null;
        $this->provider_commission_collected_by = null;
        $this->provider_commission_proof_path = null;
        $this->provider_commission_proof_original_filename = null;
    }

    public static function paymentMethods(): array
    {
        return self::PAYMENT_METHOD_LABELS;
    }

    public static function bookingTimeSlots(): array
    {
        return self::BOOKING_TIME_SLOTS;
    }

    public static function paymentStatuses(): array
    {
        return array_keys(self::MANUAL_PAYMENT_STATUS_LABELS);
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

    public static function generateCashReceiptNumber(): string
    {
        return 'RCPT-CASH-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
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

    /**
     * Return the configured customer-service map bounds in a validation-friendly shape.
     *
     * @return array{min_latitude: float, max_latitude: float, min_longitude: float, max_longitude: float}
     */
    public static function serviceLocationBounds(): array
    {
        $bounds = config('cleanflow.map.maxBounds', [[-90, -180], [90, 180]]);

        return [
            'min_latitude' => (float) ($bounds[0][0] ?? -90),
            'max_latitude' => (float) ($bounds[1][0] ?? 90),
            'min_longitude' => (float) ($bounds[0][1] ?? -180),
            'max_longitude' => (float) ($bounds[1][1] ?? 180),
        ];
    }

    public static function includedFloorArea(): int
    {
        return self::INCLUDED_FLOOR_AREA;
    }

    public static function floorAreaRates(): array
    {
        $rates = collect(Service::PACKAGE_CATALOG)
            ->filter(fn (array $package) => ($package['pricing_unit'] ?? null) === 'sqm')
            ->mapWithKeys(fn (array $package, string $slug) => [$slug => Service::effectiveRateForSlug($slug)])
            ->all();

        if (array_key_exists('basic', $rates)) {
            $rates['basic-clean'] = $rates['basic'];
        }

        return $rates;
    }

    public static function floorAreaRateForService(?string $serviceType): float
    {
        if (! Service::usesPerSquareMeterPricing($serviceType)) {
            return 0.0;
        }

        return Service::effectiveRateForSlug($serviceType);
    }

    public static function requiredCleanerCountForService(?string $serviceType, ?int $floorArea): int
    {
        return Service::requiredCleanerCountForSlug($serviceType, $floorArea);
    }

    public static function billableFloorAreaForService(?string $serviceType, int $floorArea): int
    {
        $floorArea = max(0, $floorArea);

        if (Service::usesPerSquareMeterPricing($serviceType)) {
            return $floorArea;
        }

        if (Service::usesFlatRateRangePricing($serviceType)) {
            return 0;
        }

        return max(0, $floorArea - self::includedFloorArea());
    }

    public static function addOnCatalog(bool $activeOnly = true): array
    {
        if (Schema::hasTable('service_add_ons')) {
            $catalog = ServiceAddOn::catalog($activeOnly);

            if ($catalog !== []) {
                return collect($catalog)
                    ->map(fn (array $addOn) => array_merge($addOn, [
                        'pricing_unit' => $addOn['pricing_unit'] ?? self::ADD_ON_PRICING_UNIT,
                    ]))
                    ->all();
            }
        }

        return $activeOnly
            ? collect(self::ADD_ON_CATALOG)
                ->map(fn (array $addOn) => array_merge($addOn, [
                    'pricing_unit' => $addOn['pricing_unit'] ?? self::ADD_ON_PRICING_UNIT,
                ]))
                ->all()
            : collect(self::ADD_ON_CATALOG)
                ->map(fn (array $addOn) => array_merge($addOn, [
                    'is_active' => true,
                    'pricing_unit' => $addOn['pricing_unit'] ?? self::ADD_ON_PRICING_UNIT,
                ]))
                ->all();
    }

    public static function addOnLabel(string $key): string
    {
        return self::addOnCatalog(false)[$key]['label'] ?? Str::of($key)->replace('_', ' ')->title()->value();
    }

    public static function normalizeAddOns(mixed $addOns): array
    {
        if (! is_array($addOns)) {
            return [];
        }

        return collect($addOns)
            ->filter(fn ($key) => is_string($key) && array_key_exists($key, self::addOnCatalog()))
            ->unique()
            ->values()
            ->all();
    }

    public static function normalizeAddOnQuantities(mixed $quantities, mixed $addOns = []): array
    {
        $catalog = self::addOnCatalog(false);
        $selected = collect(is_array($addOns) ? $addOns : [])
            ->filter(fn ($key) => is_string($key) && array_key_exists($key, $catalog))
            ->unique()
            ->values()
            ->all();
        $input = is_array($quantities) ? $quantities : [];

        return collect($selected)
            ->mapWithKeys(function (string $key) use ($input, $catalog) {
                $quantity = (int) ($input[$key] ?? 1);
                $unit = $catalog[$key]['pricing_unit'] ?? self::ADD_ON_PRICING_UNIT;

                return [$key => $unit === self::ADD_ON_PRICING_UNIT ? 1 : max(1, min($quantity, 50))];
            })
            ->all();
    }

    public static function addOnBreakdown(mixed $addOns, mixed $quantities = []): array
    {
        $catalog = self::addOnCatalog(false);
        $selected = collect(is_array($addOns) ? $addOns : [])
            ->filter(fn ($key) => is_string($key) && array_key_exists($key, $catalog))
            ->unique()
            ->values()
            ->all();
        $normalizedQuantities = self::normalizeAddOnQuantities($quantities, $selected);

        return collect($selected)
            ->filter(fn ($key) => is_string($key) && array_key_exists($key, $catalog))
            ->map(function (string $key) use ($normalizedQuantities) {
                $catalog = self::addOnCatalog(false);
                $quantity = $normalizedQuantities[$key] ?? 1;
                $unitPrice = (float) $catalog[$key]['price'];

                return [
                    'key' => $key,
                    'label' => self::addOnLabel($key),
                    'price' => round($unitPrice * $quantity, 2),
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'description' => $catalog[$key]['description'] ?? '',
                    'pricing_unit' => $catalog[$key]['pricing_unit'] ?? self::ADD_ON_PRICING_UNIT,
                ];
            })
            ->values()
            ->all();
    }

    public static function pricingConfiguration(): array
    {
        $catalogAliases = [
            'basic' => ['basic-clean'],
        ];

        $perSquareMeterServices = collect(Service::PACKAGE_CATALOG)
            ->filter(fn (array $package) => ($package['pricing_unit'] ?? null) === 'sqm')
            ->keys()
            ->flatMap(fn (string $slug) => array_merge([$slug], $catalogAliases[$slug] ?? []))
            ->values()
            ->all();

        $flatRateRangeServices = collect(Service::PACKAGE_CATALOG)
            ->filter(fn (array $package) => ($package['pricing_unit'] ?? null) === 'flat_range')
            ->flatMap(function (array $package, string $slug) use ($catalogAliases) {
                $range = [
                    'min' => (float) ($package['price_range']['min'] ?? $package['recommended_price'] ?? 0),
                    'max' => (float) ($package['price_range']['max'] ?? $package['recommended_price'] ?? 0),
                ];

                return collect(array_merge([$slug], $catalogAliases[$slug] ?? []))
                    ->mapWithKeys(fn (string $serviceSlug) => [$serviceSlug => $range]);
            })
            ->all();

        return [
            'property_fees' => self::PROPERTY_FEES,
            'property_type_labels' => self::PROPERTY_TYPE_LABELS,
            'included_floor_area' => self::includedFloorArea(),
            'floor_area_rates' => self::floorAreaRates(),
            'per_square_meter_services' => $perSquareMeterServices,
            'flat_rate_range_services' => $flatRateRangeServices,
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

    public function canUseLiveVideo(): bool
    {
        return $this->status === 'in_progress' && ($this->staff_id !== null || $this->staffAssignments()->exists());
    }

    public function dailyRoomIsActive(): bool
    {
        return filled($this->daily_room_name)
            && filled($this->daily_room_url)
            && $this->daily_room_expires_at
            && Carbon::parse($this->daily_room_expires_at)->isFuture();
    }

    public function canAccessLiveVideo(User $user): bool
    {
        if (! $this->canUseLiveVideo()) {
            return false;
        }

        return match ($user->role) {
            'admin' => true,
            'client' => (int) $this->user_id === (int) $user->id,
            'staff' => $this->isAssignedToStaff((int) $user->id),
            default => false,
        };
    }

    public function canManageLiveVideo(User $user): bool
    {
        if (! $this->canUseLiveVideo()) {
            return false;
        }

        return $user->role === 'admin'
            || ($user->role === 'staff' && $this->isAssignedToStaff((int) $user->id));
    }

    public function expectedServiceStart(): Carbon
    {
        $timezone = config('cleanflow.attendance_timezone', 'Asia/Manila');

        return Carbon::parse(self::normalizeScheduleDate($this->scheduled_date).' '.self::normalizeScheduleTime($this->scheduled_time), $timezone)
            ->utc();
    }

    public function expectedServiceCompletion(): Carbon
    {
        return $this->expectedServiceStart()->copy()->addMinutes((int) ($this->duration_minutes ?: Service::DEFAULT_DURATION_MINUTES));
    }

    public function setExpectedServiceWindow(): void
    {
        $this->expected_started_at = $this->expected_started_at ?: $this->expectedServiceStart();
        $this->expected_completed_at = $this->expected_completed_at ?: $this->expectedServiceCompletion();
        $this->on_time_status = $this->on_time_status ?: 'not_started';
    }

    public function markServiceStarted(?Carbon $startedAt = null): void
    {
        $this->setExpectedServiceWindow();
        $this->started_at = $this->started_at ?: ($startedAt ?: now());
        $this->refreshTimelinessStatus();
    }

    public function markServiceCompleted(?Carbon $completedAt = null): void
    {
        $this->setExpectedServiceWindow();
        $this->started_at = $this->started_at ?: ($this->expected_started_at ?: now());
        $this->completed_at = $this->completed_at ?: ($completedAt ?: now());
        $this->refreshTimelinessStatus();
    }

    public function refreshTimelinessStatus(): void
    {
        $expectedStartedAt = $this->expected_started_at ? Carbon::parse($this->expected_started_at) : $this->expectedServiceStart();
        $expectedCompletedAt = $this->expected_completed_at ? Carbon::parse($this->expected_completed_at) : $this->expectedServiceCompletion();
        $startedAt = $this->started_at ? Carbon::parse($this->started_at) : null;
        $completedAt = $this->completed_at ? Carbon::parse($this->completed_at) : null;

        $this->started_late_minutes = $startedAt && $startedAt->gt($expectedStartedAt)
            ? $this->wholeLateMinutes($expectedStartedAt, $startedAt)
            : 0;
        $this->completed_late_minutes = $completedAt && $completedAt->gt($expectedCompletedAt)
            ? $this->wholeLateMinutes($expectedCompletedAt, $completedAt)
            : 0;

        $this->on_time_status = match (true) {
            ! $startedAt => 'not_started',
            $completedAt && $this->started_late_minutes > 0 && $this->completed_late_minutes > 0 => 'late',
            $completedAt && $this->completed_late_minutes > 0 => 'completed_late',
            $this->started_late_minutes > 0 => 'started_late',
            default => 'on_time',
        };

        $this->on_time_notes = $this->timelinessSummary();
    }

    private function wholeLateMinutes(Carbon $expectedAt, Carbon $actualAt): int
    {
        return max(0, (int) ceil($expectedAt->diffInMinutes($actualAt)));
    }

    public function timelinessLabel(): string
    {
        return match ($this->on_time_status) {
            'on_time' => 'On Time',
            'started_late' => 'Started Late',
            'completed_late' => 'Completed Late',
            'late' => 'Started and Completed Late',
            'not_started' => 'Not Started',
            default => 'Not Tracked',
        };
    }

    public function timelinessBadgeClass(): string
    {
        return match ($this->on_time_status) {
            'on_time' => 'bg-green-100 text-green-800',
            'started_late' => 'bg-amber-100 text-amber-800',
            'completed_late', 'late' => 'bg-red-100 text-red-700',
            'not_started' => 'bg-slate-100 text-slate-600',
            default => 'bg-slate-100 text-slate-500',
        };
    }

    public function timelinessSummary(): string
    {
        if (! $this->started_at) {
            return 'Service has not started yet.';
        }

        if (! $this->completed_at) {
            return $this->started_late_minutes > 0
                ? 'Started '.$this->started_late_minutes.' minutes late.'
                : 'Started on time.';
        }

        if ($this->started_late_minutes === 0 && $this->completed_late_minutes === 0) {
            return 'Started and completed on time.';
        }

        if ($this->started_late_minutes > 0 && $this->completed_late_minutes > 0) {
            return 'Started '.$this->started_late_minutes.' minutes late and completed '.$this->completed_late_minutes.' minutes late.';
        }

        if ($this->completed_late_minutes > 0) {
            return 'Completed '.$this->completed_late_minutes.' minutes late.';
        }

        return 'Started '.$this->started_late_minutes.' minutes late but completed within the expected window.';
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

    public function hasPreferredCleanerRequest(): bool
    {
        return $this->preferred_cleaner_application_id !== null
            && $this->preferred_cleaner_status !== 'none';
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

        $sameScheduleBookings = self::scheduleConflictQuery($scheduledDate, $scheduledTime)
            ->where('user_id', '!=', $userId)
            ->get(['barangay', 'street_address']);

        if ($sameScheduleBookings->isNotEmpty()) {
            $riskReasons[] = 'Another client already has an active booking at this date and time.';
        }

        $sameAddressSameScheduleExists = $sameScheduleBookings
            ->where('barangay', $barangay)
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

    /**
     * Return the number of staff-role users whose schedule can still cover a
     * booking window. Until staff qualifications are modelled separately, a
     * staff-role user is treated as qualified for the service catalogue.
     */
    public static function availableCleanerCountForSchedule(
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $targetDurationMinutes = null,
        ?int $exceptBookingId = null
    ): int {
        $totalStaff = User::where('role', 'staff')->count();

        if ($totalStaff === 0) {
            return 0;
        }

        $busyStaffIds = [];
        $unassignedCleanerDemand = 0;

        self::query()
            ->whereIn('status', self::capacityScheduleStatuses())
            ->where(function (Builder $query) {
                $query
                    ->whereNull('manual_review_status')
                    ->orWhere('manual_review_status', '!=', 'blocked');
            })
            ->whereDate('scheduled_date', self::normalizeScheduleDate($scheduledDate))
            ->when(
                $exceptBookingId !== null,
                fn (Builder $query) => $query->where('id', '!=', $exceptBookingId)
            )
            ->with('staffAssignments:id,booking_id,staff_id')
            ->get([
                'id',
                'staff_id',
                'required_cleaners',
                'scheduled_date',
                'scheduled_time',
                'duration_minutes',
                'status',
            ])
            ->each(function (Booking $existingBooking) use (&$busyStaffIds, &$unassignedCleanerDemand, $scheduledDate, $scheduledTime, $targetDurationMinutes): void {
                if (! self::assignmentWindowsOverlap(
                    $scheduledDate,
                    $scheduledTime,
                    $targetDurationMinutes,
                    $existingBooking->scheduled_date,
                    $existingBooking->scheduled_time,
                    (int) ($existingBooking->duration_minutes ?: Service::durationForSlug($existingBooking->service_type))
                )) {
                    return;
                }

                $assignedStaffIds = collect([$existingBooking->staff_id])
                    ->merge($existingBooking->staffAssignments->pluck('staff_id'))
                    ->filter()
                    ->map(fn ($staffId): int => (int) $staffId)
                    ->unique()
                    ->values();

                $busyStaffIds = array_merge($busyStaffIds, $assignedStaffIds->all());

                $requiredCleaners = max((int) ($existingBooking->required_cleaners ?: 1), 1);
                $unassignedCleanerDemand += max(0, $requiredCleaners - $assignedStaffIds->count());
            });

        return max(0, $totalStaff - count(array_unique($busyStaffIds)) - $unassignedCleanerDemand);
    }

    public static function slotHasCapacity(
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $exceptBookingId = null,
        int $requiredCleaners = 1,
        ?int $targetDurationMinutes = null
    ): bool {
        return self::availableCleanerCountForSchedule(
            $scheduledDate,
            $scheduledTime,
            $targetDurationMinutes,
            $exceptBookingId
        ) >= max(1, $requiredCleaners);
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
        ?int $exceptBookingId = null,
        ?int $targetDurationMinutes = null
    ): bool {
        return self::conflictingStaffBooking($staffId, $scheduledDate, $scheduledTime, $exceptBookingId, $targetDurationMinutes) !== null;
    }

    public static function busyStaffIdsForSchedule(
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $exceptBookingId = null,
        ?int $targetDurationMinutes = null
    ): array {
        return self::busyStaffIdsForAssignment($scheduledDate, $scheduledTime, $exceptBookingId, $targetDurationMinutes);
    }

    public static function busyStaffIdsForAssignment(
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $exceptBookingId = null,
        ?int $targetDurationMinutes = null
    ): array {
        return self::query()
            ->whereIn('status', self::staffAssignmentConflictStatuses())
            ->whereDate('scheduled_date', self::normalizeScheduleDate($scheduledDate))
            ->when(
                $exceptBookingId !== null,
                fn (Builder $query) => $query->where('id', '!=', $exceptBookingId)
            )
            ->with('staffAssignments:id,booking_id,staff_id')
            ->get(['id', 'staff_id', 'scheduled_date', 'scheduled_time', 'duration_minutes', 'status', 'updated_at'])
            ->filter(fn (Booking $booking) => self::staffBookingConflictsWithSchedule($booking, $scheduledDate, $scheduledTime, $targetDurationMinutes))
            ->flatMap(fn (Booking $booking) => collect([$booking->staff_id])->merge($booking->staffAssignments->pluck('staff_id')))
            ->filter()
            ->map(fn ($staffId) => (int) $staffId)
            ->unique()
            ->values()
            ->all();
    }

    public static function conflictingStaffBooking(
        int $staffId,
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $exceptBookingId = null,
        ?int $targetDurationMinutes = null
    ): ?self {
        return self::query()
            ->whereIn('status', self::staffAssignmentConflictStatuses())
            ->whereDate('scheduled_date', self::normalizeScheduleDate($scheduledDate))
            ->when(
                $exceptBookingId !== null,
                fn (Builder $query) => $query->where('id', '!=', $exceptBookingId)
            )
            ->with('staffAssignments:id,booking_id,staff_id')
            ->get(['id', 'staff_id', 'scheduled_date', 'scheduled_time', 'duration_minutes', 'status', 'updated_at'])
            ->filter(fn (Booking $booking) => $booking->isAssignedToStaff($staffId))
            ->first(fn (Booking $booking) => self::staffBookingConflictsWithSchedule($booking, $scheduledDate, $scheduledTime, $targetDurationMinutes));
    }

    public static function staffBookingConflictsWithSchedule(
        Booking $existingBooking,
        mixed $scheduledDate,
        mixed $scheduledTime,
        ?int $targetDurationMinutes = null
    ): bool {
        if (self::normalizeScheduleDate($existingBooking->scheduled_date) !== self::normalizeScheduleDate($scheduledDate)) {
            return false;
        }

        if ($existingBooking->status === 'completed') {
            $restStart = $existingBooking->updated_at
                ? Carbon::parse($existingBooking->updated_at)
                : self::assignmentWindowStart($existingBooking->scheduled_date, $existingBooking->scheduled_time);
            $restEnd = $restStart->copy()->addMinutes(self::STAFF_REST_MINUTES);
            $targetStart = self::assignmentWindowStart($scheduledDate, $scheduledTime);
            $targetEnd = self::assignmentWindowEnd($scheduledDate, $scheduledTime, $targetDurationMinutes);

            return $targetStart->lt($restEnd) && $restStart->lt($targetEnd);
        }

        return self::assignmentWindowsOverlap(
            $scheduledDate,
            $scheduledTime,
            $targetDurationMinutes,
            $existingBooking->scheduled_date,
            $existingBooking->scheduled_time,
            (int) ($existingBooking->duration_minutes ?: Service::durationForSlug($existingBooking->service_type))
        );
    }

    public static function assignmentWindowStart(mixed $scheduledDate, mixed $scheduledTime): Carbon
    {
        return Carbon::parse(
            self::normalizeScheduleDate($scheduledDate).' '.self::normalizeScheduleTime($scheduledTime),
            config('cleanflow.attendance_timezone', 'Asia/Manila')
        );
    }

    public static function assignmentWindowEnd(mixed $scheduledDate, mixed $scheduledTime, ?int $durationMinutes = null): Carbon
    {
        $durationMinutes = max(1, (int) ($durationMinutes ?: Service::DEFAULT_DURATION_MINUTES));

        return self::assignmentWindowStart($scheduledDate, $scheduledTime)
            ->addMinutes($durationMinutes + self::STAFF_REST_MINUTES);
    }

    public static function assignmentWindowsOverlap(
        mixed $leftDate,
        mixed $leftTime,
        ?int $leftDurationMinutes,
        mixed $rightDate,
        mixed $rightTime,
        ?int $rightDurationMinutes = null
    ): bool {
        $leftStart = self::assignmentWindowStart($leftDate, $leftTime);
        $leftEnd = self::assignmentWindowEnd($leftDate, $leftTime, $leftDurationMinutes);
        $rightStart = self::assignmentWindowStart($rightDate, $rightTime);
        $rightEnd = self::assignmentWindowEnd($rightDate, $rightTime, $rightDurationMinutes);

        return $leftStart->lt($rightEnd) && $rightStart->lt($leftEnd);
    }

    public static function calculatePrice(
        $serviceType,
        $propertyType,
        $rooms,
        $bathrooms,
        $floorArea = 0,
        $addOns = [],
        $addOnQuantities = []
    ) {
        $basePrice = Service::where('slug', $serviceType)->value('price');
        $basePrice = $basePrice !== null
            ? (float) $basePrice
            : match ($serviceType) {
                'basic', 'basic-clean' => 35.0,
                'deep' => 95.0,
                'moveinout' => 80.0,
                'postconstruction' => 105.0,
                'commercial' => 35.0,
                'office-basic' => 30.0,
                'office-deep' => 60.0,
                'weeklymaintenance' => 500.0,
                default => 0.0,
            };
        $isPerSquareMeter = Service::usesPerSquareMeterPricing($serviceType);
        $isFlatRateRange = Service::usesFlatRateRangePricing($serviceType);
        if ($isFlatRateRange) {
            $range = Service::priceRangeForSlug($serviceType);
            $configuredPrice = Service::effectiveRateForSlug($serviceType);
            $basePrice = $configuredPrice > 0
                ? $configuredPrice
                : (float) ($range['min'] ?? $basePrice);
        }
        $basePrice = $isPerSquareMeter ? 0.0 : $basePrice;
        $propertyFee = $isFlatRateRange ? 0.0 : (float) (self::PROPERTY_FEES[$propertyType] ?? 0.0);
        $roomsFee = 0.0;
        $bathroomsFee = 0.0;
        $floorArea = max(0, (int) $floorArea);
        $includedFloorArea = self::includedFloorArea();
        $billableFloorArea = $isFlatRateRange ? 0 : self::billableFloorAreaForService($serviceType, $floorArea);
        $floorAreaRate = self::floorAreaRateForService($serviceType);
        $floorAreaFee = $billableFloorArea * $floorAreaRate;
        $addOnBreakdown = self::addOnBreakdown($addOns, $addOnQuantities);
        $addOnsFee = (float) collect($addOnBreakdown)->sum('price');
        $totalPrice = $basePrice + $propertyFee + $roomsFee + $bathroomsFee + $floorAreaFee + $addOnsFee;
        $requiredCleaners = self::requiredCleanerCountForService($serviceType, $floorArea);

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
            'cleaner_capacity_sqm' => Service::cleanerCapacityForSlug($serviceType),
            'required_cleaners' => $requiredCleaners,
            'staffing_manual_review' => self::staffingRequiresManualReview($requiredCleaners),
            'add_ons' => collect($addOnBreakdown)->pluck('key')->all(),
            'add_on_quantities' => collect($addOnBreakdown)->mapWithKeys(fn (array $addOn) => [$addOn['key'] => $addOn['quantity']])->all(),
            'add_on_breakdown' => $addOnBreakdown,
            'add_ons_fee' => round($addOnsFee, 2),
            'total' => round($totalPrice, 2),
        ];
    }
}
