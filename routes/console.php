<?php

use App\Http\Controllers\AdminSettingsController;
use App\Models\Device;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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

    if (! $device->exists || $rotateToken || $providedToken) {
        $token = $providedToken ?: Str::random(64);

        $conflictingToken = Device::query()
            ->where('api_token', $token)
            ->when($device->exists, fn ($query) => $query->where('id', '!=', $device->id))
            ->exists();

        if ($conflictingToken) {
            $this->error('The provided token is already assigned to another device.');

            return Command::FAILURE;
        }

        $device->api_token = $token;
    }

    $device->save();

    $this->table(
        ['Field', 'Value'],
        [
            ['Action', $device->wasRecentlyCreated ? 'Created' : 'Updated'],
            ['Name', $device->name],
            ['Serial', $device->serial_number],
            ['Location', $device->location ?: '-'],
            ['Token', $device->api_token],
            ['Active', $device->is_active ? 'yes' : 'no'],
        ]
    );

    $this->newLine();
    $this->warn('Keep the token private. Put it into the ESP32 sketch as DEVICE_TOKEN.');

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
                throw new \RuntimeException('write returned false');
            }

            if ($storage->get($path) !== $contents) {
                throw new \RuntimeException('read content did not match');
            }

            if (! $storage->delete($path)) {
                throw new \RuntimeException('delete returned false');
            }

            $this->info('Probe passed: '.$label);
        } catch (\Throwable $exception) {
            $failures++;
            $this->error('Probe failed: '.$label.' — '.$exception->getMessage());
        } finally {
            try {
                if ($storage->exists($path)) {
                    $storage->delete($path);
                }
            } catch (\Throwable) {
                $this->error('Cleanup failed: '.$label.' — inspect '.$path);
            }
        }
    }

    if (! $probe) {
        $this->comment('Configuration only. Add --probe to test remote read/write/delete access.');
    }

    return $failures > 0 ? Command::FAILURE : Command::SUCCESS;
})->purpose('Inspect configured upload disks and optionally probe remote storage access');

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
