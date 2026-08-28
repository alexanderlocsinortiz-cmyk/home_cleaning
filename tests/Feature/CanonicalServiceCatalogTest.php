<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanonicalServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_canonical_service_package_is_persisted_with_matching_price_and_duration(): void
    {
        foreach (Service::packageCatalog() as $slug => $package) {
            $service = Service::where('slug', $slug)->first();

            $this->assertNotNull($service, "Missing canonical service: {$slug}");
            $this->assertTrue((bool) $service->is_active, "Inactive canonical service: {$slug}");
            $this->assertSame((float) $package['recommended_price'], (float) $service->price, "Price drift for {$slug}");
            $this->assertSame((int) $package['recommended_duration_minutes'], (int) $service->duration_minutes, "Duration drift for {$slug}");
        }
    }

    public function test_every_canonical_service_has_a_complete_scope_definition(): void
    {
        foreach (Service::packageCatalog() as $slug => $package) {
            $service = Service::where('slug', $slug)->firstOrFail();

            $this->assertTrue(
                $service->scopeDefinitionIsComplete(),
                "Incomplete scope definition for {$slug}"
            );
        }
    }

    public function test_every_canonical_add_on_is_persisted_with_matching_price(): void
    {
        foreach (Booking::ADD_ON_CATALOG as $key => $addOn) {
            $storedAddOn = Booking::addOnCatalog(false)[$key] ?? null;

            $this->assertNotNull($storedAddOn, "Missing canonical add-on: {$key}");
            $this->assertSame((float) $addOn['price'], (float) $storedAddOn['price'], "Price drift for add-on {$key}");
        }
    }

    public function test_per_square_meter_quote_uses_the_persisted_service_rate(): void
    {
        Service::where('slug', 'deep')->update(['price' => 50]);

        $pricing = Booking::calculatePrice('deep', 'house', 1, 1, 40, []);

        $this->assertSame(50.0, $pricing['floor_area_rate']);
        $this->assertSame(2000.0, $pricing['floor_area_fee']);
        $this->assertSame(2000.0, $pricing['total']);
    }
}
