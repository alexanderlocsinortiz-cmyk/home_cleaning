<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    public const DEFAULT_DURATION_MINUTES = 60;

    protected $fillable = ['name', 'slug', 'description', 'price', 'duration_minutes', 'is_active'];

    public const PACKAGE_CATALOG = [
        'basic' => [
            'name' => 'Basic Clean',
            'badge' => 'Signature Package',
            'icon' => 'fa-broom',
            'summary' => 'Routine cleaning for regularly maintained homes.',
            'highlight' => 'Best for weekly or bi-weekly upkeep.',
            'default_description' => 'Routine cleaning package for regularly maintained spaces, including general dusting, sweeping, mopping, and bathroom refresh work.',
            'recommended_price' => 35.0,
            'pricing_unit' => 'sqm',
            'recommended_duration_minutes' => 60,
            'features' => [
                'General dusting and surface wipe-down',
                'Floor sweeping and mopping',
                'Bathroom and kitchen touch-up cleaning',
            ],
        ],
        'deep' => [
            'name' => 'Deep Clean',
            'badge' => 'Premium Package',
            'icon' => 'fa-spray-can-sparkles',
            'summary' => 'Detailed cleaning for buildup, neglected zones, and harder-to-reach areas.',
            'highlight' => 'Recommended for seasonal resets and heavy-duty cleaning.',
            'default_description' => 'Detailed cleaning package for homes that need extra attention, focused scrubbing, and extended surface treatment across key living areas.',
            'recommended_price' => 95.0,
            'pricing_unit' => 'sqm',
            'recommended_duration_minutes' => 180,
            'features' => [
                'Detailed bathroom and kitchen scrubbing',
                'Focused grime and buildup removal',
                'Expanded surface and corner detailing',
            ],
        ],
        'moveinout' => [
            'name' => 'Move-in/Move-out Clean',
            'badge' => 'Turnover Package',
            'icon' => 'fa-truck-moving',
            'summary' => 'Full-space reset for turnovers, move preparation, and handover cleaning.',
            'highlight' => 'Ideal for preparing an empty or newly vacated property.',
            'default_description' => 'Comprehensive turnover cleaning package for move-ins, move-outs, and property handovers that require full-space preparation.',
            'recommended_price' => 80.0,
            'pricing_unit' => 'sqm',
            'pricing_note' => 'Billed by total floor area in square meters.',
            'recommended_duration_minutes' => 240,
            'features' => [
                'Whole-property cleaning for turnover',
                'Cabinet, fixture, and wall-surface attention',
                'Move-ready or handover-ready presentation',
            ],
        ],
        'postconstruction' => [
            'name' => 'Post Construction Cleaning',
            'badge' => 'Specialty Package',
            'icon' => 'fa-hard-hat',
            'summary' => 'Detailed cleanup after renovation, repairs, or newly finished construction work.',
            'highlight' => 'Best for newly turned-over or recently renovated spaces.',
            'default_description' => 'Deep post-construction cleanup package that removes dust residue, debris traces, and renovation buildup across key living areas.',
            'recommended_price' => 105.0,
            'pricing_unit' => 'sqm',
            'recommended_duration_minutes' => 240,
            'features' => [
                'Removal of fine construction dust and debris traces',
                'Detailed wipe-down of fixtures, ledges, and surfaces',
                'Focused cleanup for recently renovated rooms',
            ],
        ],
        'commercial' => [
            'name' => 'Office and Commercial Cleaning',
            'badge' => 'Business Package',
            'icon' => 'fa-building',
            'summary' => 'Structured cleaning for offices, storefronts, and other business-ready workspaces.',
            'highlight' => 'Ideal for customer-facing spaces and team operations.',
            'default_description' => 'Commercial cleaning package for offices and business spaces, including reception areas, work zones, and common facilities.',
            'recommended_price' => 1600.0,
            'recommended_duration_minutes' => 180,
            'features' => [
                'Reception and workstation cleaning routines',
                'Restroom and pantry area sanitation',
                'Business-hours friendly cleaning workflow',
            ],
        ],
        'weeklymaintenance' => [
            'name' => 'General/Regular Cleaning',
            'badge' => 'Regular Cleaning',
            'icon' => 'fa-calendar-check',
            'summary' => 'General recurring or one-time cleaning for routine home upkeep.',
            'highlight' => 'Per-session cleaning for standard upkeep, up to 4 hours.',
            'default_description' => 'General or regular cleaning session for routine upkeep, covering common household cleaning tasks within a standard session of up to 4 hours.',
            'recommended_price' => 500.0,
            'pricing_unit' => 'flat_range',
            'price_range' => [
                'min' => 500.0,
                'max' => 800.0,
            ],
            'pricing_note' => 'Per session, up to 4 hours.',
            'recommended_duration_minutes' => 240,
            'features' => [
                'General dusting, sweeping, and mopping',
                'Routine kitchen and bathroom refresh',
                'Up to 4 hours per standard session',
            ],
        ],
    ];

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
            'post construction cleaning',
            'post-construction cleaning',
            'post construction clean' => 'postconstruction',
            'office and commercial cleaning',
            'commercial cleaning',
            'office cleaning' => 'commercial',
            'general/regular cleaning',
            'general regular cleaning',
            'general cleaning',
            'regular cleaning',
            'weekly maintenance plan',
            'weekly maintenance cleaning',
            'weekly cleaning plan' => 'weeklymaintenance',
            default => Str::slug($name),
        };
    }

    public static function displayNameForSlug(?string $slug): string
    {
        return match (self::catalogSlug($slug)) {
            'basic' => 'Basic Clean',
            'deep' => 'Deep Clean',
            'moveinout' => 'Move-in/Move-out Clean',
            'postconstruction' => 'Post Construction Cleaning',
            'commercial' => 'Office and Commercial Cleaning',
            'weeklymaintenance' => 'General/Regular Cleaning',
            null, '' => 'Unknown Service',
            default => Str::of($slug)->replace(['-', '_'], ' ')->title()->value(),
        };
    }

    public static function packageCatalog(): array
    {
        return self::PACKAGE_CATALOG;
    }

    public static function packageMetadataFor(?string $slug): ?array
    {
        if (! $slug) {
            return null;
        }

        $catalogSlug = self::catalogSlug($slug);
        $metadata = self::PACKAGE_CATALOG[$catalogSlug] ?? null;

        if (! $metadata) {
            return null;
        }

        return array_merge($metadata, [
            'slug' => $slug,
            'name' => $metadata['name'] ?? self::displayNameForSlug($catalogSlug),
        ]);
    }

    public static function usesPerSquareMeterPricing(?string $slug): bool
    {
        return (self::PACKAGE_CATALOG[self::catalogSlug($slug)]['pricing_unit'] ?? null) === 'sqm';
    }

    public static function usesFlatRateRangePricing(?string $slug): bool
    {
        return (self::PACKAGE_CATALOG[self::catalogSlug($slug)]['pricing_unit'] ?? null) === 'flat_range';
    }

    public static function priceRangeForSlug(?string $slug): ?array
    {
        $range = self::PACKAGE_CATALOG[self::catalogSlug($slug)]['price_range'] ?? null;

        if (! is_array($range) || ! isset($range['min'], $range['max'])) {
            return null;
        }

        return [
            'min' => (float) $range['min'],
            'max' => (float) $range['max'],
        ];
    }

    public static function durationForSlug(?string $slug): int
    {
        if (! $slug) {
            return self::DEFAULT_DURATION_MINUTES;
        }

        return (int) (self::PACKAGE_CATALOG[self::catalogSlug($slug)]['recommended_duration_minutes'] ?? self::DEFAULT_DURATION_MINUTES);
    }

    public static function catalogSlug(?string $slug): ?string
    {
        return match ($slug) {
            'basic-clean' => 'basic',
            default => $slug,
        };
    }
}
