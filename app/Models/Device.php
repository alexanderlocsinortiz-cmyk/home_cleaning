<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'name',
        'serial_number',
        'api_token',
        'location',
        'is_active',
        'last_seen_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function enrollmentRequests()
    {
        return $this->hasMany(DeviceEnrollmentRequest::class);
    }
}
