<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RejectOversizedProofUpload
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('staff/bookings/*/status')) {
            $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
            $maxRequestBytes = (int) config('cleanflow.proof_uploads.max_request_kb', 32768) * 1024;

            if ($contentLength > $maxRequestBytes) {
                $maxMegabytes = (int) floor($maxRequestBytes / 1024 / 1024);

                return back()->withErrors([
                    'proof_upload' => 'The proof upload is too large. Keep the total upload under '.$maxMegabytes.' MB.',
                ]);
            }
        }

        return $next($request);
    }
}
