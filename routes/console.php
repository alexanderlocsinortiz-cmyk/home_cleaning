<?php

use App\Http\Controllers\AdminSettingsController;
use App\Jobs\SendBookingReminderEmail;
use App\Models\Booking;
use App\Models\BookingServiceProof;
use App\Models\CleanerApplication;
use App\Models\Device;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\SecurityEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bookings:send-reminders', function (): int {
    $timezone = (string) config('cleanflow.attendance_timezone', config('app.timezone'));
    $now = Carbon::now($timezone);
    $windows = [
        [
            'key' => '24h',
            'min_minutes' => 1430,
            'max_minutes' => 1450,
            'client_title' => 'Booking tomorrow',
            'worker_title' => 'Booking tomorrow',
            'client_message' => 'Your cleaning service is scheduled for :date at :time. Please make sure the service address is ready for your cleaner.',
            'worker_message' => 'Booking :code is scheduled for :date at :time. Please review the service address and arrive prepared.',
        ],
        [
            'key' => '1h',
            'min_minutes' => 50,
            'max_minutes' => 70,
            'client_title' => 'Booking starts soon',
            'worker_title' => 'Booking starts in about 1 hour',
            'client_message' => 'Your cleaning service starts at :time today. Please keep your phone available in case your cleaner needs help finding the address.',
            'worker_message' => 'Booking :code starts at :time today. Please check the client pin and service notes before travelling.',
        ],
    ];
    $created = 0;

    Booking::query()
        ->with(['user', 'staffAssignments', 'cleanerApplication.user'])
        ->where('status', 'confirmed')
        ->whereDate('scheduled_date', '>=', $now->toDateString())
        ->get()
        ->each(function (Booking $booking) use ($now, $timezone, $windows, &$created): void {
            if (! $booking->scheduled_date || ! $booking->scheduled_time) {
                return;
            }

            $startsAt = Carbon::parse(
                $booking->scheduled_date->toDateString().' '.$booking->scheduled_time,
                $timezone
            );
            $minutesUntilStart = $now->diffInMinutes($startsAt, false);
            $window = collect($windows)->first(fn (array $candidate): bool => $minutesUntilStart >= $candidate['min_minutes']
                && $minutesUntilStart <= $candidate['max_minutes']
            );

            if (! $window) {
                return;
            }

            $bookingCode = 'CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);
            $date = $startsAt->format('F d, Y');
            $time = $startsAt->format('h:i A');
            $recipients = collect();

            if ($booking->user) {
                $recipients->push([
                    'user' => $booking->user,
                    'role' => 'client',
                ]);
            }

            $staffIds = collect([$booking->staff_id])
                ->merge($booking->staffAssignments->pluck('staff_id'))
                ->filter()
                ->unique()
                ->values();

            User::whereIn('id', $staffIds)->get()->each(function (User $staff) use ($recipients): void {
                $recipients->push([
                    'user' => $staff,
                    'role' => 'worker',
                ]);
            });

            if ($booking->hasAcceptedProviderAssignment() && $booking->cleanerApplication?->user) {
                $recipients->push([
                    'user' => $booking->cleanerApplication->user,
                    'role' => 'worker',
                ]);
            }

            $recipients
                ->unique(fn (array $recipient): string => $recipient['user']->id.'-'.$recipient['role'])
                ->each(function (array $recipient) use ($booking, $bookingCode, $date, $time, $window, &$created): void {
                    $user = $recipient['user'];
                    $isClient = $recipient['role'] === 'client';
                    $key = 'booking-reminder:'.$booking->id.':'.$user->id.':'.$window['key'];
                    $notification = Notification::firstOrCreate(
                        ['dedupe_key' => $key],
                        [
                            'user_id' => $user->id,
                            'booking_id' => $booking->id,
                            'subject' => 'Booking reminder - Home Cleaning Service',
                            'title' => $isClient ? $window['client_title'] : $window['worker_title'],
                            'message' => str_replace(
                                [':code', ':date', ':time'],
                                [$bookingCode, $date, $time],
                                $isClient ? $window['client_message'] : $window['worker_message']
                            ),
                            'type' => 'booking_reminder',
                            'link' => route($isClient ? 'bookings.show' : ($user->role === 'staff' ? 'staff.bookings' : 'provider.bookings'), $isClient ? $booking->id : []),
                        ]
                    );

                    if ($notification->wasRecentlyCreated) {
                        SendBookingReminderEmail::dispatch($notification->id);
                        $created++;
                    }
                });
        });

    $this->info('Created '.$created.' booking reminder notification'.($created === 1 ? '' : 's').'.');

    return Command::SUCCESS;
})->purpose('Create deduplicated in-app and email reminders for confirmed bookings');

