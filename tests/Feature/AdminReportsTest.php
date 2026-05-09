<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Rating;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
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
                && ! $serviceTypes->contains('office');
        });
        $response->assertViewHas('invalidServiceBookings', 1);
    }

    public function test_admin_reports_expose_advanced_analytics_and_trend_sections(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $admin = $this->createUser([
            'email' => 'admin-advanced-reports@example.com',
            'username' => 'adminadvancedreports',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-advanced-reports@example.com',
            'username' => 'clientadvancedreports',
            'role' => 'client',
        ]);
        $staff = $this->createUser([
            'email' => 'staff-advanced-reports@example.com',
            'username' => 'staffadvancedreports',
            'role' => 'staff',
            'first_name' => 'Trend',
            'last_name' => 'Leader',
        ]);

        $currentBooking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 35,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1180,
            'status' => 'completed',
            'staff_id' => $staff->id,
        ]);
        $currentBooking->forceFill([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(1),
        ])->save();

        $previousMonthBooking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '456 Mabini Street',
            'scheduled_date' => now()->subMonth()->addDays(2)->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 960,
            'status' => 'completed',
            'staff_id' => $staff->id,
        ]);
        $previousMonthBooking->forceFill([
            'created_at' => now()->subMonth()->addDays(1),
            'updated_at' => now()->subMonth()->addDays(2),
        ])->save();

        Rating::create([
            'booking_id' => $currentBooking->id,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'stars' => 5,
            'comment' => 'Excellent service.',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Rating::create([
            'booking_id' => $previousMonthBooking->id,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'stars' => 4,
            'comment' => 'Very good service.',
            'created_at' => now()->subMonth()->addDays(3),
            'updated_at' => now()->subMonth()->addDays(3),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports'));

        $response->assertOk();
        $response->assertSee('Booking Trends');
        $response->assertSee('Customer Satisfaction Trends');
        $response->assertSee('Top Staff Trends');
        $response->assertSee('Busiest Booking Time');
        $response->assertViewHas('analyticsOverview', function (array $analyticsOverview) {
            return array_key_exists('completion_rate', $analyticsOverview)
                && array_key_exists('average_satisfaction', $analyticsOverview)
                && array_key_exists('peak_time_label', $analyticsOverview);
        });
        $response->assertViewHas('monthlyBookingTrend', function (Collection $monthlyBookingTrend) {
            return $monthlyBookingTrend->count() === 6;
        });
        $response->assertViewHas('topStaffLeaders', function (Collection $topStaffLeaders) use ($staff) {
            return $topStaffLeaders->pluck('id')->contains($staff->id);
        });
    }

    public function test_admin_reports_can_filter_summary_by_custom_date_range(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $admin = $this->createUser([
            'email' => 'admin-filtered-reports@example.com',
            'username' => 'adminfilteredreports',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-filtered-reports@example.com',
            'username' => 'clientfilteredreports',
            'role' => 'client',
        ]);

        $included = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1000,
            'status' => 'completed',
        ]);
        $included->forceFill(['created_at' => '2026-05-05 10:00:00'])->save();

        $excluded = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '456 Mabini Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '10:00',
            'price' => 2000,
            'status' => 'completed',
        ]);
        $excluded->forceFill(['created_at' => '2026-04-01 10:00:00'])->save();

        $response = $this->actingAs($admin)->get(route('admin.reports', [
            'period' => 'custom',
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-31',
        ]));

        $response->assertOk();
        $response->assertViewHas('totalBookings', 1);
        $response->assertViewHas('totalRevenue', fn ($totalRevenue) => (float) $totalRevenue === 1000.0);
        $response->assertViewHas('filters', function (array $filters) {
            return $filters['period'] === 'custom'
                && $filters['date_from'] === '2026-05-01'
                && $filters['date_to'] === '2026-05-31';
        });
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
