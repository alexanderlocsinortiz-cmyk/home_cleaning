<?php

namespace App\Jobs;

use App\Models\MobilePushToken;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendExpoPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 45;

    public array $backoff = [10, 60, 300];

    public function __construct(public int $notificationId)
    {
        $this->queue = (string) config('services.expo_push.queue', 'emails');
    }

    public function handle(): void
    {
        $notification = Notification::with('recipient')->find($this->notificationId);

        if (! $notification || ! $notification->recipient) {
            return;
        }

        $tokens = MobilePushToken::query()
            ->where('user_id', $notification->user_id)
            ->pluck('token')
            ->values()
            ->all();

        if ($tokens === []) {
            return;
        }

        $messages = array_map(
            fn (string $token): array => $this->messagePayload($notification, $token),
            $tokens,
        );

        foreach (array_chunk($messages, 100) as $messageChunk) {
            $this->sendChunk($messageChunk);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send mobile push notification.', [
            'notification_id' => $this->notificationId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function sendChunk(array $messages): void
    {
        $request = $this->pushRequest();
        $response = $request->post((string) config('services.expo_push.url'), $messages);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Expo Push Service returned HTTP '.$response->status().': '.
                mb_substr($response->body(), 0, 500),
            );
        }

        $tickets = $response->json('data', []);

        if (! is_array($tickets)) {
            return;
        }

        foreach ($tickets as $index => $ticket) {
            if (! is_array($ticket) || ($ticket['status'] ?? null) !== 'error') {
                continue;
            }

            $errorCode = data_get($ticket, 'details.error');

            if ($errorCode === 'DeviceNotRegistered' && isset($messages[$index]['to'])) {
                MobilePushToken::where('token', $messages[$index]['to'])->delete();
            }

            Log::warning('Expo Push Service rejected a mobile push notification.', [
                'notification_id' => $this->notificationId,
                'push_token_hash' => isset($messages[$index]['to'])
                    ? hash('sha256', (string) $messages[$index]['to'])
                    : null,
                'error' => $errorCode ?? ($ticket['message'] ?? 'unknown'),
            ]);
        }
    }

    private function pushRequest(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout((int) config('services.expo_push.timeout_seconds', 10));
        $accessToken = config('services.expo_push.access_token');

        if (filled($accessToken)) {
            $request = $request->withToken((string) $accessToken);
        }

        return $request;
    }

    private function messagePayload(Notification $notification, string $token): array
    {
        $recipientRole = $notification->recipient?->role;
        $route = $notification->booking_id
            ? ($recipientRole === 'client'
                ? '/client-booking-details?id='.$notification->booking_id
                : '/staff-bookings')
            : ($recipientRole === 'client' ? '/client-notifications' : '/staff-notifications');

        return [
            'to' => $token,
            'title' => $notification->title ?: $notification->subject ?: 'CleanFlow update',
            'body' => $notification->message,
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => 'default',
            'data' => [
                'booking_id' => $notification->booking_id,
                'link' => $notification->link,
                'notification_id' => $notification->id,
                'route' => $route,
            ],
        ];
    }
}
