<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BookingScheduleTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_booking_form_starts_at_eight_am(): void
    {
        $client = $this->client();
        $this->service();

        $response = $this->actingAs($client)->get(route('bookings.create'));

        $response->assertOk();
        $response->assertDontSee('value="07:00"', false);
        $response->assertSee('value="08:00"', false);
        $response->assertSee('Available start times: 8:00 AM - 4:00 PM (Asia/Manila).', false);
        foreach (Booking::bookingTimeSlots() as $timeSlot) {
            $response->assertSee('value="'.$timeSlot.'"', false);
        }
    }

    public function test_web_booking_rejects_a_start_time_before_business_hours(): void
    {
        $client = $this->client();
        $this->service();

        $this->actingAs($client)
            ->from(route('bookings.create'))
            ->post(route('bookings.store'), [
                'service_type' => 'basic',
                'property_type' => 'house',
                'rooms' => 1,
                'bathrooms' => 1,
                'floor_area' => 30,
                'barangay' => 'Poblacion',
                'street_address' => '123 Rizal Street',
                'scheduled_date' => now()->addDays(3)->toDateString(),
                'scheduled_time' => '07:00',
                'payment_method' => 'on_site_cash',
                'service_plan' => 'one_time',
            ])
            ->assertRedirect(route('bookings.create'))
            ->assertSessionHasErrors('scheduled_time');
    }

    public function test_mobile_booking_rejects_a_start_time_before_business_hours(): void
    {
        $client = $this->client(['email' => 'mobile-before-hours@example.com', 'username' => 'mobilebeforehours']);
        $service = $this->service();
        $token = $this->postJson('/api/mobile/login', [
            'email' => $client->email,
            'password' => 'Password123',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/bookings', [
                'service_type' => $service->slug,
                'property_type' => 'house',
                'floor_area' => 30,
                'add_ons' => [],
                'payment_method' => 'on_site_cash',
                'barangay' => 'Poblacion',
                'street_address' => '123 Sample Street',
                'scheduled_date' => now()->addDay()->toDateString(),
                'scheduled_time' => '07:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('scheduled_time');
    }

    private function client(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'first_name' => 'Schedule',
            'last_name' => 'Client',
            'email' => 'schedule-client@example.com',
            'password' => Hash::make('Password123'),
            'phone' => '09171234567',
            'date_of_birth' => '2000-01-01',
            'gender' => 'female',
            'street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Valencia City',
            'zip_code' => '8709',
            'username' => 'scheduleclient',
            'role' => 'client',
        ], $overrides));
    }

    private function service(): Service
    {
        return Service::updateOrCreate(['slug' => 'basic'], [
            'name' => 'Basic Clean',
            'description' => 'Routine cleaning',
            'price' => 570,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);
    }
}
