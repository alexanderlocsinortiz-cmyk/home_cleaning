<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class SecurityEvent extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Security events are append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('Security events can only be removed by the retention command.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $event, ?User $user = null, array $metadata = []): self
    {
        $request = app()->bound('request') ? request() : null;

        return static::create([
            'user_id' => $user?->id,
            'event' => $event,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent()
                ? Str::limit((string) $request->userAgent(), 1000, '')
                : null,
            'metadata' => $metadata ?: null,
        ]);
    }
}
