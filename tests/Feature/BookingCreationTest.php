<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_booking_create_route_uses_the_real_booking_form(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $user = User::create([
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => 'client-route@example.com',
            'phone' => '09171234568',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'clientroute',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $response = $this->actingAs($user)->get(route('client.bookings.create'));

        $response->assertOk();
        $response->assertViewIs('bookings.create');
        $response->assertDontSee('Floor Area (sqm)', false);
        $response->assertDontSee('Add-ons (optional)', false);
        $response->assertSee('Street / Purok / House Details', false);
    }

    public function test_authenticated_client_can_create_a_booking_with_calculated_price(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $user = User::create([
            'first_name' => 'Client',
            'last_name' => 'User',
            'email' => 'client@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'clientuser',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $payload = [
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 3,
            'bathrooms' => 2,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '09:00',
        ];

        $response = $this->actingAs($user)->post(route('bookings.store'), $payload);

        $response->assertRedirect(route('bookings.index'));

        $booking = Booking::first();

        $this->assertNotNull($booking);
        $this->assertSame($user->id, $booking->user_id);
        $this->assertSame('pending', $booking->status);
        $this->assertSame(970.0, (float) $booking->price);
        $this->assertSame(570.0, (float) $booking->base_price);
        $this->assertSame(200.0, (float) $booking->property_fee);
        $this->assertSame(100.0, (float) $booking->rooms_fee);
        $this->assertSame(100.0, (float) $booking->bathrooms_fee);
    }

    public function test_unverified_client_cannot_create_a_booking(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $user = User::create([
            'first_name' => 'Pending',
            'last_name' => 'Client',
            'email' => 'pending@example.com',
            'phone' => '09171234569',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'pendingclient',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post(route('bookings.store'), [
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 2,
            'bathrooms' => 1,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '09:00',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_staff_cannot_create_a_booking_through_client_routes(): void
    {
        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $user = User::create([
            'first_name' => 'Staff',
            'last_name' => 'User',
            'email' => 'staff-booking@example.com',
            'phone' => '09171234560',
            'date_of_birth' => '1998-01-01',
            'gender' => 'male',
            'street' => '456 Mabini Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'staffbooking',
            'role' => 'staff',
            'password' => Hash::make('password123'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $response = $this->actingAs($user)->post(route('bookings.store'), [
            'service_type' => 'basic',
            'property_type' => 'apartment',
            'rooms' => 2,
            'bathrooms' => 1,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '09:00',
        ]);

        $response->assertRedirect(route('staff.dashboard'));
        $this->assertDatabaseCount('bookings', 0);
    }
}
