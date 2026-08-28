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
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
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
        $this->assertSame(1, MobileApiToken::count());
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

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'ana@example.com')
            ->assertJsonPath('user.full_name', 'Ana Santos');
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
}
