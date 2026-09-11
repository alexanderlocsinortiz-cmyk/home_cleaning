<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_registration_rejects_identity_values_above_profile_limits(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'first_name' => str_repeat('A', 101),
            'last_name' => 'Valid',
            'email' => 'long-registration@example.com',
            'phone' => '09123456789',
            'date_of_birth' => '1990-01-01',
            'password' => 'CleanFlow!123',
            'password_confirmation' => 'CleanFlow!123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('first_name');
        $this->assertDatabaseCount('users', 0);
    }
}
