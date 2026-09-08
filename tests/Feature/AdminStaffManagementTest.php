<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_create_staff_with_invalid_phone_number(): void
    {
        $admin = $this->createUser('admin', 'admin-staff-phone@example.com', 'adminstaffphone');

        $response = $this->actingAs($admin)->post(route('admin.staff.store'), [
            'first_name' => 'Invalid',
            'last_name' => 'Phone',
            'email' => 'invalid-phone-staff@example.com',
            'phone' => '0924252j5j5j355sgg',
            'username' => 'invalidphone',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('users', ['email' => 'invalid-phone-staff@example.com']);
    }

    public function test_admin_cannot_update_staff_with_invalid_phone_number(): void
    {
        $admin = $this->createUser('admin', 'admin-staff-update-phone@example.com', 'adminstaffupdatephone');
        $staff = $this->createUser('staff', 'staff-update-phone@example.com', 'staffupdatephone');

        $response = $this->actingAs($admin)->put(route('admin.staff.update', $staff), [
            'first_name' => 'Staff',
            'last_name' => 'User',
            'email' => $staff->email,
            'phone' => '0917-invalid',
            'username' => $staff->username,
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'phone' => '09171234567',
        ]);
    }

    public function test_admin_cannot_delete_staff_with_booking_history(): void
    {
        $admin = $this->createUser('admin', 'admin-staff-protect@example.com', 'adminstaffprotect');
        $client = $this->createUser('client', 'client-staff-protect@example.com', 'clientstaffprotect');
        $staff = $this->createUser('staff', 'staff-protect@example.com', 'staffprotect');

        $this->canonicalService([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'description' => 'Routine cleaning',
            'price' => 570,
            'is_active' => true,
        ]);

        Booking::create([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
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
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.staff.destroy', $staff));

        $response->assertRedirect(route('admin.staff.index'));
        $response->assertSessionHas('error', 'Staff members with booking history are protected from deletion.');
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'role' => 'staff']);
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
