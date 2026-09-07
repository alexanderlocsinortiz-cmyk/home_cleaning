<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MobilePasswordResetApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_user_can_request_verify_and_complete_password_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'mobile-reset@example.com',
            'password' => Hash::make('old-password-123'),
        ]);

        $this->postJson('/api/mobile/password/request-code', [
            'email' => $user->email,
        ])->assertOk()
            ->assertJsonPath('expires_in_minutes', 60)
            ->assertJsonPath('resend_after_seconds', 45);

        $code = null;
        Notification::assertSentTo($user, ResetPasswordOtp::class, function (ResetPasswordOtp $notification) use (&$code): bool {
            $code = $notification->code;

            return $notification->expiresInMinutes === 60;
        });

        $verification = $this->postJson('/api/mobile/password/verify-code', [
            'code' => $code,
            'email' => $user->email,
        ])->assertOk()
            ->assertJsonPath('expires_in_seconds', 600);

        $resetToken = $verification->json('reset_token');

        $this->postJson('/api/mobile/password/reset', [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
            'reset_token' => $resetToken,
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->postJson('/api/mobile/password/reset', [
            'password' => 'another-password-123',
            'password_confirmation' => 'another-password-123',
            'reset_token' => $resetToken,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('reset_token');
    }

    public function test_mobile_password_reset_does_not_reveal_unknown_email(): void
    {
        Notification::fake();

        $this->postJson('/api/mobile/password/request-code', [
            'email' => 'missing-reset@example.com',
        ])->assertOk()
            ->assertJsonPath('message', 'If that email exists, a password reset code was sent.');

        Notification::assertNothingSent();
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function test_password_reset_revokes_existing_mobile_tokens(): void
    {
        Notification::fake();
        $user = User::factory()->create([
            'email' => 'reset-sessions@example.com',
            'password' => Hash::make('old-password-123'),
        ]);

        $oldToken = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'old-password-123',
        ])->json('token');

        $this->postJson('/api/mobile/password/request-code', [
            'email' => $user->email,
        ])->assertOk();

        $code = null;
        Notification::assertSentTo($user, ResetPasswordOtp::class, function (ResetPasswordOtp $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        });

        $resetToken = $this->postJson('/api/mobile/password/verify-code', [
            'email' => $user->email,
            'code' => $code,
        ])->json('reset_token');

        $this->postJson('/api/mobile/password/reset', [
            'reset_token' => $resetToken,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->getJson('/api/mobile/me')
            ->assertUnauthorized();

        $this->assertDatabaseCount('mobile_api_tokens', 0);
    }
}
