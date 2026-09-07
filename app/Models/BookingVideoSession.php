<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingVideoSession extends Model
{
    protected $fillable = [
        'booking_id',
        'daily_room_name',
        'daily_room_url',
        'daily_room_expires_at',
        'live_video_started_at',
        'live_video_ended_at',
    ];

    protected $casts = [
        'daily_room_expires_at' => 'datetime',
        'live_video_started_at' => 'datetime',
        'live_video_ended_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
