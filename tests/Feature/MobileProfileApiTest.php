<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_user_can_persist_contact_profile_changes(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'email' => 'profile-mobile@example.com',
            'phone' => '09123456789',
            'role' => 'staff',
            'password' => Hash::make('Password123'),
        ]);

        $token = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/mobile/profile', [
                'first_name' => 'Anne',
                'last_name' => 'Reyes',
                'phone' => '09987654321',
            ])
            ->assertOk()
            ->assertJsonPath('user.first_name', 'Anne')
            ->assertJsonPath('user.phone', '09987654321');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Anne',
            'last_name' => 'Reyes',
            'phone' => '09987654321',
        ]);
    }

    public function test_mobile_profile_rejects_a_non_philippine_mobile_phone_shape(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid-profile-phone@example.com',
            'phone' => '09123456789',
            'role' => 'client',
            'password' => Hash::make('Password123'),
        ]);

        $token = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/mobile/profile', [
                'first_name' => 'Ana',
                'last_name' => 'Santos',
                'phone' => '12345678901',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '09123456789',
        ]);
    }

    public function test_mobile_client_profile_enforces_the_same_date_rules_as_registration(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid-profile-birthday@example.com',
            'phone' => '09123456789',
            'role' => 'client',
            'password' => Hash::make('Password123'),
        ]);

        $token = $this->postJson('/api/mobile/login', [
            'email' => $user->email,
            'password' => 'Password123',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/mobile/profile', [
                'first_name' => 'Ana',
                'last_name' => 'Santos',
                'phone' => '09123456789',
                'date_of_birth' => '2000-1-1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_of_birth');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/mobile/profile', [
                'first_name' => 'Ana',
                'last_name' => 'Santos',
                'phone' => '09123456789',
                'date_of_birth' => now()->subYears(17)->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_of_birth');
    }
}
