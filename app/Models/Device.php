<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Device extends Model
{
    protected $fillable = [
        'name',
        'serial_number',
        'api_token',
        'secret_key',
        'location',
        'is_active',
        'last_seen_at',
        'token_expires_at',
        'last_token_rotated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'last_token_rotated_at' => 'datetime',
    ];

    // Relationships
    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function enrollmentRequests()
    {
        return $this->hasMany(DeviceEnrollmentRequest::class);
    }

    // Check if token is expired
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at < now();
    }

    // Check if device can authenticate
    public function canAuthenticate(): bool
    {
        return $this->is_active && !$this->isTokenExpired();
    }

    // Generate new token and secret pair
    public static function generateTokenPair(): array
    {
        return [
            'token' => Str::random(64),
            'secret_key' => Str::random(64),
        ];
    }

    // Hash token for storage (one-way)
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    // Verify provided token against stored hash
    public function verifyToken(string $providedToken): bool
    {
        $providedHash = hash('sha256', $providedToken);
        return hash_equals($this->api_token, $providedHash);
    }
}
