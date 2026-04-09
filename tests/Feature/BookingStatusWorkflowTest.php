<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingStatusWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_confirm_a_booking_without_assigning_staff(): void
    {
        $admin = $this->createUser('admin', 'admin-status@example.com', 'adminstatus');
        $client = $this->createUser('client', 'client-status@example.com', 'clientstatus');
        $booking = $this->createBooking($client, null, 'pending');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'confirmed',
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('staff_id');
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_admin_cannot_assign_a_client_as_staff(): void
    {
        $admin = $this->createUser('admin', 'admin-assign@example.com', 'adminassign');
        $client = $this->createUser('client', 'client-assign@example.com', 'clientassign');
        $booking = $this->createBooking($client, null, 'pending');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'pending',
                'staff_id' => $client->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('staff_id');
        $this->assertNull($booking->fresh()->staff_id);
    }

    public function test_admin_cannot_skip_pending_booking_straight_to_completed(): void
    {
        $admin = $this->createUser('admin', 'admin-skip@example.com', 'adminskip');
        $client = $this->createUser('client', 'client-skip@example.com', 'clientskip');
        $staff = $this->createUser('staff', 'staff-skip@example.com', 'staffskip');
        $booking = $this->createBooking($client, $staff, 'pending');

        $response = $this->actingAs($admin)
            ->from(route('admin.bookings'))
            ->patch(route('admin.bookings.status', $booking->id), [
                'status' => 'completed',
                'staff_id' => $staff->id,
            ]);

        $response->assertRedirect(route('admin.bookings'));
        $response->assertSessionHasErrors('status');
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_staff_can_move_confirmed_booking_to_in_progress(): void
    {
        $client = $this->createUser('client', 'client-progress@example.com', 'clientprogress');
        $staff = $this->createUser('staff', 'staff-progress@example.com', 'staffprogress');
        $booking = $this->createBooking($client, $staff, 'confirmed');

        $response = $this->actingAs($staff)
            ->from(route('staff.bookings'))
            ->patch(route('staff.bookings.status', $booking->id), [
                'status' => 'in_progress',
            ]);

        $response->assertRedirect(route('staff.bookings'));
        $response->assertSessionHas('success', 'Booking marked as in progress.');
        $this->assertSame('in_progress', $booking->fresh()->status);
    }

    public function test_staff_cannot_mark_confirmed_booking_as_completed_directly(): void
    {
        $client = $this->createUser('client', 'client-complete@example.com', 'clientcomplete');
        $staff = $this->createUser('staff', 'staff-complete@example.com', 'staffcomplete');
        $booking = $this->createBooking($client, $staff, 'confirmed');

        $response = $this->actingAs($staff)
            ->from(route('staff.bookings'))
            ->patch(route('staff.bookings.status', $booking->id), [
                'status' => 'completed',
            ]);

        $response->assertRedirect(route('staff.bookings'));
        $response->assertSessionHasErrors('status');
        $this->assertSame('confirmed', $booking->fresh()->status);
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

    private function createBooking(User $client, ?User $staff, string $status): Booking
    {
        return Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1200,
            'status' => $status,
            'staff_id' => $staff?->id,
        ]);
    }
}
