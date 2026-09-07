<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServiceAddOn extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'description',
        'price',
        'pricing_unit',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function keyForLabel(string $label): string
    {
        return Str::slug($label, '_');
    }

    public static function catalog(bool $activeOnly = true): array
    {
        $query = self::query()->orderBy('sort_order')->orderBy('label');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get()
            ->mapWithKeys(fn (self $addOn) => [
                $addOn->key => [
                    'label' => $addOn->label,
                    'price' => (float) $addOn->price,
                    'pricing_unit' => $addOn->pricing_unit ?: 'per booking',
                    'description' => $addOn->description,
                    'is_active' => $addOn->is_active,
                ],
            ])
            ->all();
    }
}
