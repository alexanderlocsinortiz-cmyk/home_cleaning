<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use LogicException;

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
        $this->ensureProductionUploadsAreDurable();
        $this->ensureProductionRuntimeIsSafe();
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

            $adminNotificationsQuery = auth()->check() && auth()->user()->role === 'admin'
                ? Notification::where('user_id', auth()->id())->whereNull('read_at')
                : Notification::whereRaw('1 = 0');

            $view->with([
                'pendingBookingsCount' => $pendingQuery->count(),
                'pendingBookingsPreview' => $pendingBookingsPreview,
                'adminUnreadNotificationsCount' => (clone $adminNotificationsQuery)->count(),
                'adminNotificationsPreview' => (clone $adminNotificationsQuery)
                    ->with('booking')
                    ->latest()
                    ->take(5)
                    ->get(),
            ]);
        });

        // ✅ Cache staff unread notifications for 1 minute to avoid N+1 query
        View::composer('layouts.staff', function ($view) {
            if (! auth()->check()) {
                $view->with('unreadNotifCount', 0);

                return;
            }

            $unreadCount = Cache::remember(
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

    private function ensureProductionUploadsAreDurable(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $privateDisk = (string) config('filesystems.private_uploads_disk');
        $publicDisk = (string) config('filesystems.public_uploads_disk');
        $proofDisk = (string) config('filesystems.proof_uploads_disk');

        $usesLocalDriver = function (string $diskName): bool {
            $diskConfig = config("filesystems.disks.{$diskName}");

            return ! is_array($diskConfig) || ($diskConfig['driver'] ?? null) === 'local';
        };

        if ($usesLocalDriver($privateDisk)) {
            throw new LogicException(
                'Production cannot start with local private uploads. Configure FILESYSTEM_PRIVATE_DISK to durable private object storage.'
            );
        }

        if ($usesLocalDriver($publicDisk)) {
            throw new LogicException(
                'Production cannot start with local public uploads. Configure FILESYSTEM_PUBLIC_DISK to durable object storage.'
            );
        }

        if ($usesLocalDriver($proofDisk)) {
            throw new LogicException(
                'Production cannot start with local booking proof uploads. Configure FILESYSTEM_PROOF_DISK to durable private object storage.'
            );
        }

        $privateVisibility = strtolower((string) (config("filesystems.disks.{$privateDisk}.visibility") ?? 'private'));

        if ($privateDisk === $publicDisk) {
            throw new LogicException(
                'Production cannot use the public uploads disk for private uploads. Configure FILESYSTEM_PRIVATE_DISK separately.'
            );
        }

        if (in_array($privateVisibility, ['public', 'public-read'], true)) {
            throw new LogicException(
                'Production private uploads must use private object visibility.'
            );
        }

        if ($proofDisk === $publicDisk) {
            throw new LogicException(
                'Production cannot use the public uploads disk for booking proof media. Configure FILESYSTEM_PROOF_DISK separately.'
            );
        }

        $proofVisibility = strtolower((string) (config("filesystems.disks.{$proofDisk}.visibility") ?? 'private'));

        if (in_array($proofVisibility, ['public', 'public-read'], true)) {
            throw new LogicException(
                'Production booking proof uploads must use private object visibility.'
            );
        }

        $backupDisk = (string) config('filesystems.database_backup_disk');

        if ($usesLocalDriver($backupDisk)) {
            throw new LogicException(
                'Production cannot start with local database backups. Configure DATABASE_BACKUP_DISK to durable private object storage.'
            );
        }

        $backupVisibility = strtolower((string) (config("filesystems.disks.{$backupDisk}.visibility") ?? 'private'));

        if ($backupDisk === $publicDisk) {
            throw new LogicException(
                'Production cannot use the public uploads disk for database backups. Configure DATABASE_BACKUP_DISK to a private disk.'
            );
        }

        if (in_array($backupVisibility, ['public', 'public-read'], true)) {
            throw new LogicException(
                'Production database backups must use private object visibility.'
            );
        }
    }

    private function ensureProductionRuntimeIsSafe(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $unsafeSettings = [];

        if ((bool) config('app.debug')) {
            $unsafeSettings[] = 'APP_DEBUG must be false';
        }

        if (! filled(config('app.key'))) {
            $unsafeSettings[] = 'APP_KEY must be configured';
        }

        if (in_array((string) config('queue.default'), ['sync', 'null'], true)) {
            $unsafeSettings[] = 'QUEUE_CONNECTION must use a durable queue with a running worker';
        }

        if (in_array((string) config('mail.default'), ['log', 'array'], true)) {
            $unsafeSettings[] = 'MAIL_MAILER must use a real delivery transport';
        }

        if (in_array((string) config('cache.default'), ['array', 'file'], true)) {
            $unsafeSettings[] = 'CACHE_STORE must use database or shared Redis storage';
        }

        if ((string) config('session.driver') === 'file') {
            $unsafeSettings[] = 'SESSION_DRIVER must use database or shared Redis storage';
        }

        if (! (bool) config('session.encrypt')) {
            $unsafeSettings[] = 'SESSION_ENCRYPT must be true';
        }

        if (! (bool) config('session.secure')) {
            $unsafeSettings[] = 'SESSION_SECURE_COOKIE must be true';
        }

        if (strtolower((string) config('session.same_site')) !== 'strict') {
            $unsafeSettings[] = 'SESSION_SAME_SITE must be strict';
        }

        if (! (bool) config('cleanflow.iot.require_signed_requests', true)) {
            $unsafeSettings[] = 'IOT_REQUIRE_SIGNED_REQUESTS must be true';
        }

        if (! str_starts_with(strtolower(rtrim((string) config('app.url'), '/')), 'https://')) {
            $unsafeSettings[] = 'APP_URL must use HTTPS';
        }

        if ($unsafeSettings !== []) {
            throw new LogicException('Unsafe production configuration: '.implode('; ', $unsafeSettings).'.');
        }
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
