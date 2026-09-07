<?php

namespace Tests\Feature;

use App\Http\Middleware\RateLimitPerDevice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitPerDeviceTest extends TestCase
{
    public function test_device_limit_expires_after_one_minute(): void
    {
        $token = 'rate-limit-regression-token';
        $middleware = new RateLimitPerDevice;
        $next = static fn (Request $request): Response => response('ok');
        $request = Request::create('/api/iot/device/enrollment/next', 'GET');
        $request->headers->set('X-Device-Token', $token);

        try {
            for ($attempt = 0; $attempt < 120; $attempt++) {
                $this->assertSame(200, $middleware->handle($request, $next)->getStatusCode());
            }

            $limited = $middleware->handle($request, $next);

            $this->assertSame(429, $limited->getStatusCode());
            $this->assertGreaterThan(0, (int) $limited->headers->get('Retry-After'));

            Carbon::setTestNow(now()->addSeconds(61));

            $this->assertSame(200, $middleware->handle($request, $next)->getStatusCode());
        } finally {
            RateLimiter::clear('rate_limit:device:'.hash('sha256', $token));
            RateLimiter::clear('rate_limit:iot:identified-ip:'.$request->ip());
            Carbon::setTestNow();
        }
    }
}
