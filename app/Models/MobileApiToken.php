<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token_hash',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
