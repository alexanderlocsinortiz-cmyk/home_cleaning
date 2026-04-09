<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceEnrollmentRequest extends Model
{
    protected $fillable = [
        'device_id',
        'user_id',
        'requested_by',
        'template_id',
        'status',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
