<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingLiveVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_staff_can_create_and_join_live_video_room(): void
    {
        [$client, $staff, $booking] = $this->inProgressBooking();

        config([
            'services.daily.api_key' => 'test-daily-key',
            'services.daily.domain' => 'cleanflow-test',
        ]);

        Http::fake([
            'https://api.daily.co/v1/rooms' => Http::response([
                'url' => 'https://cleanflow-test.daily.co/cf-booking-room',
            ]),
            'https://api.daily.co/v1/meeting-tokens' => Http::response([
                'token' => 'staff-token',
            ]),
        ]);

        $response = $this->actingAs($staff)->get(route('bookings.live-video', $booking));

        $response->assertOk();
        $response->assertSee('staff-token');
        $response->assertSee('cleanflow-test.daily.co');

        $booking->refresh();
        $this->assertNotNull($booking->daily_room_name);
        $this->assertNotNull($booking->daily_room_expires_at);
        $this->assertNotNull($booking->live_video_started_at);

        Http::assertSentCount(2);
        $this->assertSame($client->id, $booking->user_id);
    }

    public function test_booking_client_can_join_existing_live_video_room_as_viewer(): void
    {
        [$client, , $booking] = $this->inProgressBooking([
            'daily_room_name' => 'cf-existing-room',
            'daily_room_url' => 'https://cleanflow-test.daily.co/cf-existing-room',
            'daily_room_expires_at' => now()->addHour(),
            'live_video_started_at' => now()->subMinutes(5),
        ]);

        config(['services.daily.api_key' => 'test-daily-key']);

        Http::fake([
            'https://api.daily.co/v1/meeting-tokens' => Http::response([
                'token' => 'client-token',
            ]),
        ]);

        $response = $this->actingAs($client)->get(route('bookings.live-video', $booking));

        $response->assertOk();
        $response->assertSee('Viewer mode is enabled');
        $response->assertSee('client-token');

        Http::assertSentCount(1);
    }

    public function test_client_is_redirected_when_staff_has_not_started_live_video_room(): void
    {
        [$client, , $booking] = $this->inProgressBooking();

        config(['services.daily.api_key' => 'test-daily-key']);

        $response = $this->actingAs($client)->get(route('bookings.live-video', $booking));

        $response->assertRedirect(route('bookings.show', $booking->id));
        $response->assertSessionHas('info', 'The cleaner has not started the live video room yet.');

        Http::assertNothingSent();
    }

    public function test_unassigned_staff_cannot_open_live_video_room(): void
    {
        [, , $booking] = $this->inProgressBooking();
        $otherStaff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($otherStaff)->get(route('bookings.live-video', $booking));

        $response->assertForbidden();
    }

    public function test_assigned_staff_can_end_live_video_room(): void
    {
        [, $staff, $booking] = $this->inProgressBooking([
            'daily_room_name' => 'cf-existing-room',
            'daily_room_url' => 'https://cleanflow-test.daily.co/cf-existing-room',
            'daily_room_expires_at' => now()->addHour(),
            'live_video_started_at' => now()->subMinutes(10),
        ]);

        config(['services.daily.api_key' => 'test-daily-key']);

        Http::fake([
            'https://api.daily.co/v1/rooms/cf-existing-room' => Http::response([]),
        ]);

        $response = $this->actingAs($staff)->delete(route('bookings.live-video.end', $booking));

        $response->assertRedirect(route('bookings.show', $booking->id));
        $response->assertSessionHas('success', 'Live video has been ended for this booking.');

        $booking->refresh();
        $this->assertNull($booking->daily_room_name);
        $this->assertNull($booking->daily_room_url);
        $this->assertNotNull($booking->live_video_ended_at);

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && $request->url() === 'https://api.daily.co/v1/rooms/cf-existing-room');
    }

    public function test_client_cannot_end_live_video_room(): void
    {
        [$client, , $booking] = $this->inProgressBooking([
            'daily_room_name' => 'cf-existing-room',
            'daily_room_url' => 'https://cleanflow-test.daily.co/cf-existing-room',
            'daily_room_expires_at' => now()->addHour(),
            'live_video_started_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($client)->delete(route('bookings.live-video.end', $booking));

        $response->assertForbidden();
    }

    private function inProgressBooking(array $overrides = []): array
    {
        $client = User::factory()->create(['role' => 'client']);
        $staff = User::factory()->create(['role' => 'staff']);
        $service = $this->canonicalService(['slug' => 'basic-clean', 'name' => 'Basic Clean']);

        $booking = Booking::factory()->create(array_merge([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'service_type' => 'basic-clean',
            'property_type' => 'house',
            'status' => 'in_progress',
            'scheduled_date' => now()->toDateString(),
            'scheduled_time' => '09:00:00',
            'barangay' => 'Poblacion',
            'street_address' => 'Rizal Street',
            'price' => 1750,
        ], $overrides));

        return [$client, $staff, $booking];
    }
}
