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

        if (!$deviceSerial) {
            return response()->json(['error' => 'Device serial required'], 400);
        }

        // Rate limit per device: 10 requests per minute
        $key = 'rate_limit:device:' . $deviceSerial;
        $limit = 10;
        $decayMinutes = 1;

        if (cache()->get($key, 0) >= $limit) {
            Log::warning('Device rate limit exceeded', [
                'device_serial' => $deviceSerial,
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
