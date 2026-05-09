<?php

namespace Tests\Unit;

use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_user_can_be_created()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'role' => 'client',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_has_required_fields()
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->first_name);
        $this->assertNotNull($user->last_name);
        $this->assertNotNull($user->email);
    }

    public function test_user_password_is_hashed()
    {
        $plainPassword = 'password123';
        $user = User::factory()->create(['password' => bcrypt($plainPassword)]);

        $this->assertTrue(\Hash::check($plainPassword, $user->password));
    }

    public function test_user_has_phone_and_date_of_birth()
    {
        $user = User::factory()->create([
            'phone' => '09123456789',
            'date_of_birth' => '2000-01-01',
        ]);

        $this->assertEquals('09123456789', $user->phone);
        $this->assertInstanceOf(Carbon::class, $user->date_of_birth);
    }

    public function test_user_role_can_be_set()
    {
        $roles = ['client', 'staff', 'admin'];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertEquals($role, $user->role);
        }
    }

    public function test_user_address_fields_are_stored()
    {
        $user = User::factory()->create([
            'street' => '123 Main St',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8718',
        ]);

        $this->assertEquals('123 Main St', $user->street);
        $this->assertEquals('Poblacion', $user->barangay);
        $this->assertEquals('Valencia City', $user->city);
        $this->assertEquals('8718', $user->zip_code);
    }

    public function test_user_password_is_hidden()
    {
        $user = User::factory()->create();
        $attributes = $user->getHidden();

        $this->assertContains('password', $attributes);
    }

    public function test_user_email_verification_code_is_hidden()
    {
        $user = User::factory()->create();
        $attributes = $user->getHidden();

        $this->assertContains('email_verification_code', $attributes);
    }

    public function test_user_can_have_email_verified()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->assertNull($user->email_verified_at);

        // Reload from database after update
        $user->email_verified_at = now();
        $user->save();
        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_have_fingerprint_template()
    {
        $user = User::factory()->create(['fingerprint_template_id' => 12345]);
        $this->assertEquals(12345, $user->refresh()->fingerprint_template_id);
    }

    public function test_user_gender_is_optional()
    {
        $user = User::factory()->create(['gender' => null]);
        $this->assertNull($user->gender);
    }

    public function test_client_has_multiple_bookings()
    {
        $user = User::factory()->create(['role' => 'client']);

        \App\Models\Booking::factory(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->bookings);
    }
}
