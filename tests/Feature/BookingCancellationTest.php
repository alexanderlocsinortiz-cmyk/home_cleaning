<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_cancel_pending_booking_without_staff(): void
    {
        $client = $this->createVerifiedClient('client-cancel@example.com', 'clientcancel');
        $booking = $this->createBooking($client, null, 'pending');

        $response = $this->actingAs($client)
            ->from(route('bookings.index'))
            ->patch(route('bookings.cancel', $booking->id));

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success');

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_client_cannot_cancel_booking_with_assigned_staff(): void
    {
        $client = $this->createVerifiedClient('client-cancel-staff@example.com', 'clientcancelstaff');
        $staff = $this->createStaff('staff-cancel@example.com', 'staffcancel');
        $booking = $this->createBooking($client, $staff, 'pending');

        $response = $this->actingAs($client)
            ->from(route('bookings.index'))
            ->patch(route('bookings.cancel', $booking->id));

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('error');

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_client_cannot_cancel_non_pending_booking(): void
    {
        $client = $this->createVerifiedClient('client-cancel-confirmed@example.com', 'clientcancelconfirmed');
        $staff = $this->createStaff('staff-cancel-conf@example.com', 'staffcancelconfirmed');
        $booking = $this->createBooking($client, $staff, 'confirmed');

        $response = $this->actingAs($client)
            ->from(route('bookings.index'))
            ->patch(route('bookings.cancel', $booking->id));

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('error');

        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_client_cannot_cancel_other_clients_booking(): void
    {
        $client = $this->createVerifiedClient('client-owner@example.com', 'clientowner');
        $otherClient = $this->createVerifiedClient('client-other@example.com', 'clientother');
        $booking = $this->createBooking($otherClient, null, 'pending');

        $response = $this->actingAs($client)
            ->patch(route('bookings.cancel', $booking->id));

        $response->assertStatus(403);
    }

    public function test_admin_can_cancel_any_booking(): void
    {
        $admin = $this->createAdmin('admin-cancel@example.com', 'admincancel');
        $client = $this->createVerifiedClient('client-admin-cancel@example.com', 'clientadmincancel');
        $booking = $this->createBooking($client, null, 'pending');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'cancelled',
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHas('success');

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_admin_cannot_skip_status_to_cancel_completed_booking(): void
    {
        $admin = $this->createAdmin('admin-cancel-complete@example.com', 'admincancelcomplete');
        $client = $this->createVerifiedClient('client-cancel-complete@example.com', 'clientcancelcomplete');
        $staff = $this->createStaff('staff-cancel-complete@example.com', 'staffcancelcomplete');
        $booking = $this->createBooking($client, $staff, 'completed');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'cancelled',
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('status');

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_cancellation_creates_notification(): void
    {
        $admin = $this->createAdmin('admin-notify-cancel@example.com', 'adminnotifycancel');
        $client = $this->createVerifiedClient('client-notify-cancel@example.com', 'clientnotifycancel');
        $booking = $this->createBooking($client, null, 'pending');

        $this->actingAs($admin)
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'cancelled',
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $client->id,
            'title' => 'Booking cancelled',
        ]);
    }

    private function createVerifiedClient(string $email, string $username): User
    {
        $user = User::create([
            'first_name' => 'Client',
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
            'role' => 'client',
            'password' => Hash::make('password123'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    private function createAdmin(string $email, string $username): User
    {
        return User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => $email,
            'phone' => '09171234567',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'street' => '123 Admin Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => $username,
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);
    }

    private function createStaff(string $email, string $username): User
    {
        return User::create([
            'first_name' => 'Staff',
            'last_name' => 'User',
            'email' => $email,
            'phone' => '09171234567',
            'date_of_birth' => '1995-01-01',
            'gender' => 'male',
            'street' => '123 Staff Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => $username,
            'role' => 'staff',
            'password' => Hash::make('password123'),
        ]);
    }

    private function createBooking(User $client, ?User $staff, string $status): Booking
    {
        return Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1200,
            'status' => $status,
            'staff_id' => $staff?->id,
        ]);
    }
}
