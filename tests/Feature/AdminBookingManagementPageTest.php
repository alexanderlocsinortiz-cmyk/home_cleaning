<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBookingManagementPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bookings_page_defaults_to_active_operational_queue(): void
    {
        $admin = $this->createUser('admin', 'admin-bookings@example.com', 'adminbookings');
        $client = $this->createUser('client', 'client-bookings@example.com', 'clientbookings');
        $staff = $this->createUser('staff', 'staff-bookings@example.com', 'staffbookings');

        $pending = $this->createBooking($client, null, 'pending', now()->addDay()->toDateString(), '09:00');
        $confirmed = $this->createBooking($client, $staff, 'confirmed', now()->addDays(2)->toDateString(), '10:00');
        $inProgress = $this->createBooking($client, $staff, 'in_progress', now()->toDateString(), '08:00');
        $completed = $this->createBooking($client, $staff, 'completed', now()->subDay()->toDateString(), '11:00');

        $response = $this->actingAs($admin)->get(route('admin.bookings'));

        $response->assertOk();
        $response->assertSee('Active Booking Queue');
        $response->assertSee('Completed Bookings');
        $response->assertSee('CF-' . str_pad($pending->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('CF-' . str_pad($confirmed->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('CF-' . str_pad($inProgress->id, 5, '0', STR_PAD_LEFT));
        $response->assertDontSee('CF-' . str_pad($completed->id, 5, '0', STR_PAD_LEFT));
    }

    public function test_admin_can_open_completed_history_tab_without_operational_controls(): void
    {
        $admin = $this->createUser('admin', 'admin-history@example.com', 'adminhistory');
        $client = $this->createUser('client', 'client-history@example.com', 'clienthistory');
        $staff = $this->createUser('staff', 'staff-history@example.com', 'staffhistory');

        $completed = $this->createBooking($client, $staff, 'completed', now()->subDays(2)->toDateString(), '13:00');
        $cancelled = $this->createBooking($client, null, 'cancelled', now()->subDays(3)->toDateString(), '14:00');

        $response = $this->actingAs($admin)->get(route('admin.bookings', ['tab' => 'completed']));

        $response->assertOk();
        $response->assertSee('Completed Booking History');
        $response->assertSee('CF-' . str_pad($completed->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('CF-' . str_pad($cancelled->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee('View Details');
        $response->assertSee('No rating yet');
        $response->assertDontSee('Current staff:');
        $response->assertDontSee('Active Booking Queue');
    }

    private function createUser(string $role, string $email, string $username): User
    {
        $user = User::create([
            'first_name' => ucfirst($role),
            'last_name' => 'User',
            'email' => $email,
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => $username,
            'role' => $role,
            'password' => Hash::make('password123'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    private function createBooking(User $client, ?User $staff, string $status, string $scheduledDate, string $scheduledTime): Booking
    {
        $booking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => $scheduledDate,
            'scheduled_time' => $scheduledTime,
            'price' => 1200,
            'status' => $status,
            'staff_id' => $staff?->id,
        ]);

        if (in_array($status, ['completed', 'cancelled'], true)) {
            $booking->forceFill(['updated_at' => now()->subHour()])->save();
        }

        return $booking->fresh();
    }
}
