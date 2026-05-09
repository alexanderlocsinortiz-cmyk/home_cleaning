<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'staff_id',
        'user_id',
        'device_id',
        'punch_type',
        'punched_at',
        'logged_at',
        'fingerprint_template_id',
        'status',
        'source',
        'raw_payload',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
        'logged_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}

