<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileServiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_services_is_public(): void
    {
        $this->getJson('/api/mobile/services')
            ->assertOk()
            ->assertJsonStructure(['services', 'barangays', 'property_types', 'add_ons', 'payment_methods']);
    }

    public function test_mobile_user_can_fetch_service_catalog(): void
    {
        Service::updateOrCreate(['slug' => 'basic'], [
            'name' => 'Basic Clean',
            'price' => 35,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
        Service::factory()->create([
            'name' => 'Inactive Service',
            'slug' => 'inactive-service',
            'is_active' => false,
        ]);

        $token = $this->mobileToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/services')
            ->assertOk()
            ->assertJsonFragment([
                'slug' => 'basic',
                'uses_per_square_meter_pricing' => true,
            ])
            ->assertJsonMissing([
                'slug' => 'inactive-service',
            ])
            ->assertJsonStructure([
                'services' => [[
                    'scope_definition' => [
                        'included_areas',
                        'included_tasks',
                        'excluded_tasks',
                        'condition_limits',
                        'equipment_policy',
                        'access_limits',
                        'extra_work_policy',
                        'acceptance_criteria',
                    ],
                ]],
                'property_types',
                'add_ons',
                'payment_methods',
                'service_plans',
                'subscription_frequencies',
                'pricing',
            ]);

        $response->assertJsonPath('add_ons.0.pricing_unit', 'per booking');
    }

    public function test_mobile_user_can_calculate_price_from_backend(): void
    {
        Service::updateOrCreate(['slug' => 'deep'], [
            'name' => 'Deep Clean',
            'price' => 95,
            'is_active' => true,
        ]);

        $token = $this->mobileToken();

        $this->postJson('/api/mobile/calculate-price', [
            'service_type' => 'deep',
            'property_type' => 'house',
            'floor_area' => 30,
            'add_ons' => [],
        ])
            ->assertOk()
            ->assertJsonPath('pricing.floor_area_fee', 2850)
            ->assertJsonPath('pricing.total', 2850)
            ->assertJsonPath('formatted_total', 'P2,850.00');
    }

    public function test_mobile_price_format_keeps_centavos(): void
    {
        Service::updateOrCreate(['slug' => 'deep'], [
            'name' => 'Deep Clean',
            'price' => 95.25,
            'is_active' => true,
        ]);

        $token = $this->mobileToken();

        $this->postJson('/api/mobile/calculate-price', [
            'service_type' => 'deep',
            'property_type' => 'house',
            'floor_area' => 30,
            'add_ons' => [],
        ])
            ->assertOk()
            ->assertJsonPath('pricing.total', 2857.5)
            ->assertJsonPath('formatted_total', 'P2,857.50');
    }

    public function test_mobile_price_calculation_rejects_incompatible_service_and_property_type(): void
    {
        Service::updateOrCreate(['slug' => 'deep'], [
            'name' => 'Deep Clean',
            'price' => 95,
            'is_active' => true,
        ]);

        $this->mobileToken();

        $this->postJson('/api/mobile/calculate-price', [
            'service_type' => 'deep',
            'property_type' => 'office',
            'floor_area' => 30,
            'add_ons' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('service_type');
    }

    private function mobileToken(): string
    {
        User::factory()->create([
            'email' => 'mobile-services@example.com',
            'password' => Hash::make('Password123'),
        ]);

        return $this->postJson('/api/mobile/login', [
            'email' => 'mobile-services@example.com',
            'password' => 'Password123',
        ])->json('token');
    }
}
