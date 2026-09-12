<?php

namespace Tests\Feature;

use App\Jobs\SendExpoPushNotification;
use App\Models\MobilePushToken;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MobilePushTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_user_can_register_and_remove_a_push_token(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'push-token@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'client',
        ]);
        $token = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->assertOk()->json('token');
        $pushToken = 'ExponentPushToken[mobile-test-token]';

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/push-tokens', [
                'device_name' => 'Test Android',
                'platform' => 'android',
                'token' => $pushToken,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Push notifications enabled for this device.');

        $this->assertDatabaseHas('mobile_push_tokens', [
            'user_id' => $user->id,
            'token' => $pushToken,
            'platform' => 'android',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/mobile/push-tokens', ['token' => $pushToken])
            ->assertOk()
            ->assertJsonPath('deleted_count', 1);

        $this->assertDatabaseMissing('mobile_push_tokens', ['token' => $pushToken]);
        $this->assertSame(0, MobilePushToken::count());
    }

    public function test_mobile_push_token_cannot_be_registered_with_an_invalid_shape(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid-push-token@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'staff',
        ]);
        $token = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->assertOk()->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/push-tokens', [
                'platform' => 'android',
                'token' => 'not-a-push-token',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_notification_job_sends_a_push_payload_to_the_users_registered_devices(): void
    {
        Queue::fake();

        Http::fake([
            'https://exp.host/*' => Http::response([
                'data' => [['status' => 'ok', 'id' => 'ticket-1']],
            ], 200),
        ]);

        $user = User::factory()->create([
            'role' => 'client',
        ]);
        $pushToken = 'ExponentPushToken[delivery-test-token]';

        MobilePushToken::create([
            'user_id' => $user->id,
            'token' => $pushToken,
            'platform' => 'android',
            'device_name' => 'Test Android',
            'last_used_at' => now(),
        ]);
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => 'Booking confirmed',
            'message' => 'Your booking is confirmed.',
            'type' => 'success',
            'link' => '/notifications',
        ]);

        (new SendExpoPushNotification($notification->id))->handle();

        Http::assertSent(function ($request) use ($pushToken): bool {
            $payload = $request->data();

            return $request->url() === config('services.expo_push.url')
                && ($payload[0]['to'] ?? null) === $pushToken
                && ($payload[0]['title'] ?? null) === 'Booking confirmed'
                && ($payload[0]['data']['route'] ?? null) === '/client-notifications';
        });
    }
}
