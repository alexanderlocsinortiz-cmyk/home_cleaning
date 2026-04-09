<?php

namespace Tests\Feature;

use App\Models\User;
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
