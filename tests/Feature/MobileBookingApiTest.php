<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileBookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_booking_creation_requires_authentication(): void
    {
        $this->postJson('/api/mobile/bookings', $this->validPayload())
            ->assertUnauthorized();
    }

    public function test_mobile_client_can_create_real_booking(): void
    {
        $service = $this->service();
        $token = $this->mobileToken();

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/bookings', $this->validPayload([
                'service_type' => $service->slug,
            ]));

        $response
            ->assertCreated()
            ->assertJsonPath('booking.service.slug', 'deep')
            ->assertJsonPath('booking.status', 'pending')
            ->assertJsonPath('booking.payment_status', 'pending')
            ->assertJsonPath('formatted_total', 'P2,850');

        $this->assertDatabaseHas('bookings', [
            'service_id' => $service->id,
            'service_type' => 'deep',
            'property_type' => 'house',
            'floor_area' => 30,
            'barangay' => 'Poblacion',
            'street_address' => '123 Sample Street',
            'status' => 'pending',
            'price' => 2850,
        ]);
    }

    public function test_mobile_client_can_list_own_bookings(): void
    {
        $service = $this->service();
        $user = User::factory()->create([
            'email' => 'booking-list@example.com',
            'password' => Hash::make('Password123'),
        ]);
        $otherUser = User::factory()->create();
        $token = $this->loginToken('booking-list@example.com');

        Booking::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'service_type' => $service->slug,
            'property_type' => 'house',
            'rooms' => 1,
            'bathrooms' => 1,
            'floor_area' => 30,
            'add_ons' => [],
            'barangay' => 'Poblacion',
            'street_address' => '123 Sample Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '08:00',
            'duration_minutes' => 120,
            'price' => 2850,
            'base_price' => 0,
            'property_fee' => 0,
            'rooms_fee' => 0,
            'bathrooms_fee' => 0,
            'floor_area_fee' => 2850,
            'add_ons_fee' => 0,
            'payment_method' => 'on_site_cash',
            'payment_status' => 'pending',
            'service_plan' => 'one_time',
            'status' => 'pending',
        ]);

        Booking::create([
            'user_id' => $otherUser->id,
            'service_id' => $service->id,
            'service_type' => $service->slug,
            'property_type' => 'house',
            'rooms' => 1,
            'bathrooms' => 1,
            'floor_area' => 30,
            'add_ons' => [],
            'barangay' => 'Poblacion',
            'street_address' => '999 Other Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00',
            'duration_minutes' => 120,
            'price' => 2850,
            'base_price' => 0,
            'property_fee' => 0,
            'rooms_fee' => 0,
            'bathrooms_fee' => 0,
            'floor_area_fee' => 2850,
            'add_ons_fee' => 0,
            'payment_method' => 'on_site_cash',
            'payment_status' => 'pending',
            'service_plan' => 'one_time',
            'status' => 'pending',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/bookings')
            ->assertOk()
            ->assertJsonCount(1, 'bookings')
            ->assertJsonPath('bookings.0.street_address', '123 Sample Street');
    }

    public function test_staff_account_cannot_create_client_booking(): void
    {
        $this->service();
        User::factory()->create([
            'email' => 'mobile-staff@example.com',
            'password' => Hash::make('Password123'),
            'role' => 'staff',
        ]);

        $token = $this->loginToken('mobile-staff@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/bookings', $this->validPayload())
            ->assertForbidden();
    }

    public function test_mobile_client_can_cancel_an_unassigned_pending_booking(): void
    {
        $client = User::factory()->create(['email' => 'cancel-mobile@example.com', 'password' => Hash::make('Password123')]);
        $booking = Booking::factory()->create(['user_id' => $client->id, 'status' => 'pending', 'staff_id' => null]);
        $token = $this->loginToken('cancel-mobile@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/bookings/'.$booking->id.'/cancel')
            ->assertOk()
            ->assertJsonPath('booking.status', 'cancelled');
    }

    public function test_mobile_client_can_reschedule_own_booking(): void
    {
        $client = User::factory()->create(['email' => 'reschedule-mobile@example.com', 'password' => Hash::make('Password123')]);
        $booking = Booking::factory()->create(['user_id' => $client->id, 'status' => 'pending', 'scheduled_time' => '08:00']);
        $token = $this->loginToken('reschedule-mobile@example.com');
        $date = now()->addDays(3)->toDateString();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/bookings/'.$booking->id.'/reschedule', ['scheduled_date' => $date, 'scheduled_time' => '10:00'])
            ->assertOk()
            ->assertJsonPath('booking.scheduled_date', $date)
            ->assertJsonPath('booking.scheduled_time', '10:00');
    }

    public function test_mobile_client_can_rate_completed_booking(): void
    {
        $client = User::factory()->create(['email' => 'rate-mobile@example.com', 'password' => Hash::make('Password123')]);
        $staff = User::factory()->create(['role' => 'staff']);
        $booking = Booking::factory()->create(['user_id' => $client->id, 'staff_id' => $staff->id, 'status' => 'completed']);
        $token = $this->loginToken('rate-mobile@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/bookings/'.$booking->id.'/rate', ['stars' => 5, 'comment' => 'Great service.'])
            ->assertOk();

        $this->assertDatabaseHas('ratings', ['booking_id' => $booking->id, 'stars' => 5]);
    }

    public function test_mobile_client_can_open_a_dispute_for_an_eligible_booking(): void
    {
        $client = User::factory()->create(['email' => 'dispute-mobile@example.com', 'password' => Hash::make('Password123')]);
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'status' => 'completed',
            'provider_payout_status' => 'pending',
            'provider_gross_amount' => 2850,
        ]);
        $token = $this->loginToken('dispute-mobile@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/bookings/'.$booking->id.'/dispute', [
                'dispute_reason' => 'incomplete_service',
                'dispute_description' => 'Several required cleaning tasks were not completed.',
            ])
            ->assertOk()
            ->assertJsonPath('booking.dispute_status', 'open');
    }

    public function test_mobile_client_cannot_manage_another_clients_booking(): void
    {
        $client = User::factory()->create(['email' => 'owner-mobile@example.com', 'password' => Hash::make('Password123')]);
        $otherClient = User::factory()->create();
        $booking = Booking::factory()->create(['user_id' => $otherClient->id, 'status' => 'pending']);
        $token = $this->loginToken('owner-mobile@example.com');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/bookings/'.$booking->id.'/cancel')
            ->assertForbidden();
    }

    private function service(): Service
    {
        return $this->canonicalService([
            'name' => 'Deep Clean',
            'slug' => 'deep',
            'price' => 95,
            'duration_minutes' => 120,
            'is_active' => true,
        ]);
    }

    private function mobileToken(): string
    {
        User::factory()->create([
            'email' => 'booking-client@example.com',
            'password' => Hash::make('Password123'),
        ]);

        return $this->loginToken('booking-client@example.com');
    }

    private function loginToken(string $email): string
    {
        return $this->postJson('/api/mobile/login', [
            'email' => $email,
            'password' => 'Password123',
        ])->json('token');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'service_type' => 'deep',
            'property_type' => 'house',
            'floor_area' => 30,
            'add_ons' => [],
            'payment_method' => 'on_site_cash',
            'barangay' => 'Poblacion',
            'street_address' => '123 Sample Street',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '08:00',
        ], $overrides);
    }
}