Artisan::command('bookings:expire-unpaid-online', function (): int {
    $expiryMinutes = (int) config('cleanflow.payments.unpaid_online_expiry_minutes', 30);
    $cutoff = now()->subMinutes($expiryMinutes);
    $expired = 0;

    // Read candidate IDs first, then re-check every condition while holding the
    // booking and payment rows. This prevents a payment webhook racing the
    // expiry task from cancelling a booking that has already been paid.
    $candidateIds = Booking::query()
        ->whereIn('status', ['pending', 'confirmed'])
        ->whereHas('payments', function ($query) use ($cutoff): void {
            $query->whereIn('method', ['gcash', 'maya'])
                ->where('status', 'pending')
                ->where('created_at', '<=', $cutoff);
        })
        ->orderBy('id')
        ->pluck('id');

    foreach ($candidateIds as $bookingId) {
        $didExpire = Cache::lock('booking-payment-expiry:'.$bookingId, 30)->block(5, function () use ($bookingId, $cutoff, $expiryMinutes): bool {
            return DB::transaction(function () use ($bookingId, $cutoff, $expiryMinutes): bool {
                $booking = Booking::with(['staffAssignments', 'cleanerApplication'])
                    ->whereKey($bookingId)
                    ->lockForUpdate()
                    ->first();

                if (! $booking || ! in_array($booking->status, ['pending', 'confirmed'], true)) {
                    return false;
                }

                $payment = Payment::query()
                    ->where('booking_id', $booking->id)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();

                if (! $payment
                    || ! Booking::isDigitalPaymentMethod($payment->method)
                    || $payment->status !== 'pending'
                    || ! $payment->created_at
                    || $payment->created_at->gt($cutoff)
                    || $booking->assignedStaffIds() !== []
                    || $booking->hasAcceptedProviderAssignment()) {
                    return false;
                }

                $fromStatus = $booking->status;
                $bookingCode = 'CF-'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);

                $booking->status = 'cancelled';
                $booking->save();
                $booking->logActivity(null, 'status_updated', 'Booking automatically cancelled because online payment was not completed within the payment window.', [
                    'from_status' => $fromStatus,
                    'to_status' => 'cancelled',
                    'reason' => 'unpaid_online_payment_expired',
                    'expiry_minutes' => $expiryMinutes,
                ]);

                Notification::firstOrCreate(
                    ['dedupe_key' => 'booking-payment-expired:'.$booking->id],
                    [
                        'user_id' => $booking->user_id,
                        'booking_id' => $booking->id,
                        'subject' => 'Online payment expired - Home Cleaning Service',
                        'title' => 'Booking cancelled: payment not completed',
                        'message' => 'Booking '.$bookingCode.' was cancelled because the online payment was not completed within '.$expiryMinutes.' minutes. The schedule is available again. No payment was taken.',
                        'type' => 'booking_status',
                        'link' => route('bookings.show', $booking->id),
                    ]
                );

                return true;
            });
        });

        if ($didExpire) {
            $expired++;
        }
    }

    $this->info('Automatically cancelled '.$expired.' unpaid online booking'.($expired === 1 ? '' : 's').'.');

    return Command::SUCCESS;
})->purpose('Cancel unpaid GCash and Maya bookings after the payment window');

