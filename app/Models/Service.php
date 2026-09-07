<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Service extends Model
{
    public const OFFICE_SERVICE_SLUGS = ['office-basic', 'commercial', 'office-deep'];

    public static function supportsPropertyType(string $serviceSlug, string $propertyType): bool
    {
        $isOfficeService = in_array($serviceSlug, self::OFFICE_SERVICE_SLUGS, true);

        return $propertyType === 'office' ? $isOfficeService : ! $isOfficeService;
    }

    use HasFactory;

    public const DEFAULT_DURATION_MINUTES = 60;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'price',
        'duration_minutes',
        'sort_order',
        'scope_max_floor_area',
        'scope_cleaner_count',
        'scope_status',
        'scope_manual_review_above_limit',
        'scope_included_areas',
        'scope_included_tasks',
        'scope_excluded_tasks',
        'scope_condition_limits',
        'scope_equipment_policy',
        'scope_access_limits',
        'scope_extra_work_policy',
        'scope_acceptance_criteria',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'duration_minutes' => 'integer',
            'sort_order' => 'integer',
            'scope_max_floor_area' => 'integer',
            'scope_cleaner_count' => 'integer',
            'scope_manual_review_above_limit' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public const SCOPE_STATUSES = [
        'provisional',
        'approved',
    ];

    public function scopeIsApproved(): bool
    {
        return $this->scope_status === 'approved' && $this->scopeApprovalIsComplete();
    }

    public function scopeSummary(): array
    {
        return [
            'max_floor_area' => $this->scope_max_floor_area,
            'cleaner_count' => (int) ($this->scope_cleaner_count ?: 1),
            'capacity_sqm_per_cleaner' => self::cleanerCapacityForSlug($this->slug),
            'base_duration_minutes' => (int) ($this->duration_minutes ?: self::durationForSlug($this->slug)),
            'status' => $this->scopeIsApproved() ? 'approved' : 'provisional',
            'manual_review_above_limit' => (bool) $this->scope_manual_review_above_limit,
        ];
    }

    public static function cleanerCapacityForSlug(?string $slug): int
    {
        $catalogSlug = self::catalogSlug($slug);
        $configuredCapacity = config('cleanflow.staffing.capacity_sqm_per_cleaner', []);

        return max(1, (int) ($configuredCapacity[$catalogSlug] ?? config('cleanflow.staffing.default_capacity_sqm_per_cleaner', 40)));
    }

    public static function requiredCleanerCountForSlug(?string $slug, ?int $floorArea): int
    {
        $floorArea = max(0, (int) $floorArea);

        if ($floorArea === 0) {
            return 1;
        }

        return (int) ceil($floorArea / self::cleanerCapacityForSlug($slug));
    }

    public function getImageUrlAttribute(): string
    {
        if (filled($this->image_path)) {
            return Storage::disk(config('filesystems.public_uploads_disk'))->url($this->image_path);
        }

        return asset(self::defaultImagePathForSlug($this->slug));
    }

    public function getImageAltAttribute(): string
    {
        return $this->name.' service';
    }

    public static function defaultImagePathForSlug(?string $slug): string
    {
        $catalogSlug = self::catalogSlug($slug);
        $catalogSlug = $catalogSlug && array_key_exists($catalogSlug, self::PACKAGE_CATALOG) ? $catalogSlug : 'custom';

        $defaultImages = [
            'basic' => 'basic.jpg',
            'deep' => 'deep.jpg',
            'moveinout' => 'moveinout.jpg',
            'postconstruction' => 'postconstruction.jpg',
            'commercial' => 'commercial.jpg',
            'office-basic' => 'office-basic.jpg',
            'office-deep' => 'office-deep.jpg',
            'weeklymaintenance' => 'weeklymaintenance.jpg',
            'custom' => 'custom.jpg',
        ];

        return 'images/services/optimized/'.($defaultImages[$catalogSlug] ?? 'custom.jpg');
    }

    public function scopeDefinition(): array
    {
        return [
            'included_areas' => $this->scope_included_areas,
            'included_tasks' => $this->scope_included_tasks,
            'excluded_tasks' => $this->scope_excluded_tasks,
            'condition_limits' => $this->scope_condition_limits,
            'equipment_policy' => $this->scope_equipment_policy,
            'access_limits' => $this->scope_access_limits,
            'extra_work_policy' => $this->scope_extra_work_policy,
            'acceptance_criteria' => $this->scope_acceptance_criteria,
        ];
    }

    public function scopeDefinitionIsComplete(): bool
    {
        return collect($this->scopeDefinition())->every(fn ($value) => filled($value));
    }

    public function scopeApprovalIsComplete(): bool
    {
        return $this->scopeDefinitionIsComplete()
            && $this->scope_max_floor_area !== null
            && (int) $this->scope_max_floor_area >= 10
            && (int) $this->scope_max_floor_area <= 1000
            && $this->scope_cleaner_count !== null
            && (int) $this->scope_cleaner_count >= 1
            && (int) $this->scope_cleaner_count <= 20;
    }

    public function exceedsScopeLimit(?int $floorArea): bool
    {
        return $this->scope_max_floor_area !== null
            && $floorArea !== null
            && $floorArea > (int) $this->scope_max_floor_area;
    }

    public function requiresScopeManualReview(?int $floorArea): bool
    {
        return ! $this->scopeIsApproved()
            && (bool) $this->scope_manual_review_above_limit
            && $this->exceedsScopeLimit($floorArea);
    }

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
            'name' => 'Office Cleaning (Standard)',
            'badge' => 'Business Package',
            'icon' => 'fa-building',
            'summary' => 'Structured cleaning for offices, storefronts, and other business-ready workspaces.',
            'highlight' => 'Ideal for customer-facing spaces and team operations.',
            'default_description' => 'Commercial cleaning package for offices and business spaces, including reception areas, work zones, and common facilities.',
            'recommended_price' => 35.0,
            'pricing_unit' => 'sqm',
            'recommended_duration_minutes' => 180,
            'features' => [
                'Reception and workstation cleaning routines',
                'Restroom and pantry area sanitation',
                'Business-hours friendly cleaning workflow',
            ],
        ],
        'office-basic' => [
            'name' => 'Office Cleaning (Basic)',
            'badge' => 'Office Package',
            'icon' => 'fa-building',
            'summary' => 'Light office cleaning for routine workspace upkeep.',
            'highlight' => 'Best for small offices with regular maintenance.',
            'default_description' => 'Basic office cleaning for floors, visible surfaces, work areas, and common office touchpoints.',
            'recommended_price' => 30.0,
            'pricing_unit' => 'sqm',
            'recommended_duration_minutes' => 120,
            'features' => [
                'Visible surface wiping',
                'Floor sweeping and mopping',
                'Trash collection and light restroom refresh',
            ],
        ],
        'office-deep' => [
            'name' => 'Office Cleaning (Deep)',
            'badge' => 'Office Deep Clean',
            'icon' => 'fa-building-shield',
            'summary' => 'Detailed office cleaning for heavier buildup and high-touch zones.',
            'highlight' => 'Recommended for periodic resets and more demanding office cleaning.',
            'default_description' => 'Deep office cleaning for workstations, floors, restrooms, pantry areas, fixtures, and high-touch surfaces.',
            'recommended_price' => 60.0,
            'pricing_unit' => 'sqm',
            'pricing_note' => 'Default catalog rate: PHP 60 per sqm. Condition-based re-quoting is not currently configured.',
            'recommended_duration_minutes' => 240,
            'features' => [
                'Detailed workstation and high-touch cleaning',
                'Restroom and pantry deep sanitation',
                'Heavier floor and surface detailing',
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
            'office cleaning basic',
            'office cleaning (basic)' => 'office-basic',
            'office cleaning deep',
            'office cleaning (deep)' => 'office-deep',
            'office and commercial cleaning',
            'office cleaning standard',
            'office cleaning (standard)',
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
            'commercial' => 'Office Cleaning (Standard)',
            'office-basic' => 'Office Cleaning (Basic)',
            'office-deep' => 'Office Cleaning (Deep)',
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

    public static function catalogRateForSlug(?string $slug): float
    {
        return (float) (self::PACKAGE_CATALOG[self::catalogSlug($slug)]['recommended_price'] ?? 0.0);
    }

    public static function effectiveRateForSlug(?string $slug): float
    {
        if (! $slug) {
            return 0.0;
        }

        $storedRate = self::where('slug', $slug)->value('price');

        if ($storedRate !== null) {
            return (float) $storedRate;
        }

        $catalogSlug = self::catalogSlug($slug);
        $canonicalStoredRate = $catalogSlug && $catalogSlug !== $slug
            ? self::where('slug', $catalogSlug)->value('price')
            : null;

        return $canonicalStoredRate !== null
            ? (float) $canonicalStoredRate
            : self::catalogRateForSlug($slug);
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

    public static function durationForArea(?string $slug, ?int $floorArea, ?int $baseDurationMinutes = null): int
    {
        $baseDurationMinutes = max(1, (int) ($baseDurationMinutes ?: self::durationForSlug($slug)));

        if (! self::usesPerSquareMeterPricing($slug)) {
            return $baseDurationMinutes;
        }

        $requiredCleaners = self::requiredCleanerCountForSlug($slug, $floorArea);

        return $baseDurationMinutes * max(1, $requiredCleaners);
    }

    public static function catalogSlug(?string $slug): ?string
    {
        return match ($slug) {
            'basic-clean' => 'basic',
            default => $slug,
        };
    }
}
