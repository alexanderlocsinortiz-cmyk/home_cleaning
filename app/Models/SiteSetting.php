<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $fillable = [
        'website_name',
        'logo_path',
        'contact_email',
        'contact_phone',
        'contact_address',
        'office_hours',
        'admin_name',
        'admin_email',
        'admin_phone',
        'database_backup_password_hash',
    ];

    protected $hidden = [
        'database_backup_password_hash',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], static::defaults());
    }

    public static function defaults(): array
    {
        return [
            'website_name' => 'Home Cleaning Services',
            'contact_email' => null,
            'contact_phone' => null,
            'contact_address' => null,
            'office_hours' => null,
            'admin_name' => 'Admin Team',
            'admin_email' => null,
            'admin_phone' => null,
        ];
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            return Storage::disk(config('filesystems.public_uploads_disk'))->url($this->logo_path);
        }

        return asset('images/logo.png').'?v=20260510-logo4';
    }
}
