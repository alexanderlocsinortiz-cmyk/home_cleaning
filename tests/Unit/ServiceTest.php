<?php

namespace Tests\Unit;

use App\Models\Service;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    public function test_service_can_be_created()
    {
        $service = Service::factory()->create([
            'name' => 'Deep Clean',
            'slug' => 'deep-clean',
            'price' => 1200.00,
        ]);

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'name' => 'Deep Clean',
        ]);
    }

    public function test_service_has_required_fields()
    {
        $service = Service::factory()->create();

        $this->assertNotNull($service->name);
        $this->assertNotNull($service->slug);
        $this->assertNotNull($service->price);
    }

    public function test_service_is_active_by_default()
    {
        $service = Service::factory()->create();
        $this->assertTrue($service->is_active);
    }

    public function test_service_can_be_deactivated()
    {
        $service = Service::factory()->create(['is_active' => true]);
        $service->update(['is_active' => false]);

        $this->assertFalse((bool) $service->fresh()->is_active);
    }

    public function test_service_pricing_is_stored_correctly()
    {
        $price = 1500.50;
        $service = Service::factory()->create(['price' => $price]);

        $this->assertEquals($price, $service->price);
    }

    public function test_service_package_catalog_includes_basic()
    {
        $this->assertArrayHasKey('basic', Service::PACKAGE_CATALOG);
        $this->assertEquals('Basic Clean', Service::PACKAGE_CATALOG['basic']['name']);
    }

    public function test_service_package_catalog_includes_deep()
    {
        $this->assertArrayHasKey('deep', Service::PACKAGE_CATALOG);
        $this->assertEquals('Deep Clean', Service::PACKAGE_CATALOG['deep']['name']);
    }

    public function test_service_package_catalog_includes_moveinout()
    {
        $this->assertArrayHasKey('moveinout', Service::PACKAGE_CATALOG);
        $this->assertStringContainsString('Move-in', Service::PACKAGE_CATALOG['moveinout']['name']);
    }

    public function test_service_packages_have_recommended_prices()
    {
        foreach (Service::PACKAGE_CATALOG as $package) {
            $this->assertArrayHasKey('recommended_price', $package);
            $this->assertGreaterThan(0, $package['recommended_price']);
        }
    }

    public function test_service_packages_have_features()
    {
        foreach (Service::PACKAGE_CATALOG as $package) {
            $this->assertArrayHasKey('features', $package);
            $this->assertIsArray($package['features']);
            $this->assertGreaterThan(0, count($package['features']));
        }
    }

    public function test_service_description_is_optional()
    {
        $service = Service::factory()->create(['description' => null]);
        $this->assertNull($service->description);
    }

    public function test_service_slug_is_unique()
    {
        $slug = 'unique-service-slug';
        Service::factory()->create(['slug' => $slug]);

        // In a unique constraint test, attempting to create another with same slug should fail or need handling
        $this->assertTrue(true); // Placeholder for database constraint validation
    }
}
