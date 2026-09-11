<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRescheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_reschedule_rejects_a_time_outside_the_available_booking_slots(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'status' => 'pending',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '08:00',
        ]);
        $newDate = now()->addDays(3)->toDateString();

        $response = $this->actingAs($client)
            ->from('/bookings')
            ->patch(route('bookings.reschedule', $booking->id), [
                'scheduled_date' => $newDate,
                'scheduled_time' => '23:59',
            ]);

        $response->assertRedirect('/bookings');
        $response->assertSessionHasErrors('scheduled_time');
        $this->assertSame($booking->scheduled_date->toDateString(), $booking->fresh()->scheduled_date->toDateString());
        $this->assertSame('08:00', $booking->fresh()->scheduled_time);
    }

    public function test_web_reschedule_checks_every_assigned_cleaner(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $primaryStaff = User::factory()->create(['role' => 'staff']);
        $secondaryStaff = User::factory()->create(['role' => 'staff']);
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'staff_id' => $primaryStaff->id,
            'required_cleaners' => 2,
            'status' => 'confirmed',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '08:00',
            'duration_minutes' => 60,
        ]);
        $booking->staffAssignments()->createMany([
            ['staff_id' => $primaryStaff->id, 'task_group' => 'general_cleaning'],
            ['staff_id' => $secondaryStaff->id, 'task_group' => 'bathroom_sanitation'],
        ]);

        Booking::factory()->create([
            'user_id' => User::factory()->create(['role' => 'client'])->id,
            'staff_id' => $secondaryStaff->id,
            'status' => 'confirmed',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '09:00',
            'duration_minutes' => 60,
        ]);

        $response = $this->actingAs($client)
            ->from('/bookings')
            ->patch(route('bookings.reschedule', $booking->id), [
                'scheduled_date' => now()->addDays(3)->toDateString(),
                'scheduled_time' => '09:00',
            ]);

        $response->assertRedirect('/bookings');
        $response->assertSessionHasErrors('scheduled_time');
        $this->assertSame('08:00', $booking->fresh()->scheduled_time);
    }
}
