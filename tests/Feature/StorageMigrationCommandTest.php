<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageMigrationCommandTest extends TestCase
{
    public function test_storage_migration_copies_private_and_public_files_without_deleting_sources(): void
    {
        Config::set('filesystems.disks.migration-private', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/migration-private'),
        ]);
        Config::set('filesystems.disks.migration-public', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/migration-public'),
        ]);
        Config::set('filesystems.private_uploads_disk', 'migration-private');
        Config::set('filesystems.public_uploads_disk', 'migration-public');

        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('migration-private');
        Storage::fake('migration-public');

        Storage::disk('local')->put('cleaner-applications/1/id.png', 'private file');
        Storage::disk('public')->put('booking-proofs/before/proof.png', 'public file');

        $this->artisan('storage:migrate-local')
            ->assertExitCode(0)
            ->expectsOutputToContain('Copied cleaner-applications/1/id.png')
            ->expectsOutputToContain('Copied booking-proofs/before/proof.png');

        Storage::disk('migration-private')->assertExists('cleaner-applications/1/id.png');
        Storage::disk('migration-public')->assertExists('booking-proofs/before/proof.png');
        Storage::disk('local')->assertExists('cleaner-applications/1/id.png');
        Storage::disk('public')->assertExists('booking-proofs/before/proof.png');
    }
}
