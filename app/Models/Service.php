<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'price', 'is_active'];

    public static function canonicalSlugForName(string $name): string
    {
        $normalized = Str::of($name)->lower()->squish()->value();

        return match ($normalized) {
            'basic clean' => 'basic',
            'deep clean' => 'deep',
            'move-in/move-out clean',
            'move in/move out clean',
            'move-in move-out clean',
            'move in move out clean' => 'moveinout',
            default => Str::slug($name),
        };
    }

    public static function displayNameForSlug(?string $slug): string
    {
        return match ($slug) {
            'basic' => 'Basic Clean',
            'deep' => 'Deep Clean',
            'moveinout' => 'Move-in/Move-out Clean',
            null, '' => 'Unknown Service',
            default => Str::of($slug)->replace(['-', '_'], ' ')->title()->value(),
        };
    }
}
