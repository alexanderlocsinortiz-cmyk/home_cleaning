<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitPerDevice
{
    /**
     * Rate limit per device serial number instead of per IP
     */
    public function handle(Request $request, Closure $next)
    {
        $deviceSerial = $request->header('X-Device-Serial');
        $deviceToken = $request->header('X-Device-Token');

        if (! $deviceSerial && ! $deviceToken) {
            // The controller still returns 401, but unauthenticated requests
            // must be capped before they can flood authentication and logs.
            $key = 'rate_limit:iot:ip:'.$request->ip();
            $limit = 30;

            if (RateLimiter::tooManyAttempts($key, $limit)) {
                Log::warning('IoT IP rate limit exceeded before authentication', [
                    'ip' => $request->ip(),
                ]);

                return response()->json(
                    ['error' => 'Too many requests. Please wait.'],
                    429,
                    ['Retry-After' => (string) RateLimiter::availableIn($key)]
                );
            }

            RateLimiter::hit($key, 60);

            return $next($request);
        }

        // A caller can rotate fake device headers, so also cap identified
        // requests by source IP. The higher ceiling avoids penalizing a site
        // with multiple legitimate devices behind one network.
        $ipKey = 'rate_limit:iot:identified-ip:'.$request->ip();
        $ipLimit = 300;

        if (RateLimiter::tooManyAttempts($ipKey, $ipLimit)) {
            Log::warning('IoT identified-request IP rate limit exceeded', [
                'ip' => $request->ip(),
                'device_serial' => $deviceSerial,
            ]);

            return response()->json(
                ['error' => 'Too many requests. Please wait.'],
                429,
                ['Retry-After' => (string) RateLimiter::availableIn($ipKey)]
            );
        }

        RateLimiter::hit($ipKey, 60);

        // ESP32 devices poll enrollment and heartbeat frequently while online.
        // Cache increment() does not accept a TTL, so the old counter never
        // expired and a device was permanently blocked after 120 requests.
        $key = 'rate_limit:device:'.($deviceSerial ?: hash('sha256', $deviceToken));
        $limit = 120;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            Log::warning('Device rate limit exceeded', [
                'device_serial' => $deviceSerial,
                'has_device_token' => (bool) $deviceToken,
            ]);

            return response()->json(
                ['error' => 'Too many requests. Please wait.'],
                429,
                ['Retry-After' => (string) RateLimiter::availableIn($key)]
            );
        }

        RateLimiter::hit($key, $decaySeconds);

        return $next($request);
    }
}
