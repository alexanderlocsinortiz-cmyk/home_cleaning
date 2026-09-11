<?php

namespace Tests\Feature;

use App\Models\MobileApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MobileAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_client_can_register_and_receive_bearer_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/mobile/register', [
            'first_name' => 'Maria',
            'last_name' => 'Cruz',
            'email' => 'maria.mobile@example.com',
            'phone' => '09123456789',
            'date_of_birth' => '1999-01-10',
            'password' => 'CleanFlow!Mobile123',
            'password_confirmation' => 'CleanFlow!Mobile123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'maria.mobile@example.com')
            ->assertJsonPath('user.role', 'client');

        $this->assertDatabaseHas('users', [
            'email' => 'maria.mobile@example.com',
            'role' => 'client',
        ]);
        $this->assertDatabaseHas('security_events', [
            'event' => 'mobile_registration',
            'user_id' => User::where('email', 'maria.mobile@example.com')->value('id'),
        ]);
        $this->assertSame(1, MobileApiToken::count());
    }

    public function test_mobile_registration_rejects_a_non_philippine_mobile_phone_shape(): void
    {
        $this->postJson('/api/mobile/register', [
            'first_name' => 'Invalid',
            'last_name' => 'Phone',
            'email' => 'invalid-mobile-phone@example.com',
            'phone' => '12345678901',
            'date_of_birth' => '1999-01-10',
            'password' => 'CleanFlow!Mobile123',
            'password_confirmation' => 'CleanFlow!Mobile123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        $this->assertDatabaseMissing('users', ['email' => 'invalid-mobile-phone@example.com']);
    }

    public function test_mobile_user_can_login_and_fetch_profile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'email' => 'ana@example.com',
            'role' => 'client',
            'password' => Hash::make('Password123'),
        ]);

        $login = $this->postJson('/api/mobile/login', [
            'email' => 'ana@example.com',
            'password' => 'Password123',
            'device_name' => 'Expo Go',
        ]);

        $login->assertOk()->assertJsonPath('user.id', $user->id);
        $this->assertDatabaseHas('security_events', [
            'event' => 'mobile_login_succeeded',
            'user_id' => $user->id,
        ]);

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'ana@example.com')
            ->assertJsonPath('user.full_name', 'Ana Santos');
    }

    public function test_unverified_mobile_client_can_request_and_complete_email_verification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'verify-mobile@example.com',
            'email_verified_at' => null,
            'password' => Hash::make('Password123'),
            'role' => 'client',
        ]);
        $token = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->assertOk()->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/email-verification/send')
            ->assertOk()
            ->assertJsonPath('email_verified', false)
            ->assertJsonPath('expires_in_minutes', 15);

        $code = $user->fresh()->issueEmailVerificationCode();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/email-verification/verify', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('email_verified', true)
            ->assertJsonPath('user.email_verified', true);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNull($user->fresh()->email_verification_code);
    }

    public function test_mobile_email_verification_requires_a_six_digit_code(): void
    {
        $user = User::factory()->create([
            'email' => 'verify-mobile-format@example.com',
            'email_verified_at' => null,
            'password' => Hash::make('Password123'),
            'role' => 'client',
        ]);
        $token = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/email-verification/verify', ['code' => '123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_mobile_login_rejects_bad_password(): void
    {
        User::factory()->create([
            'email' => 'bad-password@example.com',
            'password' => Hash::make('Password123'),
        ]);

        $this->postJson('/api/mobile/login', [
            'email' => 'bad-password@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseHas('security_events', [
            'event' => 'mobile_login_failed',
            'user_id' => User::where('email', 'bad-password@example.com')->value('id'),
        ]);
    }

    public function test_mobile_login_rejects_a_role_not_supported_by_the_mobile_app(): void
    {
        User::factory()->create([
            'email' => 'admin-mobile@example.com',
            'role' => 'admin',
            'password' => Hash::make('Password123'),
        ]);

        $this->postJson('/api/mobile/login', [
            'email' => 'admin-mobile@example.com',
            'password' => 'Password123',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'This account role is not available in the mobile app.');

        $this->assertDatabaseCount('mobile_api_tokens', 0);
    }

    public function test_mobile_logout_revokes_current_token(): void
    {
        User::factory()->create([
            'email' => 'logout@example.com',
            'password' => Hash::make('Password123'),
        ]);

        $token = $this->postJson('/api/mobile/login', [
            'email' => 'logout@example.com',
            'password' => 'Password123',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/logout')
            ->assertOk();

        $this->assertSame(0, MobileApiToken::count());

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/me')
            ->assertUnauthorized();
    }

    public function test_existing_mobile_token_is_blocked_when_account_is_restricted(): void
    {
        $user = User::factory()->create([
            'email' => 'restricted-mobile@example.com',
            'password' => Hash::make('Password123'),
        ]);

        $token = $this->postJson('/api/mobile/login', [
            'email' => 'restricted-mobile@example.com',
            'password' => 'Password123',
        ])->json('token');

        $user->forceFill([
            'access_restricted_until' => now()->addHour(),
            'access_restriction_reason' => 'Policy violation',
        ])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'This account is temporarily restricted.')
            ->assertJsonPath('reason', 'Policy violation');
    }

    public function test_mobile_login_keeps_only_the_five_most_recent_sessions(): void
    {
        $user = User::factory()->create([
            'email' => 'session-limit@example.com',
            'password' => Hash::make('Password123'),
        ]);
        $tokens = [];

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $tokens[] = $this->postJson('/api/mobile/login', [
                'email' => $user->email,
                'password' => 'Password123',
                'device_name' => 'Device '.$attempt,
            ])->assertOk()->json('token');
        }

        $this->assertDatabaseCount('mobile_api_tokens', 5);

        $this->withHeader('Authorization', 'Bearer '.$tokens[0])
            ->getJson('/api/mobile/me')
            ->assertUnauthorized();

        $this->withHeader('Authorization', 'Bearer '.$tokens[5])
            ->getJson('/api/mobile/me')
            ->assertOk();
    }

    public function test_mobile_user_can_log_out_all_devices(): void
    {
        $user = User::factory()->create([
            'email' => 'logout-all@example.com',
            'password' => Hash::make('Password123'),
        ]);
        $tokens = [];

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $tokens[] = $this->postJson('/api/mobile/login', [
                'email' => $user->email,
                'password' => 'Password123',
                'device_name' => 'Device '.$attempt,
            ])->assertOk()->json('token');
        }

        $this->withHeader('Authorization', 'Bearer '.$tokens[0])
            ->postJson('/api/mobile/logout-all')
            ->assertOk()
            ->assertJsonPath('revoked_count', 2);

        $this->assertDatabaseCount('mobile_api_tokens', 0);
        $this->assertDatabaseHas('security_events', [
            'event' => 'mobile_logout_all',
            'user_id' => $user->id,
        ]);

        foreach ($tokens as $token) {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->getJson('/api/mobile/me')
                ->assertUnauthorized();
        }
    }
}
