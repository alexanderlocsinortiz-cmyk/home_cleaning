<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_rate_a_completed_booking_once(): void
    {
        $client = $this->createUser('client', 'client-rating@example.com', 'clientrating');
        $staff = $this->createUser('staff', 'staff-rating@example.com', 'staffrating');
        $booking = $this->createBooking($client, $staff, 'completed');

        $response = $this->actingAs($client)
            ->from(route('bookings.show', $booking->id))
            ->post(route('bookings.rate', $booking->id), [
                'stars' => 5,
                'comment' => 'Excellent work.',
            ]);

        $response->assertRedirect(route('bookings.show', $booking->id));
        $response->assertSessionHas('success', 'Thanks for sharing your feedback. Your rating has been saved.');
        $this->assertDatabaseHas('ratings', [
            'booking_id' => $booking->id,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'stars' => 5,
        ]);
    }

    public function test_client_cannot_rate_a_booking_until_it_is_completed(): void
    {
        $client = $this->createUser('client', 'pending-rating@example.com', 'pendingrating');
        $staff = $this->createUser('staff', 'pending-staff@example.com', 'pendingstaff');
        $booking = $this->createBooking($client, $staff, 'confirmed');

        $response = $this->actingAs($client)
            ->from(route('bookings.show', $booking->id))
            ->post(route('bookings.rate', $booking->id), [
                'stars' => 4,
            ]);

        $response->assertRedirect(route('bookings.show', $booking->id));
        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_client_cannot_rate_the_same_booking_twice(): void
    {
        $client = $this->createUser('client', 'duplicate-rating@example.com', 'duplicaterating');
        $staff = $this->createUser('staff', 'duplicate-staff@example.com', 'duplicatestaff');
        $booking = $this->createBooking($client, $staff, 'completed');

        Rating::create([
            'booking_id' => $booking->id,
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'stars' => 5,
            'comment' => 'Already rated.',
        ]);

        $response = $this->actingAs($client)
            ->from(route('bookings.show', $booking->id))
            ->post(route('bookings.rate', $booking->id), [
                'stars' => 3,
                'comment' => 'Second try.',
            ]);

        $response->assertRedirect(route('bookings.show', $booking->id));
        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('ratings', 1);
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

    private function createBooking(User $client, User $staff, string $status): Booking
    {
        return Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->subDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 1200,
            'status' => $status,
            'staff_id' => $staff->id,
        ]);
    }
}
