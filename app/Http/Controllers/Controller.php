<?php

namespace App\Http\Controllers;

use App\Models\CleanerApplication;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

abstract class Controller
{
    /**
     * Create a notification and invalidate related caches
     */
    public function createNotification(array $data)
    {
        $notification = Notification::create($data);

        // ✅ Invalidate user's notification count cache
        Cache::forget('staff:unread_notif_'.$data['user_id']);

        return $notification;
    }

    /**
     * Build service area stats shared across client and staff portals
     */
    protected function serviceAreaStats(): array
    {
        $barangays = config('cleanflow.service_areas', []);

        $avg = Rating::avg('stars');

        return [
            'barangays' => count($barangays),
            'coverage_areas' => count(config('cleanflow.bukidnon_service_areas', [])),
            'customers' => User::where('role', 'client')->count(),
            'staff' => User::where('role', 'staff')->count(),
            'satisfaction' => $avg ? round(($avg / 5) * 100) : 98,
        ];
    }

    /**
     * Build public-safe provider coverage markers for the service-area maps.
     *
     * Provider exact base coordinates stay private. The map only places a
     * marker at the selected city/municipality center and shows providers who
     * are approved and have activated their provider account.
     */
    protected function providerCoverageMapPoints(): array
    {
        $providers = CleanerApplication::query()
            ->where('status', CleanerApplication::STATUS_APPROVED)
            ->whereNotNull('activated_at')
            ->get([
                'id',
                'business_name',
                'applicant_type',
                'services_offered',
                'coverage_barangays',
                'service_area',
                'availability_status',
            ]);

        return collect(config('cleanflow.bukidnon_service_areas', []))
            ->map(function (array $area) use ($providers): ?array {
                $areaProviders = $providers
                    ->filter(fn (CleanerApplication $provider): bool => $provider->coversBarangay($area['name']))
                    ->values();

                if ($areaProviders->isEmpty()) {
                    return null;
                }

                return [
                    'name' => $area['name'],
                    'lat' => (float) $area['lat'],
                    'lng' => (float) $area['lng'],
                    'provider_count' => $areaProviders->count(),
                    'available_provider_count' => $areaProviders
                        ->filter(fn (CleanerApplication $provider): bool => $provider->isAvailableForAssignment())
                        ->count(),
                    'providers' => $areaProviders->map(function (CleanerApplication $provider): array {
                        $services = collect(preg_split('/[,;|]+/', (string) $provider->services_offered) ?: [])
                            ->map(fn (string $service): string => trim($service))
                            ->filter()
                            ->values()
                            ->all();

                        return [
                            'name' => $provider->business_name,
                            'type' => $provider->applicant_type,
                            'services' => $services,
                            'availability' => $provider->availabilityLabel(),
                        ];
                    })->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
