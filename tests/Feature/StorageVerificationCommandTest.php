<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageVerificationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_verification_probes_private_and_public_disks(): void
    {
        Config::set('filesystems.disks.verification-private', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/verification-private'),
        ]);
        Config::set('filesystems.disks.verification-public', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/verification-public'),
        ]);
        Config::set('filesystems.private_uploads_disk', 'verification-private');
        Config::set('filesystems.public_uploads_disk', 'verification-public');

        Storage::fake('verification-private');
        Storage::fake('verification-public');

        $this->artisan('storage:verify --probe')
            ->assertExitCode(0)
            ->expectsOutputToContain('Probe passed: private uploads')
            ->expectsOutputToContain('Probe passed: public uploads');
    }
}

