<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingServiceProof;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileBookingDetailsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_fetch_booking_details_and_authenticated_service_proof_media(): void
    {
        Storage::fake('local');
        $client = $this->user('mobile-details-client@example.com');
        $staff = $this->user('mobile-details-staff@example.com', 'staff');
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'status' => 'completed',
        ]);
        $proofPath = 'booking-proofs/after/after.jpg';
        Storage::disk('local')->put($proofPath, 'proof image');
        $proof = BookingServiceProof::create([
            'booking_id' => $booking->id,
            'uploaded_by' => $staff->id,
            'stage' => 'after',
            'media_type' => 'image',
            'file_path' => $proofPath,
            'original_name' => 'after.jpg',
        ]);

        $token = $this->loginToken($client->email);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/mobile/bookings/{$booking->id}/details")
            ->assertOk()
            ->assertJsonPath('booking.id', $booking->id)
            ->assertJsonPath('booking.proofs.0.original_name', 'after.jpg')
            ->assertJsonPath('booking.proofs.0.media_url', route('api.mobile.booking.proof', [$booking, $proof]));

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get("/api/mobile/bookings/{$booking->id}/proofs/{$proof->id}")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $otherClient = $this->user('mobile-details-other@example.com');
        $otherToken = $this->loginToken($otherClient->email);
        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->getJson("/api/mobile/bookings/{$booking->id}/details")
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->get("/api/mobile/bookings/{$booking->id}/proofs/{$proof->id}")
            ->assertForbidden();
    }

    public function test_client_and_assigned_staff_can_send_booking_messages(): void
    {
        $client = $this->user('mobile-message-client@example.com');
        $staff = $this->user('mobile-message-staff@example.com', 'staff');
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'status' => 'confirmed',
        ]);

        $clientToken = $this->loginToken($client->email);
        $this->withHeader('Authorization', 'Bearer '.$clientToken)
            ->postJson("/api/mobile/bookings/{$booking->id}/messages", ['message' => 'Please confirm the arrival time.'])
            ->assertCreated()
            ->assertJsonPath('booking_message.message', 'Please confirm the arrival time.');

        $staffToken = $this->loginToken($staff->email);
        $this->withHeader('Authorization', 'Bearer '.$staffToken)
            ->postJson("/api/mobile/bookings/{$booking->id}/messages", ['message' => 'I will arrive at the scheduled time.'])
            ->assertCreated();

        $this->assertDatabaseCount('booking_messages', 2);
    }

    public function test_client_can_upload_mobile_cash_payment_proof(): void
    {
        Storage::fake('local');
        $client = $this->user('mobile-cash-proof@example.com');
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'status' => 'completed',
            'payment_method' => 'on_site_cash',
            'payment_status' => 'pending',
        ]);
        $payment = Payment::updateOrCreate([
            'booking_id' => $booking->id,
        ], [
            'method' => 'on_site_cash',
            'status' => 'pending',
            'amount' => 1200,
            'currency' => 'PHP',
            'provider' => 'manual',
        ]);

        $token = $this->loginToken($client->email);
        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->post("/api/mobile/bookings/{$booking->id}/cash-payment-proof", [
            'cash_payment_proof' => UploadedFile::fake()->create('cash-receipt.jpg', 100, 'image/jpeg'),
        ])->assertOk();

        $payment = $payment->fresh();
        $this->assertSame('pending', $payment->cash_proof_status);
        Storage::disk('local')->assertExists($payment->cash_proof_path);
        $this->assertDatabaseHas('booking_activity_logs', [
            'booking_id' => $booking->id,
            'actor_id' => $client->id,
            'action' => 'cash_payment_proof_submitted',
        ]);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get("/api/mobile/bookings/{$booking->id}/cash-payment-proof")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_client_can_submit_mobile_rating_with_photo(): void
    {
        Storage::fake('local');
        $client = $this->user('mobile-rating-photo@example.com');
        $staff = $this->user('mobile-rating-photo-staff@example.com', 'staff');
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'staff_id' => $staff->id,
            'status' => 'completed',
        ]);

        $token = $this->loginToken($client->email);
        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->post("/api/mobile/bookings/{$booking->id}/rate", [
            'stars' => 5,
            'comment' => 'The service was excellent.',
            'photo' => UploadedFile::fake()->create('review.jpg', 100, 'image/jpeg'),
        ])->assertOk();

        $rating = $booking->fresh()->rating;
        $this->assertNotNull($rating?->photo);
        Storage::disk('local')->assertExists($rating->photo);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/mobile/bookings/{$booking->id}/details")
            ->assertJsonPath('booking.rating.stars', 5)
            ->assertJsonPath('booking.rating.photo_available', true);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->get("/api/mobile/bookings/{$booking->id}/rating-photo")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    private function user(string $email, string $role = 'client'): User
    {
        return User::factory()->create([
            'email' => $email,
            'password' => Hash::make('Password123'),
            'role' => $role,
        ]);
    }

    private function loginToken(string $email): string
    {
        return $this->postJson('/api/mobile/login', [
            'email' => $email,
            'password' => 'Password123',
        ])->json('token');
    }
}
