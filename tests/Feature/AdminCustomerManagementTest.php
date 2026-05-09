<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_customer_delete_route_only_deletes_clients(): void
    {
        $admin = $this->createUser('admin', 'admin-delete@example.com', 'admindelete');
        $staff = $this->createUser('staff', 'staff-delete@example.com', 'staffdelete');

        $response = $this->actingAs($admin)->delete(route('admin.customers.destroy', $staff->id));

        $response->assertNotFound();
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'role' => 'staff']);
    }

    public function test_admin_can_delete_a_client_customer(): void
    {
        $admin = $this->createUser('admin', 'admin-client-delete@example.com', 'adminclientdelete');
        $client = $this->createUser('client', 'client-delete@example.com', 'clientdelete');

        $response = $this->actingAs($admin)->delete(route('admin.customers.destroy', $client->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Customer deleted successfully.');
        $this->assertDatabaseMissing('users', ['id' => $client->id]);
    }

    public function test_admin_customer_list_shows_latest_booking_activity(): void
    {
        $admin = $this->createUser('admin', 'admin-customer-list@example.com', 'admincustomerlist');
        $client = $this->createUser('client', 'client-booking-list@example.com', 'clientbookinglist');

        Service::create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        $olderBooking = Booking::create([
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
            'price' => 570,
            'status' => 'pending',
        ]);

        $latestBooking = Booking::create([
            'user_id' => $client->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'rooms' => 2,
            'bathrooms' => 1,
            'floor_area' => 35,
            'barangay' => 'Poblacion',
            'street_address' => '123 Rizal Street',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '10:00',
            'price' => 570,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.customers'));

        $response->assertOk();
        $response->assertSee('Confirmed');
        $response->assertSee(Carbon::parse($latestBooking->scheduled_date)->format('M d, Y'));
        $response->assertSee('bookings\/'.$latestBooking->id, false);
        $this->assertNotEquals($olderBooking->id, $latestBooking->id);
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
}
