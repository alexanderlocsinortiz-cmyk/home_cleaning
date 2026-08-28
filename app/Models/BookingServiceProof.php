<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingServiceProof extends Model
{
    public const STAGES = [
        'before',
        'after',
    ];

    public const MEDIA_TYPES = [
        'image',
        'video',
    ];

    protected $fillable = [
        'booking_id',
        'uploaded_by',
        'stage',
        'media_type',
        'file_path',
        'original_name',
        'captured_at',
        'latitude',
        'longitude',
        'capture_source',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeStage(Builder $query, string $stage): Builder
    {
        return $query->where('stage', $stage);
    }

    public function scopeMediaType(Builder $query, string $mediaType): Builder
    {
        return $query->where('media_type', $mediaType);
    }

    public function isImage(): bool
    {
        return $this->media_type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }
}
