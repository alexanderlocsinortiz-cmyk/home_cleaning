<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reports_only_use_canonical_services_for_service_analytics(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        Service::create([
            'name' => 'Deep Clean',
            'slug' => 'deep',
            'description' => 'Detailed cleaning',
            'price' => 1200,
            'is_active' => true,
        ]);

        $admin = $this->createUser([
            'email' => 'admin-reports@example.com',
            'username' => 'adminreports',
            'role' => 'admin',
        ]);

        $client = $this->createUser([
            'email' => 'client-reports@example.com',
            'username' => 'clientreports',
            'role' => 'client',
        ]);

        Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 45,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1180,
            'status' => 'completed',
        ]);

        Booking::create([
            'user_id' => $client->id,
            'service_type' => 'deep',
            'property_type' => 'apartment',
            'rooms' => 1,
            'bathrooms' => 1,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '456 Mabini Street',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '10:00',
            'price' => 1200,
            'status' => 'pending',
        ]);

        Booking::create([
            'user_id' => $client->id,
            'service_type' => 'office',
            'property_type' => 'apartment',
            'rooms' => 3,
            'bathrooms' => 2,
            'floor_area' => 80,
            'barangay' => 'Poblacion',
            'street_address' => '789 Quezon Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '11:00',
            'price' => 3000,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports'));

        $response->assertOk();
        $response->assertViewHas('revenueByType', function (Collection $revenueByType) {
            return $revenueByType->count() === 1
                && $revenueByType->pluck('service_type')->all() === ['basic']
                && $revenueByType->pluck('service_name')->all() === ['Basic Clean'];
        });
        $response->assertViewHas('bookingsByType', function (Collection $bookingsByType) {
            $serviceTypes = $bookingsByType->pluck('service_type');

            return $serviceTypes->contains('basic')
                && $serviceTypes->contains('deep')
                && !$serviceTypes->contains('office');
        });
        $response->assertViewHas('invalidServiceBookings', 1);
    }

    private function createUser(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'testuser',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ], $overrides));

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }
}
