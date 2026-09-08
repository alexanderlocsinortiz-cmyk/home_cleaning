<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_as_a_client(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'password' => 'CleanFlow!Client123',
            'password_confirmation' => 'CleanFlow!Client123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHas('success', 'Registration successful. Enter the verification code sent to your email.');

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertSame('client', $user->role);
        $this->assertNull($user->username);
        $this->assertSame('09171234567', $user->phone);
        $this->assertSame('2000-01-01', optional($user->date_of_birth)->toDateString());
        $this->assertNotNull($user->email_verification_code);
        $this->assertNotNull($user->email_verification_code_expires_at);

        Notification::assertSentTo(
            $user,
            CustomVerifyEmail::class,
            fn (CustomVerifyEmail $notification): bool => preg_match('/^\d{6}$/', $notification->code) === 1
        );
    }

    public function test_underage_user_cannot_register_as_a_client(): void
    {
        Notification::fake();

        $response = $this->from(route('register'))->post(route('register.store'), [
            'first_name' => 'Young',
            'last_name' => 'Client',
            'email' => 'young@example.com',
            'phone' => '09171234568',
            'date_of_birth' => now()->subYears(17)->addDay()->toDateString(),
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('date_of_birth');
        $this->assertDatabaseMissing('users', ['email' => 'young@example.com']);
    }

    public function test_weak_password_is_rejected_during_registration(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'first_name' => 'Weak',
            'last_name' => 'Password',
            'email' => 'weak-password@example.com',
            'phone' => '09171234569',
            'date_of_birth' => '2000-01-01',
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'weak-password@example.com']);
    }

    public function test_password_without_uppercase_and_symbol_is_rejected(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'first_name' => 'Missing',
            'last_name' => 'Requirements',
            'email' => 'missing-requirements@example.com',
            'phone' => '09171234571',
            'date_of_birth' => '2000-01-01',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'missing-requirements@example.com']);
    }

    public function test_strong_password_is_accepted_during_registration(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'first_name' => 'Strong',
            'last_name' => 'Password',
            'email' => 'strong-password@example.com',
            'phone' => '09171234570',
            'date_of_birth' => '2000-01-01',
            'password' => 'CleanFlow!Strong123',
            'password_confirmation' => 'CleanFlow!Strong123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', ['email' => 'strong-password@example.com']);
    }
}
