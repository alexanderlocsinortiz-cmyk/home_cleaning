<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ClientMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->role !== 'client') {
            if (auth()->user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif (auth()->user()->role === 'staff') {
                return redirect()->route('staff.dashboard');
            }
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
