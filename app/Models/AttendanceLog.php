<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'punch_type',
        'logged_at',
        'status',
        'source',
        'raw_payload'
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
