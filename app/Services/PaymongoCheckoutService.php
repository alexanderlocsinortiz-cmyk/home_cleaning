<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaymongoCheckoutService
{
    public function createCheckoutSession(Collection $bookings, User $user): array
    {
        $response = $this->sendCheckoutRequest($bookings, $user);

        $checkoutUrl = $response['data']['attributes']['checkout_url'] ?? null;
        $checkoutSessionId = $response['data']['id'] ?? null;

        if (! is_string($checkoutUrl) || $checkoutUrl === '') {
            throw new RuntimeException('PayMongo did not return a checkout URL.');
        }

        if (! is_string($checkoutSessionId) || $checkoutSessionId === '') {
            throw new RuntimeException('PayMongo did not return a checkout session ID.');
        }

        return [
            'id' => $checkoutSessionId,
            'checkout_url' => $checkoutUrl,
            'payload' => $response,
        ];
    }

    public function createCheckoutUrl(Collection $bookings, User $user): string
    {
        return $this->createCheckoutSession($bookings, $user)['checkout_url'];
    }

    public function retrieveCheckoutSession(string $checkoutSessionId): array
    {
        $secretKey = config('services.paymongo.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            throw new RuntimeException('PayMongo secret key is not configured.');
        }

        $request = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->connectTimeout((int) config('services.paymongo.connect_timeout_seconds', 5))
            ->timeout((int) config('services.paymongo.timeout_seconds', 10))
            ->retry(
                (int) config('services.paymongo.retry_times', 2),
                (int) config('services.paymongo.retry_sleep_ms', 250)
            );

        $caBundle = config('services.paymongo.ca_bundle');

        if (is_string($caBundle) && $caBundle !== '' && is_file($caBundle)) {
            $request = $request->withOptions(['verify' => $caBundle]);
        }

        if ((bool) config('services.paymongo.disable_proxy', true)) {
            $request = $request->withOptions(['proxy' => '']);
        }

        $response = $request->get(rtrim((string) config('services.paymongo.api_url'), '/').'/v1/checkout_sessions/'.$checkoutSessionId);

        if ($response->failed()) {
            throw new RuntimeException('PayMongo checkout session lookup failed: '.$response->body());
        }

        return $response->json();
    }

    public function checkoutSessionIsPaid(array $checkoutSession): bool
    {
        $attributes = $checkoutSession['data']['attributes'] ?? [];

        if (($attributes['status'] ?? null) === 'paid' || ($attributes['status'] ?? null) === 'completed') {
            return true;
        }

        if (data_get($attributes, 'payment_intent.attributes.status') === 'succeeded') {
            return true;
        }

        $payments = collect(data_get($attributes, 'payments', []))
            ->merge(data_get($attributes, 'payment_intent.attributes.payments', []));

        return $payments->contains(function ($payment): bool {
            return in_array(data_get($payment, 'attributes.status'), ['paid', 'succeeded'], true);
        });
    }

    public function paymentReferenceFromCheckoutSession(array $checkoutSession): string
    {
        $attributes = $checkoutSession['data']['attributes'] ?? [];
        $payments = collect(data_get($attributes, 'payments', []))
            ->merge(data_get($attributes, 'payment_intent.attributes.payments', []));
        $payment = $payments->first();

        return data_get($payment, 'attributes.reference_number')
            ?: data_get($payment, 'id')
            ?: data_get($attributes, 'reference_number')
            ?: data_get($attributes, 'payment_intent.id')
            ?: data_get($checkoutSession, 'data.id')
            ?: Booking::generatePaymentReference('gcash');
    }

    private function sendCheckoutRequest(Collection $bookings, User $user): array
    {
        $secretKey = config('services.paymongo.secret_key');

        if (! is_string($secretKey) || $secretKey === '') {
            throw new RuntimeException('PayMongo secret key is not configured.');
        }

        /** @var \App\Models\Booking|null $firstBooking */
        $firstBooking = $bookings->first();

        if (! $firstBooking) {
            throw new RuntimeException('Cannot create a PayMongo checkout session without a booking.');
        }

        $request = Http::withBasicAuth($secretKey, '')
            ->acceptJson()
            ->connectTimeout((int) config('services.paymongo.connect_timeout_seconds', 5))
            ->timeout((int) config('services.paymongo.timeout_seconds', 10))
            ->retry(
                (int) config('services.paymongo.retry_times', 2),
                (int) config('services.paymongo.retry_sleep_ms', 250)
            );

        $caBundle = config('services.paymongo.ca_bundle');

        if (is_string($caBundle) && $caBundle !== '' && is_file($caBundle)) {
            $request = $request->withOptions(['verify' => $caBundle]);
        }

        if ((bool) config('services.paymongo.disable_proxy', true)) {
            $request = $request->withOptions(['proxy' => '']);
        }

        $response = $request->post(rtrim((string) config('services.paymongo.api_url'), '/').'/v1/checkout_sessions', [
            'data' => [
                'attributes' => [
                    'billing' => [
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ],
                    'cancel_url' => route('bookings.show', $firstBooking->id),
                    'description' => $this->description($bookings),
                    'line_items' => $this->lineItems($bookings),
                    'metadata' => [
                        'booking_id' => (string) $firstBooking->id,
                        'booking_ids' => $bookings->pluck('id')->implode(','),
                        'client_id' => (string) $user->id,
                        'service_plan' => (string) $firstBooking->service_plan,
                    ],
                    'payment_method_types' => $this->paymentMethodTypes($firstBooking->payment_method),
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'show_line_items' => true,
                    'success_url' => route('bookings.payment.return', $firstBooking->id),
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('PayMongo checkout session failed: '.$response->body());
        }

        return $response->json();
    }

    private function description(Collection $bookings): string
    {
        /** @var \App\Models\Booking $firstBooking */
        $firstBooking = $bookings->first();
        $bookingCode = 'CF-'.str_pad((string) $firstBooking->id, 5, '0', STR_PAD_LEFT);

        if ($bookings->count() === 1) {
            return 'CleanFlow booking '.$bookingCode;
        }

        return 'CleanFlow subscription starting '.$bookingCode.' ('.$bookings->count().' visits)';
    }

    private function lineItems(Collection $bookings): array
    {
        return $bookings
            ->map(function (Booking $booking): array {
                return [
                    'amount' => (int) round(((float) $booking->price) * 100),
                    'currency' => 'PHP',
                    'description' => $booking->service_label,
                    'name' => 'Cleaning service CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
                    'quantity' => 1,
                ];
            })
            ->values()
            ->all();
    }

    private function paymentMethodTypes(string $paymentMethod): array
    {
        return match ($paymentMethod) {
            'gcash' => ['gcash'],
            'maya' => [(string) config('services.paymongo.maya_method_type', 'paymaya')],
            default => ['gcash', (string) config('services.paymongo.maya_method_type', 'paymaya')],
        };
    }
}
