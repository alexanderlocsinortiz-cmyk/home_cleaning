<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->removeStaleViteHotFile();
        View::composer('*', function ($view) {
            $view->with('siteSettings', SiteSetting::current());
        });

        // ✅ Cache pending bookings count for 5 minutes to avoid N+1 query on every page load
        View::composer('layouts.admin', function ($view) {
            $pendingQuery = Booking::where('status', 'pending');
            $pendingBookingsPreview = (clone $pendingQuery)
                ->with('user:id,first_name,last_name,email')
                ->latest()
                ->take(5)
                ->get();

            $view->with([
                'pendingBookingsCount' => $pendingQuery->count(),
                'pendingBookingsPreview' => $pendingBookingsPreview,
            ]);
        });

        // ✅ Cache staff unread notifications for 1 minute to avoid N+1 query
        View::composer('layouts.staff', function ($view) {
            if (! auth()->check()) {
                $view->with('unreadNotifCount', 0);

                return;
            }

            $unreadCount = \Illuminate\Support\Facades\Cache::remember(
                'staff:unread_notif_'.auth()->id(),
                60,  // 1 minute
                function () {
                    return Notification::where('user_id', auth()->id())
                        ->whereNull('read_at')
                        ->count();
                }
            );
            $view->with('unreadNotifCount', $unreadCount);
        });

        View::composer('layouts.client', function ($view) {
            if (! auth()->check()) {
                $view->with([
                    'clientUnreadNotificationCount' => 0,
                    'clientNotificationsPreview' => collect(),
                ]);

                return;
            }

            $notificationsQuery = Notification::where('user_id', auth()->id());

            $view->with([
                'clientUnreadNotificationCount' => (clone $notificationsQuery)->whereNull('read_at')->count(),
                'clientNotificationsPreview' => (clone $notificationsQuery)
                    ->latest()
                    ->take(5)
                    ->get(),
            ]);
        });
    }

    private function removeStaleViteHotFile(): void
    {
        $hotFilePath = public_path('hot');

        if (! is_file($hotFilePath)) {
            return;
        }

        $hotFileUrl = trim((string) @file_get_contents($hotFilePath));

        if ($hotFileUrl === '') {
            @unlink($hotFilePath);

            return;
        }

        $urlParts = parse_url($hotFileUrl);

        if (! is_array($urlParts) || empty($urlParts['host'])) {
            @unlink($hotFilePath);

            return;
        }

        $host = (string) $urlParts['host'];
        $scheme = (string) ($urlParts['scheme'] ?? 'http');
        $port = (int) ($urlParts['port'] ?? ($scheme === 'https' ? 443 : 80));

        if (! $this->isHostReachable($host, $port)) {
            @unlink($hotFilePath);
        }
    }

    private function isHostReachable(string $host, int $port): bool
    {
        $connection = @fsockopen($host, $port, $errorNumber, $errorMessage, 0.2);

        if (! is_resource($connection)) {
            return false;
        }

        fclose($connection);

        return true;
    }
}
