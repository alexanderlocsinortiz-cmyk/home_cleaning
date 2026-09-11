<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAccountAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (! $user || $user->role === 'admin' || ! $user->hasActiveAccessRestriction()) {
            return $next($request);
        }

        $timezone = config('cleanflow.attendance_timezone', 'Asia/Manila');

        abort(403, 'This account is restricted until '.$user->access_restricted_until->copy()->timezone($timezone)->format('M d, Y h:i A').'.');
    }
}
