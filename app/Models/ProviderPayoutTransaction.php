<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderPayoutTransaction extends Model
{
    protected $fillable = [
        'booking_id',
        'cleaner_application_id',
        'processed_by',
        'from_status',
        'to_status',
        'provider_gross_amount',
        'platform_commission_amount',
        'provider_payout_amount',
        'payout_reference',
        'payout_paid_at',
        'payout_proof_path',
        'payout_proof_original_filename',
        'notes',
    ];

    protected $casts = [
        'provider_gross_amount' => 'decimal:2',
        'platform_commission_amount' => 'decimal:2',
        'provider_payout_amount' => 'decimal:2',
        'payout_paid_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function cleanerApplication()
    {
        return $this->belongsTo(CleanerApplication::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