Artisan::command('attendance:register-device
    {serial : Unique serial number for the ESP32 unit}
    {name : Friendly device name shown in the admin UI}
    {--location= : Optional install location like Front Desk}
    {--token= : Provide a token manually instead of generating one}
    {--rotate-token : Replace the existing token for this serial number}', function () {
    $serial = (string) $this->argument('serial');
    $name = (string) $this->argument('name');
    $location = $this->option('location');
    $providedToken = $this->option('token');
    $rotateToken = (bool) $this->option('rotate-token');

    $device = Device::firstOrNew(['serial_number' => $serial]);
    $device->name = $name;

    if ($location !== null) {
        $device->location = $location;
    }

    $device->is_active = true;

    $generatedCredentials = null;

    if (! $device->exists || $rotateToken || $providedToken) {
        $generatedPair = Device::generateTokenPair();
        [$token, $secretKey] = $providedToken
            ? [$providedToken, Str::random(64)]
            : [$generatedPair['token'], $generatedPair['secret_key']];
        $hashedToken = Device::hashToken($token);

        $conflictingToken = Device::query()
            ->where('api_token', $hashedToken)
            ->when($device->exists, fn ($query) => $query->where('id', '!=', $device->id))
            ->exists();

        if ($conflictingToken) {
            $this->error('The provided token is already assigned to another device.');

            return Command::FAILURE;
        }

        $device->api_token = $hashedToken;
        $device->secret_key = $secretKey;
        $device->token_expires_at = now()->addDays(30);
        $device->last_token_rotated_at = now();
        $generatedCredentials = [
            'token' => $token,
            'secret_key' => $secretKey,
        ];
    }

    $device->save();

    if ($generatedCredentials) {
        SecurityEvent::record(
            $device->wasRecentlyCreated ? 'iot_device_credentials_created' : 'iot_device_credentials_rotated',
            null,
            [
                'source' => 'console',
                'device_id' => $device->id,
                'device_serial' => $device->serial_number,
            ]
        );
    }

    $this->table(
        ['Field', 'Value'],
        [
            ['Action', $device->wasRecentlyCreated ? 'Created' : 'Updated'],
            ['Name', $device->name],
            ['Serial', $device->serial_number],
            ['Location', $device->location ?: '-'],
            ['Token', $generatedCredentials['token'] ?? 'unchanged (not shown)'],
            ['Signing secret', $generatedCredentials['secret_key'] ?? 'unchanged (not shown)'],
            ['Active', $device->is_active ? 'yes' : 'no'],
        ]
    );

    $this->newLine();
    if ($generatedCredentials) {
        $this->warn('Keep both credentials private. The token and signing secret are shown only in this command output.');
    }

    return Command::SUCCESS;
})->purpose('Create or update an ESP32 attendance device and print its API token');

