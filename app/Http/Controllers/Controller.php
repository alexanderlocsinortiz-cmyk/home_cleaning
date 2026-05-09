<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

abstract class Controller
{
    /**
     * Create a notification and invalidate related caches
     */
    public function createNotification(array $data)
    {
        $notification = \App\Models\Notification::create($data);

        // ✅ Invalidate user's notification count cache
        Cache::forget('staff:unread_notif_' . $data['user_id']);

        return $notification;
    }

    /**
     * Build service area stats shared across client and staff portals
     */
    protected function serviceAreaStats(): array
    {
        $barangays = config('cleanflow.service_areas', []);

        $avg = \App\Models\Rating::avg('stars');

        return [
            'barangays' => count($barangays),
            'customers' => \App\Models\User::where('role', 'client')->count(),
            'staff' => \App\Models\User::where('role', 'staff')->count(),
            'satisfaction' => $avg ? round(($avg / 5) * 100) : 98,
        ];
    }
}
