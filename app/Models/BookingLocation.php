<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'staff_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'heading',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy' => 'decimal:2',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
