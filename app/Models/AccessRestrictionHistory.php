<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessRestrictionHistory extends Model
{
    protected $fillable = [
        'target_user_id',
        'actor_user_id',
        'target_name',
        'target_email',
        'target_role',
        'action',
        'duration_days',
        'restricted_until',
        'reason',
        'restricted_pages',
        'meta',
    ];

    protected $casts = [
        'restricted_until' => 'datetime',
        'restricted_pages' => 'array',
        'meta' => 'array',
    ];

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
