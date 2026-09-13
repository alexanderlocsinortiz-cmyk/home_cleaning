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

    public function test_mobile_price_calculation_applies_server_add_on_quantities(): void
    {
        Service::updateOrCreate(['slug' => 'basic'], [
            'name' => 'Basic Clean',
            'price' => 35,
            'is_active' => true,
        ]);

        $this->mobileToken();

        $this->postJson('/api/mobile/calculate-price', [
            'service_type' => 'basic',
            'property_type' => 'house',
            'floor_area' => 30,
            'add_ons' => ['mattress_single'],
            'add_on_quantities' => ['mattress_single' => 3],
        ])
            ->assertOk()
            ->assertJsonPath('pricing.add_ons_fee', 2700)
            ->assertJsonPath('pricing.total', 3750)
            ->assertJsonPath('formatted_total', 'P3,750.00');
    }

    public function test_verified_mobile_client_receives_the_website_booking_time_rules(): void
    {
        Service::updateOrCreate(['slug' => 'deep'], [
            'name' => 'Deep Clean',
            'price' => 95,
            'is_active' => true,
        ]);
        $token = $this->mobileToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/booking-availability?service_type=deep&property_type=house&floor_area=30&scheduled_date='.now()->addDay()->toDateString())
            ->assertOk()
            ->assertJsonCount(9, 'time_slots')
            ->assertJsonPath('time_slots.0.time', '08:00')
            ->assertJsonPath('time_slots.0.available', true)
            ->assertJsonPath('time_slots.8.time', '16:00')
            ->assertJsonPath('timezone', 'Asia/Manila');
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
