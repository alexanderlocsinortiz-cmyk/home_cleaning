<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'method',
        'status',
        'amount',
        'collected_amount',
        'currency',
        'provider',
        'provider_payment_id',
        'checkout_session_id',
        'reference',
        'receipt_number',
        'paid_at',
        'collected_at',
        'collected_by',
        'receipt_notes',
        'cash_proof_path',
        'cash_proof_original_name',
        'cash_proof_mime_type',
        'cash_proof_size',
        'cash_proof_status',
        'cash_proof_submitted_at',
        'cash_proof_reviewed_at',
        'cash_proof_reviewed_by',
        'cash_proof_rejection_reason',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'collected_at' => 'datetime',
        'cash_proof_size' => 'integer',
        'cash_proof_submitted_at' => 'datetime',
        'cash_proof_reviewed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function cashProofReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cash_proof_reviewed_by');
    }
}