Artisan::command('storage:migrate-local
    {--dry-run : List files without copying anything}', function () {
    $migrations = [
        [
            'label' => 'private uploads',
            'source' => 'local',
            'destination' => config('filesystems.private_uploads_disk'),
        ],
        [
            'label' => 'public uploads',
            'source' => 'public',
            'destination' => config('filesystems.public_uploads_disk'),
        ],
    ];
    $dryRun = (bool) $this->option('dry-run');
    $copied = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($migrations as $migration) {
        $sourceDisk = Storage::disk($migration['source']);
        $destinationDisk = Storage::disk($migration['destination']);
        $files = collect($sourceDisk->allFiles())
            ->reject(fn (string $path) => basename($path) === '.gitignore')
            ->values();

        $this->info($migration['label'].': '.$files->count().' file(s) from '.$migration['source'].' to '.$migration['destination']);

        if ($migration['source'] === $migration['destination']) {
            $skipped += $files->count();
            $this->warn('Skipped because source and destination disks are the same.');

            continue;
        }

        foreach ($files as $path) {
            if ($dryRun) {
                $this->line('Would copy '.$path);
                $skipped++;

                continue;
            }

            if ($destinationDisk->exists($path)) {
                $this->line('Already exists: '.$path);
                $skipped++;

                continue;
            }

            $stream = $sourceDisk->readStream($path);

            if (! is_resource($stream)) {
                $this->error('Could not read: '.$path);
                $failed++;

                continue;
            }

            try {
                if ($destinationDisk->put($path, $stream)) {
                    $copied++;
                    $this->line('Copied '.$path);
                } else {
                    $this->error('Could not write: '.$path);
                    $failed++;
                }
            } finally {
                fclose($stream);
            }
        }
    }

    $this->newLine();
    $this->table(['Result', 'Count'], [
        ['Copied', $copied],
        ['Skipped', $skipped],
        ['Failed', $failed],
    ]);

    return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Copy existing local uploads to the configured private and public upload disks without deleting local files');

Artisan::command('storage:verify
    {--probe : Write, read, and delete a temporary object on each configured upload disk}', function () {
    $disks = [
        'private uploads' => config('filesystems.private_uploads_disk'),
        'public uploads' => config('filesystems.public_uploads_disk'),
        'booking proof uploads' => config('filesystems.proof_uploads_disk'),
    ];
    $probe = (bool) $this->option('probe');
    $failures = 0;

    foreach ($disks as $label => $diskName) {
        $diskConfig = (array) config('filesystems.disks.'.$diskName, []);
        $driver = $diskConfig['driver'] ?? 'unknown';
        $this->line($label.': '.$diskName.' ('.$driver.')');

        if (! $probe) {
            continue;
        }

        $path = 'cleanflow-health-check/'.Str::uuid().'.txt';
        $contents = 'CleanFlow storage verification '.now()->toIso8601String();
        $storage = Storage::disk($diskName);

        try {
            if (! $storage->put($path, $contents)) {
                throw new RuntimeException('write returned false');
            }

            if ($storage->get($path) !== $contents) {
                throw new RuntimeException('read content did not match');
            }

            if (! $storage->delete($path)) {
                throw new RuntimeException('delete returned false');
            }

            $this->info('Probe passed: '.$label);
        } catch (Throwable $exception) {
            $failures++;
            $this->error('Probe failed: '.$label.' — '.$exception->getMessage());
        } finally {
            try {
                if ($storage->exists($path)) {
                    $storage->delete($path);
                }
            } catch (Throwable) {
                $this->error('Cleanup failed: '.$label.' — inspect '.$path);
            }
        }
    }

    if (! $probe) {
        $this->comment('Configuration only. Add --probe to test remote read/write/delete access.');
    }

    return $failures > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Inspect configured upload disks and optionally probe remote storage access');

Artisan::command('storage:migrate-proof-media
    {--dry-run : List proof files without copying or deleting anything}
    {--include-ratings : Also migrate customer rating photos}
    {--delete-source : Delete the legacy public copy after a verified copy}', function () {
    $sourceDiskName = config('filesystems.public_uploads_disk');
    $destinationDiskName = config('filesystems.proof_uploads_disk');
    $source = Storage::disk($sourceDiskName);
    $destination = Storage::disk($destinationDiskName);
    $dryRun = (bool) $this->option('dry-run');
    $deleteSource = (bool) $this->option('delete-source');
    $copied = 0;
    $deleted = 0;
    $skipped = 0;
    $failed = 0;

    $streamsMatch = static function ($source, $destination, string $path): bool {
        try {
            if ($source->size($path) !== $destination->size($path)) {
                return false;
            }

            $sourceStream = $source->readStream($path);
            $destinationStream = $destination->readStream($path);

            if (! is_resource($sourceStream) || ! is_resource($destinationStream)) {
                if (is_resource($sourceStream)) {
                    fclose($sourceStream);
                }

                if (is_resource($destinationStream)) {
                    fclose($destinationStream);
                }

                return false;
            }

            $sourceHash = hash_init('sha256');
            $destinationHash = hash_init('sha256');

            try {
                while (! feof($sourceStream) || ! feof($destinationStream)) {
                    $sourceChunk = feof($sourceStream) ? '' : fread($sourceStream, 1024 * 1024);
                    $destinationChunk = feof($destinationStream) ? '' : fread($destinationStream, 1024 * 1024);

                    if ($sourceChunk === false || $destinationChunk === false || strlen($sourceChunk) !== strlen($destinationChunk)) {
                        return false;
                    }

                    hash_update($sourceHash, $sourceChunk);
                    hash_update($destinationHash, $destinationChunk);
                }

                return hash_final($sourceHash) === hash_final($destinationHash);
            } finally {
                fclose($sourceStream);
                fclose($destinationStream);
            }
        } catch (Throwable) {
            return false;
        }
    };

    if ($sourceDiskName === $destinationDiskName) {
        $this->warn('Source and destination disks are the same; nothing to migrate.');

        return Command::SUCCESS;
    }

    $mediaRecords = BookingServiceProof::query()
        ->orderBy('id')
        ->get(['id', 'file_path'])
        ->map(fn (BookingServiceProof $proof) => ['path' => $proof->file_path]);

    if ($this->option('include-ratings')) {
        $mediaRecords = $mediaRecords->concat(
            Rating::query()
                ->whereNotNull('photo')
                ->orderBy('id')
                ->get(['id', 'photo'])
                ->map(fn (Rating $rating) => ['path' => $rating->photo])
        );
    }

    $this->info('Found '.$mediaRecords->count().' booking media record(s).');

    foreach ($mediaRecords as $media) {
        $path = (string) $media['path'];
        $sourceExists = $source->exists($path);
        $destinationExists = $destination->exists($path);

        if (! $sourceExists && $destinationExists) {
            $this->line('Already migrated: '.$path);
            $skipped++;

            continue;
        }

        if (! $sourceExists) {
            $this->error('Missing source and destination: '.$path);
            $failed++;

            continue;
        }

        if ($dryRun) {
            $this->line(($destinationExists ? 'Would verify' : 'Would copy').' '.$path.($deleteSource ? ' and delete the source' : ''));
            $skipped++;

            continue;
        }

        if (! $destinationExists) {
            $stream = $source->readStream($path);

            if (! is_resource($stream)) {
                $this->error('Could not read: '.$path);
                $failed++;

                continue;
            }

            try {
                if (! $destination->put($path, $stream) || ! $destination->exists($path)) {
                    $this->error('Could not verify destination: '.$path);
                    $failed++;

                    continue;
                }

                if (! $streamsMatch($source, $destination, $path)) {
                    $this->error('Destination content does not match source: '.$path);
                    $failed++;

                    continue;
                }

                $copied++;
                $this->line('Copied '.$path);
            } finally {
                fclose($stream);
            }
        } else {
            if (! $streamsMatch($source, $destination, $path)) {
                $this->error('Existing destination content does not match source: '.$path);
                $failed++;

                continue;
            }

            $skipped++;
            $this->line('Destination verified: '.$path);
        }

        if ($deleteSource) {
            if ($source->delete($path)) {
                $deleted++;
                $this->line('Deleted legacy source '.$path);
            } else {
                $this->error('Could not delete legacy source: '.$path);
                $failed++;
            }
        }
    }

    $this->newLine();
    $this->table(['Result', 'Count'], [
        ['Copied', $copied],
        ['Deleted legacy source', $deleted],
        ['Skipped', $skipped],
        ['Failed', $failed],
    ]);

    return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Move booking proof media and optional rating photos to the private booking-media disk');

Artisan::command('cleanflow:verify
    {--probe : Also test write, read, and delete access on all upload disks}', function () {
    $checks = [];
    $failures = 0;

    try {
        DB::connection()->getPdo();
        $checks[] = ['Database', 'pass', DB::getDefaultConnection()];
    } catch (Throwable $exception) {
        $checks[] = ['Database', 'fail', $exception->getMessage() ?: 'connection failed'];
        $failures++;
    }

    $queueDriver = (string) config('queue.default');
    $queueSafe = ! in_array($queueDriver, ['sync', 'null'], true);
    $checks[] = ['Queue', $queueSafe ? 'pass' : 'fail', $queueDriver];
    $failures += $queueSafe ? 0 : 1;

    $mailDriver = (string) config('mail.default');
    $mailSafe = ! in_array($mailDriver, ['log', 'array'], true);
    $checks[] = ['Mail', $mailSafe ? 'pass' : 'fail', $mailDriver];
    $failures += $mailSafe ? 0 : 1;

    $cacheStore = (string) config('cache.default');
    $cacheSafe = ! in_array($cacheStore, ['array', 'file'], true);
    $checks[] = ['Cache', $cacheSafe ? 'pass' : 'fail', $cacheStore];
    $failures += $cacheSafe ? 0 : 1;

    $sessionDriver = (string) config('session.driver');
    $sessionSafe = $sessionDriver !== 'file';
    $checks[] = ['Session', $sessionSafe ? 'pass' : 'fail', $sessionDriver];
    $failures += $sessionSafe ? 0 : 1;

    $failedJobsTableExists = Schema::hasTable('failed_jobs');
    $failedJobCount = $failedJobsTableExists ? DB::table('failed_jobs')->count() : null;
    $failedJobsSafe = $failedJobsTableExists && $failedJobCount === 0;
    $failedJobsDetail = $failedJobsTableExists ? (string) $failedJobCount.' failed job(s)' : 'failed_jobs table missing';
    $checks[] = ['Failed jobs', $failedJobsSafe ? 'pass' : 'fail', $failedJobsDetail];
    $failures += $failedJobsSafe ? 0 : 1;

    $this->table(['Check', 'Result', 'Detail'], $checks);

    if ($this->option('probe')) {
        $storageExitCode = $this->call('storage:verify', ['--probe' => true]);
        $failures += $storageExitCode === Command::SUCCESS ? 0 : 1;
    }

    if ($failures > 0) {
        $this->error('CleanFlow verification found '.$failures.' failure(s).');

        return Command::FAILURE;
    }

    $this->info('CleanFlow verification passed.');

    return Command::SUCCESS;
})->purpose('Verify database, queue, mail, cache, session, failed jobs, and optional storage access');

Artisan::command('database:preflight', function () {
    $checks = [];
    $failures = 0;

    $checkDuplicates = function (string $label, Closure $query, string $missingDetail = 'not applicable') use (&$checks, &$failures): void {
        try {
            $duplicates = $query();
            $count = is_countable($duplicates) ? count($duplicates) : 0;
            $checks[] = [$label, $count === 0 ? 'pass' : 'fail', $count === 0 ? 'no duplicates' : $count.' duplicate group(s)'];
            $failures += $count === 0 ? 0 : 1;
        } catch (Throwable $exception) {
            $checks[] = [$label, 'fail', $exception->getMessage() ?: $missingDetail];
            $failures++;
        }
    };

    if (Schema::hasTable('users') && Schema::hasColumn('users', 'email')) {
        $checkDuplicates('User emails', fn () => DB::table('users')
            ->selectRaw('LOWER(TRIM(email)) AS normalized_email')
            ->groupByRaw('LOWER(TRIM(email))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_email'));
    } else {
        $checks[] = ['User emails', 'skip', 'users.email not available'];
    }

    if (Schema::hasTable('cleaner_applications') && Schema::hasColumn('cleaner_applications', 'email')) {
        $checkDuplicates('Active application emails', fn () => DB::table('cleaner_applications')
            ->whereIn('status', ['pending', 'approved'])
            ->selectRaw('LOWER(TRIM(email)) AS normalized_email')
            ->groupByRaw('LOWER(TRIM(email))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_email'));
    } else {
        $checks[] = ['Active application emails', 'skip', 'cleaner_applications.email not available'];
    }

    if (Schema::hasTable('payments')) {
        if (Schema::hasColumn('payments', 'booking_id')) {
            $checkDuplicates('Payments per booking', fn () => DB::table('payments')
                ->select('booking_id')
                ->groupBy('booking_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('booking_id'));
        } else {
            $checks[] = ['Payments per booking', 'skip', 'payments.booking_id not available'];
        }

        foreach (['checkout_session_id', 'provider_payment_id', 'reference'] as $column) {
            if (! Schema::hasColumn('payments', $column)) {
                $checks[] = ['Payment '.$column, 'skip', 'column not available'];

                continue;
            }

            $checkDuplicates('Payment '.$column, fn () => DB::table('payments')
                ->whereNotNull($column)
                ->select('booking_id', $column)
                ->groupBy('booking_id', $column)
                ->havingRaw('COUNT(*) > 1')
                ->pluck($column));
        }

        if (Schema::hasColumn('payments', 'receipt_number')) {
            $checkDuplicates('Payment receipt_number', fn () => DB::table('payments')
                ->whereNotNull('receipt_number')
                ->select('receipt_number')
                ->groupBy('receipt_number')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('receipt_number'));
        } else {
            $checks[] = ['Payment receipt_number', 'skip', 'column not available'];
        }
    } else {
        $checks[] = ['Payments', 'skip', 'payments table not available'];
    }

    $this->table(['Check', 'Result', 'Detail'], $checks);

    if ($failures > 0) {
        $this->error('Database preflight found '.$failures.' migration blocker(s).');

        return Command::FAILURE;
    }

    $this->info('Database preflight passed.');

    return Command::SUCCESS;
})->purpose('Find duplicate data that would block integrity migrations without changing the database');

Artisan::command('database:backup-cloud', function () {
    $backupController = app(AdminSettingsController::class);
    $backupPath = null;

    try {
        $backupPath = $backupController->createDatabaseBackup();
        $remotePath = $backupController->storeDatabaseBackupRemotely($backupPath);

        $this->info('Database backup uploaded: '.$remotePath);

        return Command::SUCCESS;
    } catch (Throwable $exception) {
        $this->error($exception->getMessage() ?: 'Cloud database backup failed.');

        return Command::FAILURE;
    } finally {
        if ($backupPath && is_file($backupPath)) {
            @unlink($backupPath);
        }
    }
})->purpose('Create and upload a database backup to the configured private cloud disk');

Artisan::command('cleaner-applications:purge-sensitive-data
    {--dry-run : Report eligible applications without deleting anything}', function () {
    $cutoff = now()->subDays((int) config('cleanflow.privacy.rejected_application_retention_days', 180));
    $disk = Storage::disk(config('filesystems.private_uploads_disk'));
    $dryRun = (bool) $this->option('dry-run');
    $purged = 0;
    $failed = 0;

    CleanerApplication::query()
        ->where('status', CleanerApplication::STATUS_REJECTED)
        ->whereNull('sensitive_data_purged_at')
        ->where('created_at', '<=', $cutoff)
        ->with('documents')
        ->chunkById(100, function ($applications) use ($disk, $dryRun, &$purged, &$failed): void {
            foreach ($applications as $application) {
                $paths = collect([
                    $application->profile_photo_path,
                    $application->business_logo_path,
                    $application->government_id_front_document_path,
                    $application->government_id_back_document_path,
                    $application->government_id_document_path,
                    $application->nbi_clearance_document_path,
                    $application->selfie_with_id_path,
                ])->merge($application->documents->pluck('file_path'))
                    ->filter()
                    ->unique()
                    ->values();

                if ($dryRun) {
                    $this->line('Would purge application #'.$application->id.' ('.$paths->count().' file(s))');
                    $purged++;

                    continue;
                }

                try {
                    foreach ($paths as $path) {
                        if ($disk->exists($path) && ! $disk->delete($path)) {
                            throw new RuntimeException('Could not delete '.$path);
                        }
                    }

                    $application->documents()->delete();
                    $application->forceFill([
                        'business_name' => '[Purged application #'.$application->id.']',
                        'contact_person' => 'Purged applicant',
                        'email' => 'purged-'.$application->id.'@invalid.cleanflow',
                        'phone' => '00000000000',
                        'date_of_birth' => null,
                        'current_address' => null,
                        'profile_photo_path' => null,
                        'profile_photo_original_filename' => null,
                        'business_logo_path' => null,
                        'business_logo_original_filename' => null,
                        'government_id_type' => null,
                        'government_id_number' => null,
                        'government_id_front_document_path' => null,
                        'government_id_front_document_original_filename' => null,
                        'government_id_back_document_path' => null,
                        'government_id_back_document_original_filename' => null,
                        'government_id_document_path' => null,
                        'government_id_document_original_filename' => null,
                        'nbi_clearance_number' => null,
                        'nbi_clearance_document_path' => null,
                        'nbi_clearance_document_original_filename' => null,
                        'selfie_with_id_path' => null,
                        'selfie_with_id_original_filename' => null,
                        'verification_notes' => null,
                        'sensitive_data_purged_at' => now(),
                    ])->save();
                    $purged++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->error('Could not purge application #'.$application->id.': '.$exception->getMessage());
                }
            }
        });

    $this->table(['Result', 'Count'], [
        ['Purged', $purged],
        ['Failed', $failed],
    ]);

    return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Delete expired rejected-applicant identity data while retaining an anonymized audit record');

Artisan::command('security:prune-events
    {--retention-days= : Override the configured security event retention period}
    {--dry-run : Report eligible events without deleting anything}', function () {
    $retentionDays = (int) ($this->option('retention-days') ?: config('cleanflow.privacy.security_event_retention_days', 365));

    if ($retentionDays < 30) {
        $this->error('Security event retention must be at least 30 days.');

        return Command::FAILURE;
    }

    $cutoff = now()->subDays($retentionDays);
    $query = DB::table('security_events')->where('created_at', '<', $cutoff);
    $eligible = (clone $query)->count();

    if ($this->option('dry-run')) {
        $this->info('Would prune '.$eligible.' security event(s) older than '.$cutoff->toDateTimeString().'.');

        return Command::SUCCESS;
    }

    $deleted = $query->delete();
    $this->info('Pruned '.$deleted.' security event(s) older than '.$cutoff->toDateTimeString().'.');

    return Command::SUCCESS;
})->purpose('Prune security events beyond the configured retention period');
