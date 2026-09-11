<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Tests\TestCase;

class BookingWebVerificationAccessTest extends TestCase
{
    public function test_unverified_client_cannot_view_booking_details_by_direct_url(): void
    {
        $client = User::factory()->unverified()->create(['role' => 'client']);
        $booking = Booking::factory()->create(['user_id' => $client->id]);

        $this->actingAs($client)
            ->get(route('bookings.show', $booking->id))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('error', 'Please verify your email before viewing booking details.');
    }

    public function test_unverified_client_cannot_send_booking_messages_by_direct_url(): void
    {
        $client = User::factory()->unverified()->create(['role' => 'client']);
        $staff = User::factory()->create(['role' => 'staff']);
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'status' => 'confirmed',
        ]);

        $this->actingAs($client)
            ->post(route('bookings.messages.store', $booking), [
                'message' => 'Please confirm the arrival time.',
            ])
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('error', 'Please verify your email before sending booking messages.');
    }

    public function test_unverified_client_cannot_view_booking_receipt_or_payment_document(): void
    {
        $client = User::factory()->unverified()->create(['role' => 'client']);
        $booking = Booking::factory()->create(['user_id' => $client->id]);

        $this->actingAs($client)
            ->get(route('bookings.receipt', $booking->id))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('error', 'Please verify your email before viewing booking receipts.');

        $this->actingAs($client)
            ->get(route('bookings.cash-payment-proof.download', $booking->id))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('error', 'Please verify your email before viewing payment documents.');
    }
}
