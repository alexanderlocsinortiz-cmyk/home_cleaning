<?php

namespace App\Http\Middleware;

use App\Models\CleanerApplication;
use Closure;
use Illuminate\Http\Request;

class ProviderMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->role !== 'provider') {
            abort(403, 'Unauthorized. Provider access only.');
        }

        $application = auth()->user()->cleanerApplication;

        if (
            ! $application
            || $application->status !== CleanerApplication::STATUS_APPROVED
            || ! $application->activated_at
        ) {
            abort(403, 'Unauthorized. Provider account is not approved and activated.');
        }

        return $next($request);
    }
}
