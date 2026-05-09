<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Rating;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAnalyticsPageTest extends TestCase
{
    public function test_admin_analytics_dashboard_renders_current_metrics(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $admin = $this->createUser([
            'email' => 'admin-analytics-page@example.com',
            'username' => 'adminanalyticspage',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-analytics-page@example.com',
            'username' => 'clientanalyticspage',
            'role' => 'client',
        ]);
        $staff = $this->createUser([
            'email' => 'staff-analytics-page@example.com',
            'username' => 'staffanalyticspage',
            'role' => 'staff',
            'first_name' => 'Range',
            'last_name' => 'Leader',
        ]);

        $completedBooking = Booking::create([
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
            'payment_status' => 'paid',
            'status' => 'completed',
            'staff_id' => $staff->id,
        ]);
        $completedBooking->forceFill([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDay(),
        ])->save();

        $pendingBooking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 1,
            'bathrooms' => 1,
            'floor_area' => 28,
            'barangay' => 'Poblacion',
            'street_address' => '456 Mabini Street',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '10:00',
            'price' => 770,
            'status' => 'pending',
        ]);
        $pendingBooking->forceFill([
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->save();

        Rating::create([
            'booking_id' => $completedBooking->id,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'stars' => 5,
            'comment' => 'Excellent service.',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.analytics', ['date_range' => 30]));

        $response->assertOk();
        $response->assertSee('Business Analytics');
        $response->assertSee('Revenue and Booking Status');
        $response->assertSee('Service Popularity');
        $response->assertSee('Daily Trends');
        $response->assertSee('Basic Clean');
        $response->assertSee($staff->full_name);
        $response->assertViewHas('bookingMetrics', function (array $bookingMetrics) {
            return $bookingMetrics['total'] === 2
                && $bookingMetrics['completed'] === 1
                && $bookingMetrics['pending'] === 1;
        });
        $response->assertViewHas('revenueMetrics', function (array $revenueMetrics) {
            return $revenueMetrics['total_revenue'] === 1180.0
                && $revenueMetrics['paid_bookings'] === 1
                && $revenueMetrics['pending_payments'] === 0;
        });
        $response->assertViewHas('servicePopularity', function (Collection $servicePopularity) {
            return $servicePopularity->count() === 1
                && $servicePopularity->first()['name'] === 'Basic Clean'
                && $servicePopularity->first()['bookings'] === 2;
        });
        $response->assertViewHas('staffPerformance', function (Collection $staffPerformance) use ($staff) {
            return $staffPerformance->count() === 1
                && $staffPerformance->first()['name'] === $staff->full_name;
        });
    }

    public function test_admin_analytics_export_returns_csv(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $admin = $this->createUser([
            'email' => 'admin-analytics-export@example.com',
            'username' => 'adminanalyticsexport',
            'role' => 'admin',
        ]);
        $client = $this->createUser([
            'email' => 'client-analytics-export@example.com',
            'username' => 'clientanalyticsexport',
            'role' => 'client',
        ]);

        $booking = Booking::create([
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
        ]);
        $booking->forceFill([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDay(),
        ])->save();

        $response = $this->actingAs($admin)->get(route('admin.analytics.export', ['date_range' => 30]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $response->assertSeeText('Booking ID');
        $response->assertSeeText($client->email);
        $response->assertSeeText('Basic Clean');
        $response->assertSeeText('1180.00');
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
