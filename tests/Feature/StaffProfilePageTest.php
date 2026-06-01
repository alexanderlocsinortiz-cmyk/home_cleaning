<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffProfilePageTest extends TestCase
{
    public function test_staff_profile_does_not_render_barangay_assignment_controls(): void
    {
        $staff = $this->createStaff();

        $response = $this->actingAs($staff)->get(route('staff.profile'));

        $response->assertOk();
        $response->assertDontSee('Assigned barangay');
        $response->assertDontSee('name="barangay"', false);
    }

    public function test_staff_profile_updates_without_barangay_payload(): void
    {
        $staff = $this->createStaff();

        $response = $this->actingAs($staff)->put(route('staff.profile.update'), [
            'first_name' => 'Updated',
            'last_name' => 'Cleaner',
            'phone' => '09175551234',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'first_name' => 'Updated',
            'last_name' => 'Cleaner',
            'phone' => '09175551234',
            'barangay' => 'Laligan',
        ]);
    }

    private function createStaff(): User
    {
        return User::create([
            'first_name' => 'Staff',
            'last_name' => 'Member',
            'email' => 'staff-profile@example.com',
            'phone' => '09170000001',
            'date_of_birth' => '1998-01-01',
            'gender' => 'male',
            'street' => '456 Mabini Street',
            'barangay' => 'Laligan',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'staffprofile',
            'role' => 'staff',
            'password' => Hash::make('password123'),
        ]);
    }
}
