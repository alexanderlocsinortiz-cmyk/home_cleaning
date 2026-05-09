<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingMessage extends Model
{
    protected $fillable = [
        'booking_id',
        'sender_id',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
