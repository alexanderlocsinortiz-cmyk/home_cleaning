<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RejectOversizedProofUpload
{
    public function handle(Request $request, Closure $next)
    {
        $isProofUploadRoute = $request->is('staff/bookings/*/status')
            || $request->is('provider/bookings/*/status')
            || $request->is('api/mobile/staff/bookings/*/start')
            || $request->is('api/mobile/staff/bookings/*/complete');

        if ($isProofUploadRoute) {
            $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
            $maxRequestBytes = (int) config('cleanflow.proof_uploads.max_request_kb', 131072) * 1024;

            if ($contentLength > $maxRequestBytes) {
                $maxMegabytes = (int) floor($maxRequestBytes / 1024 / 1024);
                $message = 'The proof upload is too large. Keep the total upload under '.$maxMegabytes.' MB.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 413);
                }

                return back()->withErrors([
                    'proof_upload' => $message,
                ]);
            }
        }

        return $next($request);
    }
}
