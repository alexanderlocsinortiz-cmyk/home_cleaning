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
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'price' => 570,
            'status' => 'in_progress',
            'staff_id' => $staff->id,
        ]);

        $response = $this->actingAs($staff)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertSee(route('booking.location.update', $booking->id));
    }
}
