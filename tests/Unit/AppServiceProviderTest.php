<?php

namespace Tests\Unit;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Config;
use LogicException;
use ReflectionMethod;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_production_upload_guard_rejects_a_custom_named_local_disk(): void
    {
        Config::set([
            'filesystems.private_uploads_disk' => 'private_uploads',
            'filesystems.public_uploads_disk' => 'public_uploads',
            'filesystems.disks.private_uploads' => ['driver' => 'local'],
            'filesystems.disks.public_uploads' => ['driver' => 's3'],
        ]);

        $previousEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        $method = new ReflectionMethod(AppServiceProvider::class, 'ensureProductionUploadsAreDurable');
        $method->setAccessible(true);

        try {
            $this->expectException(LogicException::class);
            $method->invoke(new AppServiceProvider(app()));
        } finally {
            app()->detectEnvironment(fn () => $previousEnvironment);
        }
    }

    public function test_production_runtime_guard_rejects_unsafe_defaults(): void
    {
        Config::set([
            'app.debug' => true,
            'app.key' => null,
            'queue.default' => 'sync',
            'mail.default' => 'log',
            'cache.default' => 'file',
            'session.driver' => 'file',
        ]);

        $previousEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        $method = new ReflectionMethod(AppServiceProvider::class, 'ensureProductionRuntimeIsSafe');
        $method->setAccessible(true);

        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('APP_DEBUG must be false');
            $method->invoke(new AppServiceProvider(app()));
        } finally {
            app()->detectEnvironment(fn () => $previousEnvironment);
        }
    }

    public function test_production_upload_guard_rejects_using_the_public_disk_for_proofs(): void
    {
        Config::set([
            'filesystems.private_uploads_disk' => 'private_uploads',
            'filesystems.public_uploads_disk' => 'public_uploads',
            'filesystems.proof_uploads_disk' => 'public_uploads',
            'filesystems.disks.private_uploads' => ['driver' => 's3'],
            'filesystems.disks.public_uploads' => ['driver' => 's3', 'visibility' => 'public'],
        ]);

        $previousEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        $method = new ReflectionMethod(AppServiceProvider::class, 'ensureProductionUploadsAreDurable');
        $method->setAccessible(true);

        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('public uploads disk for booking proof media');
            $method->invoke(new AppServiceProvider(app()));
        } finally {
            app()->detectEnvironment(fn () => $previousEnvironment);
        }
    }

    public function test_production_upload_guard_rejects_public_proof_visibility(): void
    {
        Config::set([
            'filesystems.private_uploads_disk' => 'private_uploads',
            'filesystems.public_uploads_disk' => 'public_uploads',
            'filesystems.proof_uploads_disk' => 'proof_uploads',
            'filesystems.disks.private_uploads' => ['driver' => 's3'],
            'filesystems.disks.public_uploads' => ['driver' => 's3', 'visibility' => 'public'],
            'filesystems.disks.proof_uploads' => ['driver' => 's3', 'visibility' => 'public'],
        ]);

        $previousEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        $method = new ReflectionMethod(AppServiceProvider::class, 'ensureProductionUploadsAreDurable');
        $method->setAccessible(true);

        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('private object visibility');
            $method->invoke(new AppServiceProvider(app()));
        } finally {
            app()->detectEnvironment(fn () => $previousEnvironment);
        }
    }

    public function test_production_upload_guard_rejects_public_private_upload_configuration(): void
    {
        Config::set([
            'filesystems.private_uploads_disk' => 'private_uploads',
            'filesystems.public_uploads_disk' => 'public_uploads',
            'filesystems.proof_uploads_disk' => 'proof_uploads',
            'filesystems.database_backup_disk' => 'database_backups',
            'filesystems.disks.private_uploads' => ['driver' => 's3', 'visibility' => 'public'],
            'filesystems.disks.public_uploads' => ['driver' => 's3'],
            'filesystems.disks.proof_uploads' => ['driver' => 's3'],
            'filesystems.disks.database_backups' => ['driver' => 's3'],
        ]);

        $previousEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        $method = new ReflectionMethod(AppServiceProvider::class, 'ensureProductionUploadsAreDurable');
        $method->setAccessible(true);

        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('private uploads must use private object visibility');
            $method->invoke(new AppServiceProvider(app()));
        } finally {
            app()->detectEnvironment(fn () => $previousEnvironment);
        }
    }

    public function test_production_upload_guard_rejects_public_database_backup_disk(): void
    {
        Config::set([
            'filesystems.private_uploads_disk' => 'private_uploads',
            'filesystems.public_uploads_disk' => 'public_uploads',
            'filesystems.proof_uploads_disk' => 'proof_uploads',
            'filesystems.database_backup_disk' => 'public_uploads',
            'filesystems.disks.private_uploads' => ['driver' => 's3'],
            'filesystems.disks.public_uploads' => ['driver' => 's3'],
            'filesystems.disks.proof_uploads' => ['driver' => 's3'],
        ]);

        $previousEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        $method = new ReflectionMethod(AppServiceProvider::class, 'ensureProductionUploadsAreDurable');
        $method->setAccessible(true);

        try {
            $this->expectException(LogicException::class);
            $this->expectExceptionMessage('public uploads disk for database backups');
            $method->invoke(new AppServiceProvider(app()));
        } finally {
            app()->detectEnvironment(fn () => $previousEnvironment);
        }
    }
}
