<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CleanerApplicationActivityLog extends Model
{
    protected $fillable = [
        'cleaner_application_id',
        'actor_id',
        'action',
        'description',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function application()
    {
        return $this->belongsTo(CleanerApplication::class, 'cleaner_application_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
