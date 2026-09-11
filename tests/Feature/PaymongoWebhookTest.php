<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
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

    public function test_paymongo_webhook_recreates_a_missing_payment_with_the_booking_channel(): void
    {
        Config::set('services.paymongo.webhook_secret', 'whsec_test_secret');

        $client = User::factory()->create(['role' => 'client']);
        $service = Service::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'payment_method' => 'maya',
            'payment_status' => 'pending',
        ]);
        $booking->payments()->delete();

        $payload = $this->paidPaymentPayload($booking->id, 'pay_missing_payment');
        $payload['data']['attributes']['data']['attributes']['source'] = ['type' => 'paymaya'];
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', '1710000000.'.$encodedPayload, 'whsec_test_secret');

        $this->withHeaders(['Paymongo-Signature' => "t=1710000000,te={$signature},li="])
            ->postJson(route('api.paymongo.webhook'), $payload)
            ->assertOk()
            ->assertJson(['status' => 'processed']);

        $this->assertSame('maya', $booking->fresh()->payment->method);
        $this->assertSame('paid', $booking->fresh()->payment->status);
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

    public function test_repeated_paymongo_paid_webhook_is_a_no_op(): void
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
        ]);

        $payload = json_encode($this->paidCheckoutPayload($booking->id), JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', '1710000000.'.$payload, 'whsec_test_secret');
        $headers = ['Paymongo-Signature' => "t=1710000000,te={$signature},li="];

        $this->withHeaders($headers)->postJson(route('api.paymongo.webhook'), json_decode($payload, true))->assertOk();
        $this->withHeaders($headers)->postJson(route('api.paymongo.webhook'), json_decode($payload, true))
            ->assertOk()
            ->assertJson(['status' => 'already_processed']);

        $this->assertSame(1, $booking->payments()->count());
        $this->assertSame(1, Notification::where('booking_id', $booking->id)->count());
    }

    public function test_late_paid_webhook_for_cancelled_booking_is_refunded(): void
    {
        Config::set('services.paymongo.webhook_secret', 'whsec_test_secret');
        Config::set('services.paymongo.secret_key', 'sk_test_secret');
        Config::set('services.paymongo.api_url', 'https://api.paymongo.test');
        Http::fake([
            'api.paymongo.test/v1/refunds' => Http::response([
                'data' => [
                    'id' => 'ref_late_payment',
                    'attributes' => ['status' => 'succeeded', 'amount' => 120000],
                ],
            ], 201),
        ]);

        $client = User::factory()->create(['role' => 'client']);
        $service = Service::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'cancelled',
            'payment_method' => 'gcash',
            'payment_status' => 'pending',
        ]);
        $booking->payment->forceFill([
            'method' => 'gcash',
            'status' => 'pending',
            'provider' => 'paymongo',
            'amount' => 1200,
        ])->save();

        $payload = $this->paidPaymentPayload($booking->id, 'pay_late_123');
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', '1710000000.'.$encodedPayload, 'whsec_test_secret');

        $response = $this
            ->withHeaders(['Paymongo-Signature' => "t=1710000000,te={$signature},li="])
            ->postJson(route('api.paymongo.webhook'), $payload);

        $response->assertOk()->assertJson(['status' => 'processed']);

        $payment = $booking->fresh()->payment;
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('refunded', $payment->status);
        $this->assertSame('succeeded', $payment->refund_status);
        $this->assertSame('pay_late_123', $payment->provider_payment_id);
        $this->assertSame('ref_late_payment', $payment->refund_reference);
    }

    public function test_late_paid_webhook_notifies_client_when_refund_fails_and_duplicate_is_ignored(): void
    {
        Config::set('services.paymongo.webhook_secret', 'whsec_test_secret');
        Config::set('services.paymongo.secret_key', 'sk_test_secret');
        Config::set('services.paymongo.api_url', 'https://api.paymongo.test');
        Http::fake([
            'api.paymongo.test/v1/refunds' => Http::response([
                'errors' => [['code' => 'payment_not_refundable']],
            ], 422),
        ]);

        $client = User::factory()->create(['role' => 'client']);
        $service = Service::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'status' => 'cancelled',
            'payment_method' => 'gcash',
            'payment_status' => 'pending',
        ]);
        $booking->payment->forceFill([
            'method' => 'gcash',
            'status' => 'pending',
            'provider' => 'paymongo',
            'amount' => 1200,
        ])->save();

        $payload = $this->paidPaymentPayload($booking->id, 'pay_late_failed');
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', '1710000000.'.$encodedPayload, 'whsec_test_secret');
        $headers = ['Paymongo-Signature' => "t=1710000000,te={$signature},li="];

        $this->withHeaders($headers)
            ->postJson(route('api.paymongo.webhook'), $payload)
            ->assertOk()
            ->assertJson(['status' => 'processed']);

        $this->assertSame('failed', $booking->fresh()->payment->refund_status);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $client->id,
            'booking_id' => $booking->id,
            'title' => 'Payment refund needs review',
            'type' => 'warning',
            'dedupe_key' => 'payment-refund-failed:'.$booking->id,
        ]);

        $this->withHeaders($headers)
            ->postJson(route('api.paymongo.webhook'), $payload)
            ->assertOk()
            ->assertJson(['status' => 'already_processed']);

        $this->assertSame(1, Notification::where('booking_id', $booking->id)->count());
    }

    public function test_refund_failure_webhook_notifies_client_once(): void
    {
        Config::set('services.paymongo.webhook_secret', 'whsec_test_secret');

        $client = User::factory()->create(['role' => 'client']);
        $service = Service::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'payment_method' => 'maya',
            'payment_status' => 'paid',
        ]);
        $booking->payment->forceFill([
            'method' => 'maya',
            'status' => 'paid',
            'provider' => 'paymongo',
            'provider_payment_id' => 'pay_refund_failed',
            'amount' => 1200,
            'refund_status' => 'processing',
            'refund_reference' => 'ref_failed_123',
        ])->save();

        $payload = [
            'data' => [
                'id' => 'evt_refund_failed',
                'type' => 'event',
                'attributes' => [
                    'type' => 'payment.refund.updated',
                    'livemode' => false,
                    'data' => [
                        'id' => 'ref_failed_123',
                        'type' => 'refund',
                        'attributes' => [
                            'payment_id' => 'pay_refund_failed',
                            'amount' => 120000,
                            'status' => 'failed',
                        ],
                    ],
                ],
            ],
        ];
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', '1710000000.'.$encodedPayload, 'whsec_test_secret');
        $headers = ['Paymongo-Signature' => "t=1710000000,te={$signature},li="];

        $this->withHeaders($headers)
            ->postJson(route('api.paymongo.webhook'), $payload)
            ->assertOk()
            ->assertJson(['status' => 'processed']);
        $this->withHeaders($headers)
            ->postJson(route('api.paymongo.webhook'), $payload)
            ->assertOk()
            ->assertJson(['status' => 'processed']);

        $this->assertSame('failed', $booking->fresh()->payment->refund_status);
        $this->assertSame(1, Notification::where('booking_id', $booking->id)->count());
    }

    public function test_paymongo_refund_webhook_marks_payment_refunded(): void
    {
        Config::set('services.paymongo.webhook_secret', 'whsec_test_secret');

        $client = User::factory()->create(['role' => 'client']);
        $service = Service::factory()->create();
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'payment_method' => 'maya',
            'payment_status' => 'paid',
        ]);
        $booking->payment->forceFill([
            'method' => 'maya',
            'status' => 'paid',
            'provider' => 'paymongo',
            'provider_payment_id' => 'pay_refund_webhook',
            'amount' => 1200,
            'refund_status' => 'pending',
            'refund_reference' => 'ref_webhook_123',
        ])->save();

        $payload = [
            'data' => [
                'id' => 'evt_refund_updated',
                'type' => 'event',
                'attributes' => [
                    'type' => 'payment.refund.updated',
                    'livemode' => false,
                    'data' => [
                        'id' => 'ref_webhook_123',
                        'type' => 'refund',
                        'attributes' => [
                            'payment_id' => 'pay_refund_webhook',
                            'amount' => 120000,
                            'status' => 'succeeded',
                        ],
                    ],
                ],
            ],
        ];
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', '1710000000.'.$encodedPayload, 'whsec_test_secret');

        $response = $this
            ->withHeaders(['Paymongo-Signature' => "t=1710000000,te={$signature},li="])
            ->postJson(route('api.paymongo.webhook'), $payload);

        $response->assertOk()->assertJson(['status' => 'processed']);
        $this->assertSame('refunded', $booking->fresh()->payment->status);
        $this->assertSame('succeeded', $booking->fresh()->payment->refund_status);
    }

    public function test_paymongo_refund_webhook_uses_refund_id_when_payment_id_is_shared(): void
    {
        Config::set('services.paymongo.webhook_secret', 'whsec_test_secret');

        $client = User::factory()->create(['role' => 'client']);
        $service = Service::factory()->create();
        $firstBooking = Booking::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'payment_method' => 'gcash',
            'payment_status' => 'paid',
        ]);
        $secondBooking = Booking::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'payment_method' => 'gcash',
            'payment_status' => 'paid',
        ]);

        $firstBooking->payment->forceFill([
            'method' => 'gcash',
            'status' => 'paid',
            'provider' => 'paymongo',
            'provider_payment_id' => 'pay_shared_subscription',
            'refund_status' => 'pending',
            'refund_reference' => 'ref_first_occurrence',
        ])->save();
        $secondBooking->payment->forceFill([
            'method' => 'gcash',
            'status' => 'paid',
            'provider' => 'paymongo',
            'provider_payment_id' => 'pay_shared_subscription',
            'refund_status' => 'pending',
            'refund_reference' => 'ref_second_occurrence',
        ])->save();

        $payload = [
            'data' => [
                'id' => 'evt_shared_refund_updated',
                'type' => 'event',
                'attributes' => [
                    'type' => 'payment.refund.updated',
                    'livemode' => false,
                    'data' => [
                        'id' => 'ref_second_occurrence',
                        'type' => 'refund',
                        'attributes' => [
                            'payment_id' => 'pay_shared_subscription',
                            'amount' => 120000,
                            'status' => 'succeeded',
                        ],
                    ],
                ],
            ],
        ];
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', '1710000000.'.$encodedPayload, 'whsec_test_secret');

        $this->withHeaders(['Paymongo-Signature' => "t=1710000000,te={$signature},li="])
            ->postJson(route('api.paymongo.webhook'), $payload)
            ->assertOk()
            ->assertJson(['status' => 'processed']);

        $this->assertSame('pending', $firstBooking->fresh()->payment->refund_status);
        $this->assertSame('paid', $firstBooking->fresh()->payment->status);
        $this->assertSame('succeeded', $secondBooking->fresh()->payment->refund_status);
        $this->assertSame('refunded', $secondBooking->fresh()->payment->status);
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

    private function paidPaymentPayload(int $bookingId, string $paymentId): array
    {
        return [
            'data' => [
                'id' => 'evt_test_payment_paid',
                'type' => 'event',
                'attributes' => [
                    'type' => 'payment.paid',
                    'livemode' => false,
                    'data' => [
                        'id' => $paymentId,
                        'type' => 'payment',
                        'attributes' => [
                            'reference_number' => 'PM-LATE-123',
                            'metadata' => ['booking_id' => (string) $bookingId],
                        ],
                    ],
                ],
            ],
        ];
    }
}
