<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Services\PaymongoCheckoutService;
use App\Services\PaymongoRefundService;
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

        if (in_array($eventType, ['payment.refunded', 'payment.refund.updated'], true)) {
            return $this->handleRefundEvent($event, $eventType);
        }

        if (! in_array($eventType, ['checkout_session.payment.paid', 'payment.paid'], true)) {
            return response()->json(['status' => 'ignored']);
        }

        return $this->handlePaidEvent($event, $eventType);
    }

    private function handlePaidEvent(array $event, string $eventType): JsonResponse
    {
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
        $paymentMethod = $this->resolvePaymentMethod($event);
        $checkoutSessionId = data_get($event, 'data.attributes.data.id');
        $providerPaymentId = $this->resolveProviderPaymentId($event, $eventType);

        if ($bookings->every(fn (Booking $booking): bool => in_array($booking->payment?->status, ['paid', 'refunded'], true)
            && $booking->payment?->reference === $paymentReference
        )) {
            return response()->json(['status' => 'already_processed']);
        }

        $bookings->each(function (Booking $booking) use ($paymentReference, $paymentMethod, $checkoutSessionId, $providerPaymentId): void {
            $payment = $booking->paymentOrCreate([
                // A normalized booking no longer stores the payment method;
                // recover it from PayMongo when a local payment row is gone.
                'method' => $booking->payment?->method ?: $paymentMethod ?: 'gcash',
                'status' => 'pending',
                'amount' => $booking->price ?? 0,
                'currency' => 'PHP',
                'provider' => 'paymongo',
            ]);
            $wasPending = ! in_array($payment->status, ['paid', 'refunded'], true);
            $shouldReplaceReference = ! $payment->reference || str_starts_with((string) $payment->reference, 'cs_');

            $payment->forceFill([
                'status' => $payment->status === 'refunded' ? 'refunded' : 'paid',
                'reference' => $shouldReplaceReference ? $paymentReference : $payment->reference,
                'checkout_session_id' => $payment->checkout_session_id ?: (is_string($checkoutSessionId) && str_starts_with($checkoutSessionId, 'cs_') ? $checkoutSessionId : null),
                'paid_at' => $payment->paid_at ?: now(),
                'provider_payment_id' => is_string($providerPaymentId) && str_starts_with($providerPaymentId, 'pay_')
                    ? $providerPaymentId
                    : $payment->provider_payment_id,
            ])->save();

            $booking->setRelation('payment', $payment);

            if ($booking->status === 'cancelled' && $payment->status !== 'refunded') {
                try {
                    $booking->setRelation('payment', app(PaymongoRefundService::class)->refund($payment, 'Payment received after booking cancellation.'));
                    $this->createRefundNotification($booking);
                } catch (\Throwable $exception) {
                    $this->createRefundFailureNotification($booking->fresh(['payment']));

                    Log::error('Late PayMongo payment for cancelled booking could not be refunded.', [
                        'booking_id' => $booking->id,
                        'payment_id' => $payment->id,
                        'provider_payment_id' => $payment->provider_payment_id,
                        'error' => $exception->getMessage(),
                    ]);
                }

                return;
            }

            if ($wasPending) {
                $this->createPaymentNotification($booking);
            }
        });

        return response()->json(['status' => 'processed']);
    }

    private function handleRefundEvent(array $event, string $eventType): JsonResponse
    {
        $resource = data_get($event, 'data.attributes.data', []);
        $resourceId = data_get($resource, 'id');
        $refundId = is_string($resourceId) && str_starts_with($resourceId, 'ref_')
            ? $resourceId
            : (data_get($resource, 'attributes.refund_id')
                ?: data_get($resource, 'attributes.refund.id'));
        $providerPaymentId = data_get($resource, 'attributes.payment_id')
            ?: (is_string($resourceId) && str_starts_with($resourceId, 'pay_') ? $resourceId : null);

        if ((! is_string($providerPaymentId) || $providerPaymentId === '')
            && (! is_string($refundId) || $refundId === '')) {
            Log::warning('PayMongo refund webhook did not contain a payment or refund ID.', [
                'event_id' => data_get($event, 'data.id'),
                'event_type' => $eventType,
            ]);

            return response()->json(['status' => 'unmatched'], 202);
        }

        // A subscription checkout can have several local payment rows that
        // share one PayMongo payment ID. The refund ID identifies the exact
        // partial refund, so use it before falling back to the payment ID.
        $payment = Payment::with('booking')
            ->when(is_string($refundId) && $refundId !== '', fn ($query) => $query->where('refund_reference', $refundId))
            ->when((! is_string($refundId) || $refundId === '') && is_string($providerPaymentId) && $providerPaymentId !== '', fn ($query) => $query->where('provider_payment_id', $providerPaymentId))
            ->first();

        if (! $payment) {
            Log::warning('PayMongo refund webhook could not be matched to a payment.', [
                'event_id' => data_get($event, 'data.id'),
                'event_type' => $eventType,
                'refund_id' => $refundId,
                'provider_payment_id' => $providerPaymentId,
            ]);

            return response()->json(['status' => 'unmatched'], 202);
        }

        $providerStatus = $eventType === 'payment.refunded'
            ? 'succeeded'
            : data_get($resource, 'attributes.status', 'pending');
        $refundStatus = match ($providerStatus) {
            'succeeded' => 'succeeded',
            'failed' => 'failed',
            'processing' => 'processing',
            default => 'pending',
        };
        $wasSucceeded = $payment->refund_status === 'succeeded' || $payment->status === 'refunded';
        $wasFailed = $payment->refund_status === 'failed';

        $payment->forceFill([
            'refund_status' => $refundStatus,
            'refund_reference' => is_string($refundId) && $refundId !== '' ? $refundId : $payment->refund_reference,
            'refund_amount' => data_get($resource, 'attributes.amount') !== null
                ? number_format(((int) data_get($resource, 'attributes.amount')) / 100, 2, '.', '')
                : $payment->refund_amount,
            'refunded_at' => $refundStatus === 'succeeded' ? ($payment->refunded_at ?: now()) : null,
            'status' => $refundStatus === 'succeeded' ? 'refunded' : $payment->status,
            'refund_failure_reason' => $refundStatus === 'failed' ? 'PayMongo reported that the refund failed.' : null,
        ])->save();

        if (! $wasSucceeded && $refundStatus === 'succeeded' && $payment->booking) {
            $this->createRefundNotification($payment->booking->fresh(['payment']));
        }

        if (! $wasFailed && $refundStatus === 'failed' && $payment->booking) {
            $this->createRefundFailureNotification($payment->booking->fresh(['payment']));
        }

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

    private function resolvePaymentMethod(array $event): ?string
    {
        $resource = data_get($event, 'data.attributes.data', []);
        $attributes = data_get($resource, 'attributes', []);
        $payments = collect(data_get($attributes, 'payments', []))
            ->merge(data_get($attributes, 'payment_intent.attributes.payments', []));
        $candidates = collect([
            data_get($attributes, 'source.type'),
            data_get($attributes, 'payment_method.type'),
        ])->merge($payments->flatMap(fn ($payment): array => [
            data_get($payment, 'attributes.source.type'),
            data_get($payment, 'source.type'),
            data_get($payment, 'attributes.payment_method.type'),
        ]));

        $method = $candidates
            ->filter(fn ($candidate): bool => is_string($candidate) && $candidate !== '')
            ->map(fn (string $candidate): string => strtolower($candidate))
            ->first(fn (string $candidate): bool => in_array($candidate, ['gcash', 'maya', 'paymaya'], true));

        return match ($method) {
            'gcash' => 'gcash',
            'maya', 'paymaya' => 'maya',
            default => null,
        };
    }

    private function resolveProviderPaymentId(array $event, string $eventType): ?string
    {
        $resource = data_get($event, 'data.attributes.data', []);
        $candidate = $eventType === 'payment.paid'
            ? data_get($resource, 'id')
            : collect(data_get($resource, 'attributes.payments', []))
                ->merge(data_get($resource, 'attributes.payment_intent.attributes.payments', []))
                ->map(fn ($payment) => data_get($payment, 'id') ?: data_get($payment, 'attributes.id'))
                ->first(fn ($id) => is_string($id) && str_starts_with($id, 'pay_'));

        if (is_string($candidate) && str_starts_with($candidate, 'pay_')) {
            return $candidate;
        }

        $checkoutSessionId = data_get($resource, 'id');

        if ($eventType === 'checkout_session.payment.paid'
            && is_string($checkoutSessionId)
            && str_starts_with($checkoutSessionId, 'cs_')) {
            try {
                $paymongo = app(PaymongoCheckoutService::class);

                return $paymongo->paymentIdFromCheckoutSession($paymongo->retrieveCheckoutSession($checkoutSessionId));
            } catch (\Throwable $exception) {
                Log::warning('PayMongo checkout session did not expose a payment ID during webhook processing.', [
                    'checkout_session_id' => $checkoutSessionId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return null;
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

    private function createRefundNotification(Booking $booking): void
    {
        $status = $booking->payment?->refund_status;
        $message = match ($status) {
            'succeeded' => 'The online payment for booking CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT).' has been refunded through PayMongo.',
            'pending', 'processing' => 'The online payment refund for booking CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT).' is being processed.',
            default => 'The online payment refund for booking CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT).' needs admin review. Please do not pay again.',
        };

        Notification::create([
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'title' => 'Payment refund update',
            'message' => $message,
            'type' => $status === 'succeeded' ? 'success' : 'info',
            'link' => route('bookings.show', $booking->id),
        ]);
    }

    private function createRefundFailureNotification(Booking $booking): void
    {
        $bookingCode = 'CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);

        Notification::firstOrCreate(
            ['dedupe_key' => 'payment-refund-failed:'.$booking->id],
            [
                'user_id' => $booking->user_id,
                'booking_id' => $booking->id,
                'title' => 'Payment refund needs review',
                'message' => 'The online payment for booking '.$bookingCode.' could not be refunded automatically. Please do not pay again; support needs to review the payment.',
                'type' => 'warning',
                'link' => route('bookings.show', $booking->id),
            ],
        );
    }
}
