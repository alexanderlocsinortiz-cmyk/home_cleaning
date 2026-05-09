<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingActivityLog;
use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLogsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_booking_activity_logs(): void
    {
        $admin = $this->createUser('admin', 'admin-logs@example.com', 'adminlogs');
        $client = $this->createUser('client', 'client-logs@example.com', 'clientlogs');
        $booking = $this->createBooking($client);

        BookingActivityLog::create([
            'booking_id' => $booking->id,
            'actor_id' => $admin->id,
            'actor_role' => 'admin',
            'actor_name' => $admin->display_name,
            'action' => 'status_updated',
            'description' => 'Status changed from pending to confirmed.',
            'metadata' => [
                'from_status' => 'pending',
                'to_status' => 'confirmed',
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.logs'));

        $response->assertOk();
        $response->assertSee('Booking Activity Logs');
        $response->assertSee('Status changed from pending to confirmed.');
        $response->assertSee('Status updated');
        $response->assertSee('CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT));
        $response->assertSee($admin->display_name);
    }

    public function test_admin_can_view_attendance_logs_from_logs_page(): void
    {
        $admin = $this->createUser('admin', 'admin-attendance-logs@example.com', 'adminattendancelogs');
        $staff = $this->createUser('staff', 'staff-attendance-logs@example.com', 'staffattendancelogs');

        AttendanceLog::create([
            'user_id' => $staff->id,
            'punch_type' => 'in',
            'logged_at' => now(),
            'status' => 'late',
            'source' => 'device',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.logs', ['source' => 'attendance']));

        $response->assertOk();
        $response->assertSee('Attendance Logs');
        $response->assertSee('Time In');
        $response->assertSee('Late');
        $response->assertSee($staff->display_name);
    }

    public function test_admin_logs_page_can_filter_by_action(): void
    {
        $admin = $this->createUser('admin', 'admin-logs-filter@example.com', 'adminlogsfilter');
        $client = $this->createUser('client', 'client-logs-filter@example.com', 'clientlogsfilter');
        $booking = $this->createBooking($client);

        BookingActivityLog::create([
            'booking_id' => $booking->id,
            'actor_id' => $admin->id,
            'actor_role' => 'admin',
            'actor_name' => $admin->display_name,
            'action' => 'status_updated',
            'description' => 'Status changed from pending to confirmed.',
        ]);

        BookingActivityLog::create([
            'booking_id' => $booking->id,
            'actor_id' => $admin->id,
            'actor_role' => 'admin',
            'actor_name' => $admin->display_name,
            'action' => 'payment_updated',
            'description' => 'Payment status changed to paid.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.logs', ['action' => 'payment_updated']));

        $response->assertOk();
        $response->assertSee('Payment status changed to paid.');
        $response->assertDontSee('Status changed from pending to confirmed.');
    }

    public function test_client_cannot_view_admin_logs(): void
    {
        $client = $this->createUser('client', 'client-no-admin-logs@example.com', 'clientnoadminlogs');

        $response = $this->actingAs($client)->get(route('admin.logs'));

        $response->assertForbidden();
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

    private function createBooking(User $client): Booking
    {
        return Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1200,
            'status' => 'pending',
        ]);
    }
}
