<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingServiceProofAccessTest extends TestCase
{
    public function test_authenticated_booking_participants_can_view_private_proof_media(): void
    {
        [$client, $staff, $booking, $proof] = $this->bookingWithProof();

        foreach ([$client, $staff] as $user) {
            $response = $this->actingAs($user)
                ->get(route('bookings.service-proof', [$booking, $proof]));

            $response->assertOk();
            $response->assertHeader('Content-Disposition', 'inline; filename=proof.jpg');
            $this->assertSame('private proof', $response->streamedContent());
        }
    }

    public function test_unrelated_users_and_mismatched_proofs_cannot_view_booking_proof_media(): void
    {
        [$client, $staff, $booking, $proof] = $this->bookingWithProof();
        $unrelatedClient = User::factory()->create(['role' => 'client']);
        $otherBooking = Booking::factory()->create([
            'user_id' => $unrelatedClient->id,
            'staff_id' => null,
        ]);
        $otherProof = $otherBooking->serviceProofs()->create([
            'uploaded_by' => $staff->id,
            'stage' => 'before',
            'media_type' => 'image',
            'file_path' => 'booking-proofs/before/other.jpg',
            'original_name' => 'other.jpg',
        ]);

        $this->actingAs($unrelatedClient)
            ->get(route('bookings.service-proof', [$booking, $proof]))
            ->assertForbidden();

        $this->actingAs($client)
            ->get(route('bookings.service-proof', [$booking, $otherProof]))
            ->assertNotFound();
    }

    public function test_only_booking_participants_can_view_private_rating_photos(): void
    {
        [$client, $staff, $booking] = $this->bookingWithProof();
        $ratingPath = 'ratings/review.jpg';
        Storage::disk('proof-test')->put($ratingPath, 'private rating');
        $booking->rating()->create([
            'client_id' => $client->id,
            'staff_id' => $staff->id,
            'stars' => 5,
            'comment' => 'Great service',
            'photo' => $ratingPath,
        ]);

        $this->actingAs($client)
            ->get(route('bookings.rating-photo', $booking))
            ->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'client']))
            ->get(route('bookings.rating-photo', $booking))
            ->assertForbidden();
    }

    private function bookingWithProof(): array
    {
        $client = User::factory()->create(['role' => 'client']);
        $staff = User::factory()->create(['role' => 'staff']);
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'status' => 'completed',
        ]);

        Config::set('filesystems.proof_uploads_disk', 'proof-test');
        Storage::fake('proof-test');
        Storage::disk('proof-test')->put('booking-proofs/before/proof.jpg', 'private proof');

        $proof = $booking->serviceProofs()->create([
            'uploaded_by' => $staff->id,
            'stage' => 'before',
            'media_type' => 'image',
            'file_path' => 'booking-proofs/before/proof.jpg',
            'original_name' => 'proof.jpg',
        ]);

        return [$client, $staff, $booking, $proof];
    }
}
