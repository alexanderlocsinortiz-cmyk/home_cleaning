<?php

namespace Tests\Unit;

use App\Http\Controllers\AdminSettingsController;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class DatabaseBackupTest extends TestCase
{
    public function test_postgres_dump_failure_does_not_switch_to_sql_fallback(): void
    {
        $originalDefaultConnection = config('database.default');
        $originalDumpPath = config('filesystems.database_backup_pg_dump_path');

        config([
            'database.default' => 'backup-test-postgres',
            'filesystems.database_backup_pg_dump_path' => PHP_BINARY,
            'database.connections.backup-test-postgres' => [
                'driver' => 'pgsql',
                'host' => '127.0.0.1',
                'port' => 5432,
                'database' => 'cleanflow',
                'username' => 'cleanflow',
                'password' => 'password',
            ],
        ]);

        $exception = null;

        try {
            app(AdminSettingsController::class)->createDatabaseBackup();
        } catch (Throwable $caught) {
            $exception = $caught;
        } finally {
            DB::purge('backup-test-postgres');
            config([
                'database.default' => $originalDefaultConnection,
                'filesystems.database_backup_pg_dump_path' => $originalDumpPath,
            ]);
        }

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertSame(RuntimeException::class, $exception::class);
        $this->assertStringContainsString('Database backup failed while running pg_dump', $exception->getMessage());
    }
}
