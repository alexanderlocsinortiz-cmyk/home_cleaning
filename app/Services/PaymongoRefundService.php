<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaymongoRefundService
{
    public function refund(Payment $payment, string $notes = 'Booking cancelled by customer or administrator.'): Payment
    {
        return $this->refundAmount($payment, null, $notes);
    }

    public function refundAmount(Payment $payment, ?float $requestedAmount, string $notes): Payment
    {
        if (! in_array($payment->method, ['gcash', 'maya'], true) || $payment->provider !== 'paymongo') {
            throw new RuntimeException('Only paid PayMongo online payments can be refunded automatically.');
        }

        return Cache::lock('payment-refund:'.$payment->id, 30)->block(5, function () use ($payment, $requestedAmount, $notes): Payment {
            $payment = Payment::query()->findOrFail($payment->id);

            if ($payment->status === 'refunded' || $payment->refund_status === 'succeeded') {
                return $payment;
            }

            if (in_array($payment->refund_status, ['pending', 'processing'], true)) {
                return $payment;
            }

            if ($payment->status !== 'paid') {
                throw new RuntimeException('The payment must be confirmed as paid before it can be refunded.');
            }

            $providerPaymentId = $payment->provider_payment_id;

            if (! is_string($providerPaymentId) || ! str_starts_with($providerPaymentId, 'pay_')) {
                $this->markFailed($payment, 'PayMongo payment ID is missing; an administrator must verify this payment in the PayMongo dashboard.');

                throw new RuntimeException('The PayMongo payment ID is missing, so the refund needs admin review.');
            }

            if ($requestedAmount !== null) {
                if ($requestedAmount <= 0 || $requestedAmount > (float) $payment->amount) {
                    throw new RuntimeException('The refund amount must be greater than zero and no more than the payment amount.');
                }

                $payment->refund_amount = number_format($requestedAmount, 2, '.', '');
            }

            $amount = (int) round(((float) $payment->refund_amount ?: (float) $payment->amount) * 100);

            if ($amount < 100) {
                $this->markFailed($payment, 'Refund amount is below PayMongo’s minimum of PHP 1.00.');

                throw new RuntimeException('The refund amount is below PayMongo’s minimum refundable amount.');
            }

            $payment->forceFill([
                'refund_status' => 'pending',
                'refund_amount' => number_format($amount / 100, 2, '.', ''),
                'refund_requested_at' => now(),
                'refund_failure_reason' => null,
            ])->save();

            try {
                $response = $this->client()->withHeaders([
                    'Idempotency-Key' => 'cleanflow-refund-payment-'.$payment->id.'-'.$amount,
                ])->post(rtrim((string) config('services.paymongo.api_url'), '/').'/v1/refunds', [
                    'data' => [
                        'attributes' => [
                            'amount' => $amount,
                            'payment_id' => $providerPaymentId,
                            'reason' => 'requested_by_customer',
                            'notes' => $notes,
                        ],
                    ],
                ]);

                if ($response->failed()) {
                    throw new RuntimeException('PayMongo refund request failed: '.$response->body());
                }

                $refundId = data_get($response->json(), 'data.id');
                $providerStatus = data_get($response->json(), 'data.attributes.status', 'pending');

                if (! is_string($refundId) || $refundId === '') {
                    throw new RuntimeException('PayMongo returned no refund ID.');
                }

                $localStatus = match ($providerStatus) {
                    'succeeded' => 'succeeded',
                    'failed' => 'failed',
                    'processing' => 'processing',
                    default => 'pending',
                };

                $payment->forceFill([
                    'refund_status' => $localStatus,
                    'refund_reference' => $refundId,
                    'refunded_at' => $localStatus === 'succeeded' ? ($payment->refunded_at ?: now()) : null,
                    'status' => $localStatus === 'succeeded' ? 'refunded' : $payment->status,
                    'refund_failure_reason' => $localStatus === 'failed' ? 'PayMongo reported that the refund failed.' : null,
                ])->save();

                if ($localStatus === 'failed') {
                    throw new RuntimeException('PayMongo reported that the refund failed.');
                }

                return $payment;
            } catch (\Throwable $exception) {
                if ($payment->refund_status !== 'failed') {
                    $this->markFailed($payment, $exception->getMessage());
                }

                throw $exception;
            }
        });
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
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

        return $request;
    }

    private function markFailed(Payment $payment, string $reason): void
    {
        $payment->forceFill([
            'refund_status' => 'failed',
            'refund_failure_reason' => mb_substr($reason, 0, 2000),
        ])->save();
    }
}
