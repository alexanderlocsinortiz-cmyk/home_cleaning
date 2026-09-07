<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CashPaymentProofWorkflowTest extends TestCase
{
    public function test_client_can_upload_private_cash_payment_proof_and_admin_is_notified(): void
    {
        Storage::fake('local');
        $client = User::factory()->create(['role' => 'client']);
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->cashBooking($client);

        $response = $this->actingAs($client)->post(route('bookings.cash-payment-proof.upload', $booking->id), [
            'cash_payment_proof' => UploadedFile::fake()->create('cash-receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $payment = $booking->fresh()->payment;

        $this->assertSame('pending', $payment->status);
        $this->assertSame('pending', $payment->cash_proof_status);
        $this->assertNotNull($payment->cash_proof_path);
        Storage::disk('local')->assertExists($payment->cash_proof_path);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'booking_id' => $booking->id,
            'title' => 'Cash payment proof submitted',
        ]);
    }

    public function test_client_cannot_upload_proof_to_another_clients_booking(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['role' => 'client']);
        $otherClient = User::factory()->create(['role' => 'client']);
        $booking = $this->cashBooking($owner);

        $response = $this->actingAs($otherClient)->post(route('bookings.cash-payment-proof.upload', $booking->id), [
            'cash_payment_proof' => UploadedFile::fake()->create('cash-receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertNotFound();
        $this->assertNull($booking->fresh()->payment->cash_proof_path);
    }

    public function test_client_cannot_submit_cash_proof_before_service_completion(): void
    {
        Storage::fake('local');
        $client = User::factory()->create(['role' => 'client']);
        $booking = $this->cashBooking($client);
        $booking->forceFill(['status' => 'confirmed'])->save();

        $response = $this->actingAs($client)->post(route('bookings.cash-payment-proof.upload', $booking->id), [
            'cash_payment_proof' => UploadedFile::fake()->create('cash-receipt.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('cash_payment_proof');
        $this->assertNull($booking->fresh()->payment->cash_proof_path);
    }

    public function test_admin_can_approve_pending_cash_proof_and_mark_payment_paid(): void
    {
        Storage::fake('local');
        $client = User::factory()->create(['role' => 'client']);
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->cashBooking($client);

        $this->actingAs($client)->post(route('bookings.cash-payment-proof.upload', $booking->id), [
            'cash_payment_proof' => UploadedFile::fake()->create('cash-receipt.jpg', 100, 'image/jpeg'),
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.bookings.cash-payment-proof.review', $booking->id), [
            'decision' => 'approve',
            'payment_collected_amount' => '1200.00',
            'payment_collected_at' => '2026-09-04 12:30:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Cash payment proof approved and payment marked as paid.');
        $payment = $booking->fresh()->payment;

        $this->assertSame('paid', $payment->status);
        $this->assertSame('approved', $payment->cash_proof_status);
        $this->assertSame('1200.00', number_format((float) $payment->collected_amount, 2, '.', ''));
        $this->assertSame($admin->id, $payment->cash_proof_reviewed_by);
        $this->assertNotNull($payment->receipt_number);
        $this->assertDatabaseHas('booking_activity_logs', [
            'booking_id' => $booking->id,
            'actor_id' => $admin->id,
            'action' => 'cash_payment_proof_reviewed',
        ]);
    }

    public function test_admin_rejection_keeps_payment_pending_and_client_can_resubmit(): void
    {
        Storage::fake('local');
        $client = User::factory()->create(['role' => 'client']);
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = $this->cashBooking($client);

        $this->actingAs($client)->post(route('bookings.cash-payment-proof.upload', $booking->id), [
            'cash_payment_proof' => UploadedFile::fake()->create('old-receipt.jpg', 100, 'image/jpeg'),
        ]);

        $this->actingAs($admin)->patch(route('admin.bookings.cash-payment-proof.review', $booking->id), [
            'decision' => 'reject',
            'cash_proof_rejection_reason' => 'The receipt amount is not readable.',
        ])->assertSessionHas('success', 'Cash payment proof rejected. The client can upload a replacement.');

        $rejectedPayment = $booking->fresh()->payment;
        $this->assertSame('pending', $rejectedPayment->status);
        $this->assertSame('rejected', $rejectedPayment->cash_proof_status);

        $this->actingAs($client)->post(route('bookings.cash-payment-proof.upload', $booking->id), [
            'cash_payment_proof' => UploadedFile::fake()->create('new-receipt.jpg', 100, 'image/jpeg'),
        ])->assertSessionHas('success');

        $this->assertSame('pending', $booking->fresh()->payment->cash_proof_status);
    }

    private function cashBooking(User $client): Booking
    {
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'status' => 'completed',
            'payment_method' => 'on_site_cash',
            'payment_status' => 'pending',
        ]);
        $booking->forceFill(['price' => 1200])->save();

        Payment::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'method' => 'on_site_cash',
                'status' => 'pending',
                'amount' => 1200,
                'currency' => 'PHP',
                'provider' => 'manual',
            ]
        );

        return $booking->fresh();
    }
}
