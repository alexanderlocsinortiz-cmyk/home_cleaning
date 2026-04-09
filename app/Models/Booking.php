<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
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

    protected $fillable = [
        'user_id',
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
        'price',
        'base_price',
        'property_fee',
        'rooms_fee',
        'bathrooms_fee',
        'floor_area_fee',
        'add_ons_fee',
        'status',
        'staff_id',
        'address',
        'current_latitude',
        'current_longitude',
        'location_updated_at',
    ];

    protected $casts = [
        'add_ons' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_type', 'slug');
    }

    public function rating()
    {
        return $this->hasOne(Rating::class);
    }

    public function locations()
    {
        return $this->hasMany(BookingLocation::class);
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

    public static function calculatePrice($serviceType, $propertyType, $rooms, $bathrooms)
    {
        $propertyFees = [
            'house' => 0,
            'apartment' => 200,
            'boarding_house' => 300,
        ];

        $basePrice = Service::where('slug', $serviceType)->value('price');
        $basePrice = $basePrice !== null
            ? (float) $basePrice
            : match ($serviceType) {
                'basic' => 500.0,
                'deep' => 1200.0,
                'moveinout' => 2000.0,
                default => 0.0,
            };
        $propertyFee  = $propertyFees[$propertyType] ?? 0;
        $roomsFee     = ($rooms - 1) * 50;
        $bathroomsFee = ($bathrooms - 1) * 100;
        $totalPrice = $basePrice + $propertyFee + $roomsFee + $bathroomsFee;

        return [
            'base_price'    => $basePrice,
            'property_fee'  => $propertyFee,
            'rooms_fee'     => $roomsFee,
            'bathrooms_fee' => $bathroomsFee,
            'total'         => $totalPrice,
        ];
    }
}
