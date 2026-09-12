<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->assertDatabaseHas('security_events', [
            'event' => 'web_login_succeeded',
            'user_id' => $user->id,
        ]);
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
        $this->assertDatabaseHas('security_events', [
            'event' => 'web_login_failed',
        ]);
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

    public function test_login_locks_for_one_minute_after_five_invalid_password_attempts(): void
    {
        RateLimiter::clear('login-attempts:locked-client@example.com|127.0.0.1');
        RateLimiter::clear('login-lockout:locked-client@example.com|127.0.0.1');

        $user = $this->createUser([
            'email' => 'locked-client@example.com',
            'username' => 'lockedclient',
        ]);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'Too many incorrect login attempts. Please try again in 1 minute.',
        ])->assertSessionHas('login_lockout');

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors([
            'email' => 'Too many incorrect login attempts. Please try again in 1 minute.',
        ])->assertSessionHas('login_lockout');

        $this->assertGuest();
    }

    public function test_login_page_has_persistent_lockout_countdown(): void
    {
        $response = $this
            ->withSession([
                'login_lockout' => [
                    'email' => 'locked-client@example.com',
                    'ends_at' => now()->addMinute()->timestamp,
                ],
            ])
            ->get(route('login'));

        $response->assertOk();
        $response->assertSee('login-lockout-countdown', false);
        $response->assertSee('cleanflow.login.lockout', false);
    }

    public function test_login_lockout_escalates_after_each_additional_invalid_attempt(): void
    {
        RateLimiter::clear('login-attempts:escalated-client@example.com|127.0.0.1');
        RateLimiter::clear('login-lockout:escalated-client@example.com|127.0.0.1');

        $user = $this->createUser([
            'email' => 'escalated-client@example.com',
            'username' => 'escalatedclient',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->travel(61)->seconds();

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'Too many incorrect login attempts. Please try again in 5 minutes.',
        ]);

        $this->travel(301)->seconds();

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'Too many incorrect login attempts. Please try again in 10 minutes.',
        ]);

        $this->assertGuest();
    }

    public function test_login_lockout_is_capped_at_one_hour(): void
    {
        RateLimiter::clear('login-attempts:capped-client@example.com|127.0.0.1');
        RateLimiter::clear('login-lockout:capped-client@example.com|127.0.0.1');

        $user = $this->createUser([
            'email' => 'capped-client@example.com',
            'username' => 'cappedclient',
        ]);

        for ($attempt = 1; $attempt <= 17; $attempt++) {
            $response = $this->from(route('login'))->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

            if ($attempt >= 5) {
                $response->assertSessionHasErrors('email');
                $this->travel(61 * 60)->seconds();
            }
        }

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors([
            'email' => 'Too many incorrect login attempts. Please try again in 60 minutes.',
        ]);

        $this->assertGuest();
    }

    public function test_user_can_reset_password_with_email_otp(): void
    {
        Notification::fake();

        $user = $this->createUser([
            'email' => 'forgot-client@example.com',
            'username' => 'forgotclient',
        ]);

        $this->from(route('login'))->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), false);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('password.reset.verify'));
        $response->assertSessionHas('password_reset_email', $user->email);

        Notification::assertSentTo($user, ResetPasswordOtp::class, function (ResetPasswordOtp $notification) use (&$code) {
            $code = $notification->code;

            return $notification->expiresInMinutes === 60;
        });

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        $resetResponse = $this
            ->withSession(['password_reset_email' => $user->email])
            ->post(route('password.update'), [
                'code' => $code,
                'password' => 'CleanFlow!Reset123',
                'password_confirmation' => 'CleanFlow!Reset123',
            ]);

        $resetResponse->assertRedirect(route('login'));
        $resetResponse->assertSessionHas('success', 'Password reset successful. You can now sign in.');

        $this->assertTrue(Hash::check('CleanFlow!Reset123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'CleanFlow!Reset123',
        ])->assertRedirect(route('client.dashboard'));
    }

    public function test_user_can_resend_password_reset_code_from_verify_page(): void
    {
        Notification::fake();

        $user = $this->createUser([
            'email' => 'resend-reset@example.com',
            'username' => 'resendreset',
        ]);

        $this->withSession(['password_reset_email' => $user->email])
            ->get(route('password.reset.verify'))
            ->assertOk()
            ->assertSee('Resend code')
            ->assertSee(route('password.resend'), false);

        $response = $this->withSession(['password_reset_email' => $user->email])
            ->post(route('password.resend'));

        $response->assertRedirect(route('password.reset.verify'));
        $response->assertSessionHas('success', 'If that email exists, a new password reset code was sent.');
        Notification::assertSentTo($user, ResetPasswordOtp::class);
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_password_reset_resend_requires_the_reset_session(): void
    {
        Notification::fake();

        $response = $this->post(route('password.resend'));

        $response->assertRedirect(route('password.request'));
        Notification::assertNothingSent();
    }

    public function test_password_reset_resend_is_rate_limited(): void
    {
        Notification::fake();

        $user = $this->createUser([
            'email' => 'throttled-reset@example.com',
            'username' => 'throttledreset',
        ]);

        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->withSession(['password_reset_email' => $user->email])
                ->post(route('password.resend'))
                ->assertRedirect(route('password.reset.verify'));
        }

        $this->withSession(['password_reset_email' => $user->email])
            ->post(route('password.resend'))
            ->assertTooManyRequests();
    }

    public function test_expired_password_reset_code_is_rejected(): void
    {
        $user = $this->createUser([
            'email' => 'expired-reset@example.com',
            'username' => 'expiredreset',
        ]);
        $code = '123456';

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($code),
            'created_at' => now()->subMinutes(61),
        ]);

        $response = $this
            ->withSession(['password_reset_email' => $user->email])
            ->from(route('password.reset.verify'))
            ->post(route('password.update'), [
                'code' => $code,
                'password' => 'CleanFlow!Reset123',
                'password_confirmation' => 'CleanFlow!Reset123',
            ]);

        $response->assertRedirect(route('password.reset.verify'));
        $response->assertSessionHasErrors('code');
        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
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
