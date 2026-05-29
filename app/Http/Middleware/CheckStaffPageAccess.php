<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckStaffPageAccess
{
    private const ROUTE_PAGE_MAP = [
        'staff.dashboard' => 'dashboard',
        'staff.bookings' => 'bookings',
        'staff.schedule' => 'schedule',
        'staff.performance' => 'performance',
        'staff.notifications' => 'notifications',
        'staff.notifications.read-all' => 'notifications',
        'staff.notifications.read' => 'notifications',
        'staff.profile' => 'profile',
        'staff.profile.update' => 'profile',
        'staff.service-areas' => 'service_areas',
        'staff.fingerprint-consent.show' => 'notifications',
        'staff.fingerprint-consent.accept' => 'notifications',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        $page = self::ROUTE_PAGE_MAP[$request->route()?->getName()] ?? null;

        if ($user && $page && $user->isStaffPageRestricted($page)) {
            abort(403, 'Your access to this staff page is restricted.');
        }

        return $next($request);
    }
}
