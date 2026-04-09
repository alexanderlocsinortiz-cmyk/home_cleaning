<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_admin_is_redirected_to_admin_dashboard_after_login(): void
    {
        $user = $this->createUser([
            'email' => 'admin@example.com',
            'username' => 'adminuser',
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_verified_staff_is_redirected_to_staff_dashboard_after_login(): void
    {
        $user = $this->createUser([
            'email' => 'staff@example.com',
            'username' => 'staffuser',
            'role' => 'staff',
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('staff.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_verified_client_is_redirected_to_client_dashboard_after_login(): void
    {
        $user = $this->createUser([
            'email' => 'client@example.com',
            'username' => 'clientuser',
            'role' => 'client',
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('client.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_unverified_client_is_redirected_to_email_verification_notice_after_login(): void
    {
        $user = $this->createUser([
            'email' => 'pending-client@example.com',
            'username' => 'pendingclient',
            'role' => 'client',
            'email_verified_at' => null,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHas('success', 'Please verify your email before continuing.');
        $this->assertAuthenticatedAs($user);
    }

    public function test_unverified_client_cannot_access_client_dashboard(): void
    {
        $user = $this->createUser([
            'email' => 'dashboard-client@example.com',
            'username' => 'dashboardclient',
            'role' => 'client',
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('client.dashboard'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_email_verification_code_redirects_users_to_their_role_dashboard(): void
    {
        $cases = [
            ['email' => 'verify-admin@example.com', 'username' => 'verifyadmin', 'role' => 'admin', 'route' => 'admin.dashboard'],
            ['email' => 'verify-staff@example.com', 'username' => 'verifystaff', 'role' => 'staff', 'route' => 'staff.dashboard'],
            ['email' => 'verify-client@example.com', 'username' => 'verifyclient', 'role' => 'client', 'route' => 'client.dashboard'],
        ];

        foreach ($cases as $case) {
            $user = $this->createUser([
                'email' => $case['email'],
                'username' => $case['username'],
                'role' => $case['role'],
                'email_verified_at' => null,
            ]);

            $verificationCode = $user->issueEmailVerificationCode();

            $response = $this
                ->actingAs($user)
                ->from(route('verification.notice'))
                ->post(route('verification.verify'), [
                    'code' => $verificationCode,
                ]);

            $response->assertRedirect(route($case['route']));
            $this->assertNotNull($user->fresh()->email_verified_at);
            $this->assertNull($user->fresh()->email_verification_code);
            auth()->logout();
        }
    }

    public function test_user_cannot_log_in_with_invalid_credentials(): void
    {
        $user = $this->createUser([
            'email' => 'invalid-client@example.com',
            'username' => 'invalidclient',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_cannot_log_in_with_username_instead_of_email(): void
    {
        $user = $this->createUser([
            'email' => 'username-login@example.com',
            'username' => 'emailloginonly',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->username,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function createUser(array $overrides = []): User
    {
        $emailVerifiedAt = array_key_exists('email_verified_at', $overrides)
            ? $overrides['email_verified_at']
            : now();
        unset($overrides['email_verified_at']);

        $user = User::create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'user@example.com',
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'testuser',
            'role' => 'client',
            'password' => Hash::make('password123'),
        ], $overrides));

        $user->forceFill(['email_verified_at' => $emailVerifiedAt])->save();

        return $user->fresh();
    }
}
