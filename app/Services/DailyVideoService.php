<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DailyVideoService
{
    public function isConfigured(): bool
    {
        return filled($this->apiKey());
    }

    public function ensureRoomForBooking(Booking $booking): Booking
    {
        $this->guardConfigured();

        if ($booking->dailyRoomIsActive()) {
            return $booking;
        }

        $roomName = $booking->daily_room_name ?: 'cf-booking-'.$booking->id.'-'.Str::lower(Str::random(8));
        $expiresAt = $this->roomExpiresAt();

        try {
            $response = $this->dailyRequest()
                ->acceptJson()
                ->asJson()
                ->post($this->apiUrl().'/rooms', [
                    'name' => $roomName,
                    'privacy' => 'private',
                    'properties' => [
                        'exp' => $expiresAt->timestamp,
                        'enable_chat' => true,
                        'enable_screenshare' => false,
                        'start_audio_off' => false,
                        'start_video_off' => false,
                    ],
                ])
                ->throw()
                ->json();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Daily room could not be created because PHP cannot connect securely to Daily.co. Check DAILY_CA_BUNDLE in .env.', previous: $exception);
        } catch (RequestException $exception) {
            throw new RuntimeException('Daily room could not be created: '.$this->dailyErrorMessage($exception), previous: $exception);
        }

        $booking->forceFill([
            'daily_room_name' => $roomName,
            'daily_room_url' => $response['url'] ?? $this->roomUrl($roomName),
            'daily_room_expires_at' => $expiresAt,
            'live_video_started_at' => $booking->live_video_started_at ?: now(),
            'live_video_ended_at' => null,
        ])->save();

        return $booking->refresh();
    }

    public function createMeetingToken(Booking $booking, User $user): string
    {
        $this->guardConfigured();

        if (! $booking->dailyRoomIsActive()) {
            throw new RuntimeException('This booking does not have an active Daily room.');
        }

        $canBroadcast = in_array($user->role, ['admin', 'staff'], true);

        try {
            $response = $this->dailyRequest()
                ->acceptJson()
                ->asJson()
                ->post($this->apiUrl().'/meeting-tokens', [
                    'properties' => [
                        'room_name' => $booking->daily_room_name,
                        'user_id' => (string) $user->id,
                        'user_name' => $user->display_name,
                        'is_owner' => $canBroadcast,
                        'exp' => $this->tokenExpiresAt($booking)->timestamp,
                        'permissions' => [
                            'canSend' => $canBroadcast,
                        ],
                    ],
                ])
                ->throw()
                ->json();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Daily meeting token could not be created because PHP cannot connect securely to Daily.co. Check DAILY_CA_BUNDLE in .env.', previous: $exception);
        } catch (RequestException $exception) {
            throw new RuntimeException('Daily meeting token could not be created: '.$this->dailyErrorMessage($exception), previous: $exception);
        }

        $token = $response['token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Daily did not return a meeting token.');
        }

        return $token;
    }

    public function endRoomForBooking(Booking $booking): Booking
    {
        $roomName = $booking->daily_room_name;

        if (is_string($roomName) && $roomName !== '' && $this->isConfigured()) {
            try {
                $response = $this->dailyRequest()
                    ->acceptJson()
                    ->delete($this->apiUrl().'/rooms/'.$roomName);

                if ($response->failed() && $response->status() !== 404) {
                    throw new RuntimeException('Daily room could not be ended: '.$response->body());
                }
            } catch (ConnectionException $exception) {
                throw new RuntimeException('Daily room could not be ended because PHP cannot connect securely to Daily.co. Check DAILY_CA_BUNDLE in .env.', previous: $exception);
            }
        }

        $booking->forceFill([
            'daily_room_name' => null,
            'daily_room_url' => null,
            'daily_room_expires_at' => now(),
            'live_video_ended_at' => now(),
        ])->save();

        return $booking->refresh();
    }

    private function guardConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Daily.co is not configured. Add DAILY_API_KEY to the environment.');
        }
    }

    private function apiUrl(): string
    {
        return rtrim((string) config('services.daily.api_url', 'https://api.daily.co/v1'), '/');
    }

    private function apiKey(): ?string
    {
        return config('services.daily.api_key');
    }

    private function dailyRequest()
    {
        $request = Http::withToken($this->apiKey());
        $caBundle = config('services.daily.ca_bundle');

        if (is_string($caBundle) && $caBundle !== '' && is_file($caBundle)) {
            $request = $request->withOptions(['verify' => $caBundle]);
        }

        if ((bool) config('services.daily.disable_proxy', true)) {
            $request = $request->withOptions(['proxy' => '']);
        }

        return $request;
    }

    private function roomUrl(string $roomName): string
    {
        $domain = trim((string) config('services.daily.domain'));

        if ($domain === '') {
            throw new RuntimeException('Daily.co returned no room URL. Add DAILY_DOMAIN to the environment.');
        }

        $domain = Str::replaceEnd('.daily.co', '', $domain);

        return 'https://'.$domain.'.daily.co/'.$roomName;
    }

    private function roomExpiresAt(): Carbon
    {
        return now()->addHours(max(1, (int) config('services.daily.room_ttl_hours', 6)));
    }

    private function tokenExpiresAt(Booking $booking): Carbon
    {
        $tokenExpiry = now()->addMinutes(max(15, (int) config('services.daily.token_ttl_minutes', 120)));
        $roomExpiry = Carbon::parse($booking->daily_room_expires_at);

        return $tokenExpiry->lt($roomExpiry) ? $tokenExpiry : $roomExpiry;
    }

    private function dailyErrorMessage(RequestException $exception): string
    {
        $message = $exception->response?->json('error')
            ?? $exception->response?->json('info')
            ?? $exception->getMessage();

        return is_string($message) ? $message : $exception->getMessage();
    }
}
