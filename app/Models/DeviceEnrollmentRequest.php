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
        'consent_token',
        'consent_requested_at',
        'consent_accepted_at',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected $casts = [
        'consent_requested_at' => 'datetime',
        'consent_accepted_at' => 'datetime',
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
