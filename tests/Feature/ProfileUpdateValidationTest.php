<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileUpdateValidationTest extends TestCase
{
    public function test_web_client_profile_date_picker_has_the_same_age_limit(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('max="'.now(config('cleanflow.attendance_timezone', config('app.timezone')))->subYears(18)->toDateString().'"', false);
    }

    public function test_web_client_profile_rejects_an_underage_birthday(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'phone' => '09123456789',
            'date_of_birth' => '2000-01-01',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'password' => Hash::make('Password123'),
        ]);

        $response = $this->actingAs($client)->put(route('profile.update'), [
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'phone' => $client->phone,
            'date_of_birth' => now()->subYears(17)->toDateString(),
            'street' => $client->street,
            'barangay' => $client->barangay,
        ]);

        $response->assertSessionHasErrors('date_of_birth');
        $this->assertSame('2000-01-01', $client->fresh()->date_of_birth->toDateString());
    }

    public function test_wrong_current_password_does_not_save_other_profile_changes(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'phone' => '09123456789',
            'date_of_birth' => '2000-01-01',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'password' => Hash::make('Password123'),
        ]);

        $response = $this->actingAs($client)->put(route('profile.update'), [
            'first_name' => 'Should Not Save',
            'last_name' => $client->last_name,
            'phone' => $client->phone,
            'date_of_birth' => '2000-01-01',
            'street' => '456 Changed Street',
            'barangay' => $client->barangay,
            'current_password' => 'wrong-password',
            'new_password' => 'NewPassword123!',
            'new_password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors('current_password');
        $freshClient = $client->fresh();

        $this->assertSame($client->first_name, $freshClient->first_name);
        $this->assertSame($client->street, $freshClient->street);
        $this->assertTrue(Hash::check('Password123', $freshClient->password));
    }

    public function test_client_portal_profile_matches_booking_address_limits(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'phone' => '09123456789',
            'date_of_birth' => '2000-01-01',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
        ]);

        $response = $this->actingAs($client)->put(route('client.profile.update'), [
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'phone' => $client->phone,
            'date_of_birth' => '2000-01-01',
            'street' => str_repeat('A', 256),
            'barangay' => 'Not A Covered Barangay',
        ]);

        $response->assertSessionHasErrors(['street', 'barangay']);
        $freshClient = $client->fresh();

        $this->assertSame('123 Rizal Street', $freshClient->street);
        $this->assertSame('Poblacion', $freshClient->barangay);
    }
}
