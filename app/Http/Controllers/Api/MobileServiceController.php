<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculatePriceRequest;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class MobileServiceController extends Controller
{
    public function index(): JsonResponse
    {
        $services = Service::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->map(fn (Service $service) => $this->servicePayload($service))
            ->values();

        return response()->json([
            'services' => $services,
            'barangays' => array_keys(config('cleanflow.barangays', [])),
            'property_types' => collect(Booking::propertyTypeLabels())
                ->map(fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => $label,
                ])
                ->values(),
            'add_ons' => collect(Booking::addOnCatalog())
                ->map(fn (array $addOn, string $key) => [
                    'key' => $key,
                    'label' => $addOn['label'],
                    'price' => (float) $addOn['price'],
                    'description' => $addOn['description'] ?? '',
                    'pricing_unit' => $addOn['pricing_unit'] ?? Booking::ADD_ON_PRICING_UNIT,
                    'quantity_supported' => ($addOn['pricing_unit'] ?? Booking::ADD_ON_PRICING_UNIT) !== Booking::ADD_ON_PRICING_UNIT,
                ])
                ->values(),
            'payment_methods' => collect(Booking::paymentMethods())
                ->map(fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => $label,
                    'is_digital' => Booking::isDigitalPaymentMethod($key),
                ])
                ->values(),
            'service_plans' => collect(Booking::servicePlans())
                ->map(fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => $label,
                ])
                ->values(),
            'subscription_frequencies' => collect(Booking::subscriptionFrequencyLabels())
                ->map(fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => $label,
                ])
                ->values(),
            'pricing' => Booking::pricingConfiguration(),
        ]);
    }

    public function calculate(CalculatePriceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $pricing = Booking::calculatePrice(
            $validated['service_type'],
            $validated['property_type'],
            $validated['rooms'] ?? 1,
            $validated['bathrooms'] ?? 1,
            $validated['floor_area'],
            $validated['add_ons'] ?? [],
            $validated['add_on_quantities'] ?? []
        );

        return response()->json([
            'pricing' => $pricing,
            'formatted_total' => 'P'.number_format((float) $pricing['total'], 2),
        ]);
    }

    private function servicePayload(Service $service): array
    {
        $metadata = Service::packageMetadataFor($service->slug) ?? [];
        $priceRange = Service::priceRangeForSlug($service->slug);

        return [
            'id' => $service->id,
            'slug' => $service->slug,
            'name' => $service->name,
            'description' => $service->description,
            'image_url' => $service->image_url,
            'image_alt' => $service->image_alt,
            'price' => (float) $service->price,
            'duration_minutes' => (int) ($service->duration_minutes ?: Service::durationForSlug($service->slug)),
            'badge' => $metadata['badge'] ?? null,
            'icon' => $metadata['icon'] ?? null,
            'summary' => $metadata['summary'] ?? $service->description,
            'highlight' => $metadata['highlight'] ?? null,
            'features' => $metadata['features'] ?? [],
            'pricing_unit' => $metadata['pricing_unit'] ?? 'fixed',
            'price_range' => $priceRange,
            'uses_per_square_meter_pricing' => Service::usesPerSquareMeterPricing($service->slug),
            'uses_flat_rate_range_pricing' => Service::usesFlatRateRangePricing($service->slug),
            'scope' => $service->scopeSummary(),
            'scope_definition' => $service->scopeDefinition(),
        ];
    }
}
