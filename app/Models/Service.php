<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'slug', 'description', 'price', 'is_active'];

    public const PACKAGE_CATALOG = [
        'basic' => [
            'name' => 'Basic Clean',
            'badge' => 'Signature Package',
            'icon' => 'fa-broom',
            'summary' => 'Routine cleaning for regularly maintained homes.',
            'highlight' => 'Best for weekly or bi-weekly upkeep.',
            'default_description' => 'Routine cleaning package for regularly maintained spaces, including general dusting, sweeping, mopping, and bathroom refresh work.',
            'recommended_price' => 570.0,
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
            'recommended_price' => 1200.0,
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
            'recommended_price' => 2000.0,
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
            'recommended_price' => 1800.0,
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
            'features' => [
                'Reception and workstation cleaning routines',
                'Restroom and pantry area sanitation',
                'Business-hours friendly cleaning workflow',
            ],
        ],
        'weeklymaintenance' => [
            'name' => 'Weekly Maintenance Plan',
            'badge' => 'Maintenance Plan',
            'icon' => 'fa-calendar-check',
            'summary' => 'Recurring weekly upkeep to keep homes or offices clean and ready all month.',
            'highlight' => 'Designed for predictable recurring maintenance.',
            'default_description' => 'Recurring weekly maintenance package that keeps spaces consistently clean through scheduled repeat visits.',
            'recommended_price' => 900.0,
            'features' => [
                'Weekly scheduled maintenance visits',
                'Routine high-touch and floor-care checklist',
                'Cleaner continuity through recurring plans',
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
            'weekly maintenance plan',
            'weekly maintenance cleaning',
            'weekly cleaning plan' => 'weeklymaintenance',
            default => Str::slug($name),
        };
    }

    public static function displayNameForSlug(?string $slug): string
    {
        return match ($slug) {
            'basic' => 'Basic Clean',
            'deep' => 'Deep Clean',
            'moveinout' => 'Move-in/Move-out Clean',
            'postconstruction' => 'Post Construction Cleaning',
            'commercial' => 'Office and Commercial Cleaning',
            'weeklymaintenance' => 'Weekly Maintenance Plan',
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

        $metadata = self::PACKAGE_CATALOG[$slug] ?? null;

        if (! $metadata) {
            return null;
        }

        return array_merge($metadata, [
            'slug' => $slug,
            'name' => $metadata['name'] ?? self::displayNameForSlug($slug),
        ]);
    }
}
