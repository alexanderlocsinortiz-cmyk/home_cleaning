<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            return $next($request);
        }

        // ESP32 devices poll enrollment and heartbeat frequently while online.
        $key = 'rate_limit:device:'.($deviceSerial ?: hash('sha256', $deviceToken));
        $limit = 120;
        $decayMinutes = 1;

        if (cache()->get($key, 0) >= $limit) {
            Log::warning('Device rate limit exceeded', [
                'device_serial' => $deviceSerial,
                'has_device_token' => (bool) $deviceToken,
            ]);

            return response()->json(
                ['error' => 'Too many requests. Please wait.'],
                429
            );
        }

        cache()->increment($key, 1, now()->addMinutes($decayMinutes));

        return $next($request);
    }
}
