<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingActivityLog extends Model
{
    protected $fillable = [
        'booking_id',
        'actor_id',
        'actor_role',
        'actor_name',
        'action',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
