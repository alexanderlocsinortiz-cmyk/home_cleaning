<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPayout extends Model
{
    protected $fillable = [
        'booking_id',
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

    protected $casts = [
        'provider_gross_amount' => 'decimal:2',
        'platform_commission_rate' => 'decimal:4',
        'platform_commission_amount' => 'decimal:2',
        'provider_payout_amount' => 'decimal:2',
        'provider_payout_paid_at' => 'datetime',
        'cash_collected_amount' => 'decimal:2',
        'provider_commission_due' => 'decimal:2',
        'provider_commission_paid_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payoutProcessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_payout_processed_by');
    }

    public function commissionCollector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_commission_collected_by');
    }
}
