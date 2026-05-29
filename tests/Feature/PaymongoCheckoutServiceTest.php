<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Services\PaymongoCheckoutService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymongoCheckoutServiceTest extends TestCase
{
    public function test_it_creates_a_paymongo_checkout_session_for_a_digital_booking(): void
    {
        Config::set('services.paymongo.secret_key', 'sk_test_secret');
        Config::set('services.paymongo.api_url', 'https://api.paymongo.test');

        Http::fake([
            'api.paymongo.test/v1/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => [
                        'checkout_url' => 'https://checkout.paymongo.test/cs_test_123',
                    ],
                ],
            ]),
        ]);

        $client = User::factory()->create([
            'role' => 'client',
            'phone' => '09171234567',
        ]);
        $service = Service::factory()->create([
            'name' => 'Basic Clean',
            'slug' => 'basic',
            'price' => 570,
        ]);
        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'service_id' => $service->id,
            'service_type' => 'basic',
            'payment_method' => 'gcash',
            'price' => 570,
            'service_plan' => 'one_time',
        ]);

        $checkoutUrl = app(PaymongoCheckoutService::class)->createCheckoutUrl(collect([$booking]), $client);

        $this->assertSame('https://checkout.paymongo.test/cs_test_123', $checkoutUrl);

        Http::assertSent(function ($request) use ($booking, $client): bool {
            $payload = $request->data();

            return $request->url() === 'https://api.paymongo.test/v1/checkout_sessions'
                && $payload['data']['attributes']['metadata']['booking_id'] === (string) $booking->id
                && $payload['data']['attributes']['metadata']['client_id'] === (string) $client->id
                && $payload['data']['attributes']['payment_method_types'] === ['gcash']
                && $payload['data']['attributes']['line_items'][0]['amount'] === 57000;
        });
    }
}
