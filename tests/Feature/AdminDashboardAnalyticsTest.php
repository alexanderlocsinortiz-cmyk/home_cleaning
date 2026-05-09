<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Rating;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_focused_operational_sections(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $admin = $this->createUser([
            'email' => 'admin-dashboard-analytics@example.com',
            'username' => 'admindashboardanalytics',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-dashboard-analytics@example.com',
            'username' => 'clientdashboardanalytics',
            'role' => 'client',
        ]);
        $staff = $this->createUser([
            'email' => 'staff-dashboard-analytics@example.com',
            'username' => 'staffdashboardanalytics',
            'role' => 'staff',
            'first_name' => 'Peak',
            'last_name' => 'Cleaner',
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
            'updated_at' => now()->subDay(),
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

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Today: 2 bookings | 0 pending | 0 in progress | 1 staff');
        $response->assertSee('Active Queue - Recent Bookings');
        $response->assertSee('Quick Actions');
        $response->assertSee('Today Snapshot');
        $response->assertSee('Staff Trends');
        $response->assertSee('Completed');
        $response->assertSee($staff->full_name);
        $response->assertDontSee('Booking Trend Snapshot');
        $response->assertDontSee('Customer Satisfaction Trends');
        $response->assertDontSee('Peak Booking Demand');
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
