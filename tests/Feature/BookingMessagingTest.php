<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingMessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_client_can_message_assigned_staff(): void
    {
        [$client, $staff, $booking] = $this->bookingWithAssignedStaff();

        $response = $this->actingAs($client)->post(route('bookings.messages.store', $booking), [
            'message' => 'Please call when you arrive.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('booking_messages', [
            'booking_id' => $booking->id,
            'sender_id' => $client->id,
            'message' => 'Please call when you arrive.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $staff->id,
            'booking_id' => $booking->id,
            'title' => 'New booking message',
        ]);
    }

    public function test_assigned_staff_can_message_booking_client(): void
    {
        [$client, $staff, $booking] = $this->bookingWithAssignedStaff();

        $response = $this->actingAs($staff)->post(route('bookings.messages.store', $booking), [
            'message' => 'I am on the way.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('booking_messages', [
            'booking_id' => $booking->id,
            'sender_id' => $staff->id,
            'message' => 'I am on the way.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $client->id,
            'booking_id' => $booking->id,
            'title' => 'New booking message',
        ]);
    }

    public function test_unassigned_staff_cannot_message_booking(): void
    {
        [, , $booking] = $this->bookingWithAssignedStaff();
        $otherStaff = User::factory()->create(['role' => 'staff']);

        $response = $this->actingAs($otherStaff)->post(route('bookings.messages.store', $booking), [
            'message' => 'This should not be allowed.',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('booking_messages', 0);
    }

    public function test_client_cannot_message_booking_without_assigned_staff(): void
    {
        [$client, , $booking] = $this->bookingWithAssignedStaff(['staff_id' => null, 'status' => 'pending']);

        $response = $this->actingAs($client)->post(route('bookings.messages.store', $booking), [
            'message' => 'Any update?',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('booking_messages', 0);
    }

    private function bookingWithAssignedStaff(array $overrides = []): array
    {
        $client = User::factory()->create(['role' => 'client']);
        $staff = User::factory()->create(['role' => 'staff']);
        $service = Service::factory()->create(['slug' => 'basic', 'name' => 'Basic Clean']);

        $booking = Booking::factory()->create(array_merge([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'service_type' => 'basic',
            'property_type' => 'house',
            'status' => 'confirmed',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '09:00:00',
            'barangay' => 'Poblacion',
            'street_address' => 'Rizal Street',
            'price' => 500,
        ], $overrides));

        return [$client, $staff, $booking];
    }
}
