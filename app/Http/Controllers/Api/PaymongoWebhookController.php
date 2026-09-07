<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymongoWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $event = json_decode($payload, true);

        if (! is_array($event)) {
            return response()->json(['message' => 'Invalid JSON payload.'], 400);
        }

        if (! $this->hasValidSignature($request, $payload, $event)) {
            return response()->json(['message' => 'Invalid PayMongo signature.'], 401);
        }

        $eventType = data_get($event, 'data.attributes.type');

        if (! in_array($eventType, ['checkout_session.payment.paid', 'payment.paid'], true)) {
            return response()->json(['status' => 'ignored']);
        }

        $bookings = $this->resolveBookings($event);

        if ($bookings->isEmpty()) {
            Log::warning('PayMongo paid webhook could not be matched to a booking.', [
                'event_id' => data_get($event, 'data.id'),
                'event_type' => $eventType,
                'resource_id' => data_get($event, 'data.attributes.data.id'),
            ]);

            return response()->json(['status' => 'unmatched'], 202);
        }

        $paymentReference = $this->resolvePaymentReference($event);

        $checkoutSessionId = data_get($event, 'data.attributes.data.id');
        $providerPaymentId = $eventType === 'payment.paid'
            ? data_get($event, 'data.attributes.data.id')
            : data_get($event, 'data.attributes.data.attributes.payment_intent_id');

        // PayMongo may retry a webhook. Once every matched booking already
        // carries this provider reference, the event has been applied and
        // there is no work left to repeat.
        if ($bookings->every(fn (Booking $booking): bool => $booking->payment?->status === 'paid'
            && $booking->payment?->reference === $paymentReference
        )) {
            return response()->json(['status' => 'already_processed']);
        }

        $bookings->each(function (Booking $booking) use ($paymentReference, $checkoutSessionId, $providerPaymentId): void {
            $payment = $booking->paymentOrCreate([
                'method' => 'gcash',
                'status' => 'pending',
                'amount' => $booking->price ?? 0,
                'currency' => 'PHP',
                'provider' => 'paymongo',
            ]);
            $wasPending = $payment->status !== 'paid';
            $shouldReplaceReference = ! $payment->reference || str_starts_with((string) $payment->reference, 'cs_');

            $payment->forceFill([
                'status' => 'paid',
                'reference' => $shouldReplaceReference ? $paymentReference : $payment->reference,
                'checkout_session_id' => $payment->checkout_session_id ?: (is_string($checkoutSessionId) && str_starts_with($checkoutSessionId, 'cs_') ? $checkoutSessionId : null),
                'paid_at' => $payment->paid_at ?: now(),
                'provider_payment_id' => is_string($providerPaymentId) && $providerPaymentId !== ''
                    ? $providerPaymentId
                    : $payment->provider_payment_id,
            ])->save();

            $booking->setRelation('payment', $payment);

            if ($wasPending) {
                $this->createPaymentNotification($booking);
            }
        });

        return response()->json(['status' => 'processed']);
    }

    private function hasValidSignature(Request $request, string $payload, array $event): bool
    {
        $secret = config('services.paymongo.webhook_secret');
        $signatureHeader = $request->header('Paymongo-Signature');

        if (! is_string($secret) || $secret === '' || ! is_string($signatureHeader) || $signatureHeader === '') {
            return false;
        }

        $parts = collect(explode(',', $signatureHeader))
            ->mapWithKeys(function (string $part): array {
                [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');

                return [$key => $value];
            });

        $timestamp = $parts->get('t');
        $livemode = (bool) data_get($event, 'data.attributes.livemode', false);
        $signature = $parts->get($livemode ? 'li' : 'te');

        if (! $timestamp || ! $signature) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    private function resolveBookings(array $event): EloquentCollection
    {
        $resource = data_get($event, 'data.attributes.data', []);
        $metadata = data_get($resource, 'attributes.metadata', []);
        $bookingIds = collect(explode(',', (string) data_get($metadata, 'booking_ids')))
            ->filter(fn (string $id): bool => ctype_digit($id))
            ->map(fn (string $id): int => (int) $id)
            ->values();

        if ($bookingIds->isNotEmpty()) {
            return Booking::with('payment')->whereIn('id', $bookingIds)->get();
        }

        $bookingId = data_get($metadata, 'booking_id');

        if ($bookingId && ctype_digit((string) $bookingId)) {
            return Booking::with('payment')->whereKey((int) $bookingId)->get();
        }

        $externalReference = data_get($resource, 'attributes.external_reference_number')
            ?: data_get($resource, 'attributes.reference_number');

        if (is_string($externalReference) && preg_match('/^CF-?0*(\d+)$/i', $externalReference, $matches)) {
            return Booking::with('payment')->whereKey((int) $matches[1])->get();
        }

        return new EloquentCollection;
    }

    private function resolvePaymentReference(array $event): string
    {
        $resource = data_get($event, 'data.attributes.data', []);

        return data_get($resource, 'attributes.reference_number')
            ?: data_get($resource, 'attributes.payment_intent_id')
            ?: data_get($resource, 'id')
            ?: data_get($event, 'data.id')
            ?: Booking::generatePaymentReference('gcash');
    }

    private function createPaymentNotification(Booking $booking): void
    {
        Notification::create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'title' => 'Payment confirmed',
            'message' => 'Payment for booking CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT).' has been confirmed through PayMongo.',
            'type' => 'success',
            'link' => route('bookings.show', $booking->id),
        ]);
    }
}
