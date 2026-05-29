<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PaymongoWebhookTest extends TestCase
{
    public function test_valid_paymongo_paid_webhook_marks_booking_as_paid(): void
    {
        Config::set('services.paymongo.webhook_secret', 'whsec_test_secret');

        $client = User::factory()->create(['role' => 'client']);
        $service = Service::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'payment_method' => 'gcash',
            'payment_status' => 'pending',
            'payment_reference' => null,
            'paid_at' => null,
        ]);

        $payload = json_encode($this->paidCheckoutPayload($booking->id), JSON_UNESCAPED_SLASHES);
        $timestamp = '1710000000';
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test_secret');

        $response = $this
            ->withHeaders(['Paymongo-Signature' => "t={$timestamp},te={$signature},li="])
            ->postJson(route('api.paymongo.webhook'), json_decode($payload, true));

        $response->assertOk()->assertJson(['status' => 'processed']);

        $booking->refresh();

        $this->assertSame('paid', $booking->payment_status);
        $this->assertSame('PM-REF-123', $booking->payment_reference);
        $this->assertNotNull($booking->paid_at);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $client->id,
            'booking_id' => $booking->id,
            'title' => 'Payment confirmed',
        ]);
    }

    public function test_paymongo_webhook_rejects_invalid_signature(): void
    {
        Config::set('services.paymongo.webhook_secret', 'whsec_test_secret');

        $booking = Booking::factory()->create([
            'payment_method' => 'gcash',
            'payment_status' => 'pending',
        ]);

        $response = $this
            ->withHeaders(['Paymongo-Signature' => 't=1710000000,te=bad-signature,li='])
            ->postJson(route('api.paymongo.webhook'), $this->paidCheckoutPayload($booking->id));

        $response->assertUnauthorized();

        $this->assertSame('pending', $booking->fresh()->payment_status);
        $this->assertSame(0, Notification::count());
    }

    private function paidCheckoutPayload(int $bookingId): array
    {
        return [
            'data' => [
                'id' => 'evt_test_paid',
                'type' => 'event',
                'attributes' => [
                    'type' => 'checkout_session.payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => 'cs_test_123',
                        'type' => 'checkout_session',
                        'attributes' => [
                            'reference_number' => 'PM-REF-123',
                            'metadata' => [
                                'booking_id' => (string) $bookingId,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
