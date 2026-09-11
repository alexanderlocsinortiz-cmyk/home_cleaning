<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffLocationTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_uses_the_named_location_update_route_for_active_bookings(): void
    {
        $staff = User::create([
            'first_name' => 'Staff',
            'last_name' => 'Member',
            'email' => 'staff@example.com',
            'phone' => '09170000001',
            'date_of_birth' => '1998-01-01',
            'gender' => 'male',
            'street' => '456 Mabini Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'staffmember',
            'role' => 'staff',
            'password' => Hash::make('password123'),
        ]);

        $client = User::create([
            'first_name' => 'Client',
            'last_name' => 'Person',
            'email' => 'client@example.com',
            'phone' => '09170000002',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'clientperson',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ]);

        $booking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'service_latitude' => 7.9073,
            'service_longitude' => 125.0920,
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 570,
            'status' => 'in_progress',
            'staff_id' => $staff->id,
        ]);

        $response = $this->actingAs($staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertSee(route('booking.location.update', $booking->id));

        $bookingsResponse = $this->actingAs($staff)->get(route('staff.bookings'));

        $bookingsResponse->assertOk();
        $bookingsResponse->assertSee('data-destination-lat="7.9073"', false);
        $bookingsResponse->assertSee('data-destination-lng="125.092"', false);
        $bookingsResponse->assertSee('Show route', false);
        $bookingsResponse->assertSee('data-remove-upload', false);
        $bookingsResponse->assertSee('Remove video', false);
        $bookingsResponse->assertSee('for="completion-video-'.$booking->id.'"', false);
    }

    public function test_provider_cannot_read_a_booking_location_from_the_shared_endpoint(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $staff = User::factory()->create(['role' => 'staff']);
        $provider = User::factory()->create(['role' => 'provider']);
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'status' => 'in_progress',
            'current_latitude' => 7.9073,
            'current_longitude' => 125.092,
        ]);

        $this->actingAs($provider)
            ->getJson(route('booking.location.current', $booking->id))
            ->assertForbidden();
    }

    public function test_unverified_client_cannot_read_a_booking_location_from_the_shared_endpoint(): void
    {
        $client = User::factory()->unverified()->create(['role' => 'client']);
        $staff = User::factory()->create(['role' => 'staff']);
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'status' => 'in_progress',
            'current_latitude' => 7.9073,
            'current_longitude' => 125.092,
        ]);

        $this->actingAs($client)
            ->getJson(route('booking.location.current', $booking->id))
            ->assertForbidden()
            ->assertJson([
                'message' => 'Please verify your email before viewing booking location.',
                'requires_email_verification' => true,
            ]);
    }

    public function test_staff_can_send_repeated_location_updates_without_a_server_error(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $staff = User::factory()->create(['role' => 'staff']);
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($staff)
            ->postJson(route('booking.location.update', $booking->id), [
                'latitude' => 7.9073,
                'longitude' => 125.092,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($staff)
            ->postJson(route('booking.location.update', $booking->id), [
                'latitude' => 7.90731,
                'longitude' => 125.09201,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('recorded', false);
    }

    public function test_shared_location_reader_does_not_treat_zero_coordinates_as_missing(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $staff = User::factory()->create(['role' => 'staff']);
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'status' => 'in_progress',
            'current_latitude' => 0,
            'current_longitude' => 0,
        ]);

        $this->actingAs($staff)
            ->getJson(route('booking.location.current', $booking->id))
            ->assertOk()
            ->assertJsonPath('tracking', true)
            ->assertJsonPath('latitude', 0)
            ->assertJsonPath('longitude', 0);
    }
}
