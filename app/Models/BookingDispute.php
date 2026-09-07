<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDispute extends Model
{
    protected $fillable = [
        'booking_id',
        'status',
        'reason',
        'description',
        'disputed_at',
        'resolution',
        'admin_notes',
        'reviewed_by',
        'resolved_at',
    ];

    protected $casts = [
        'disputed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
