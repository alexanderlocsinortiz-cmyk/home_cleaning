<?php

namespace App\Http\Controllers;

use App\Models\AccessRestrictionHistory;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class AdminSettingsController extends Controller
{
    public const STAFF_PAGES = [
        'dashboard' => 'Dashboard',
        'bookings' => 'Bookings',
        'schedule' => 'Schedule',
        'performance' => 'Performance',
        'notifications' => 'Notifications and fingerprint consent',
        'profile' => 'Profile',
        'service_areas' => 'Service Areas',
    ];

    public function index()
    {
        $staff = User::where('role', 'staff')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $clients = User::where('role', 'client')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $restrictionHistories = AccessRestrictionHistory::with(['actorUser', 'targetUser'])
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.settings', [
            'staff' => $staff,
            'clients' => $clients,
            'staffPages' => self::STAFF_PAGES,
            'restrictionHistories' => $restrictionHistories,
            'generalSettings' => SiteSetting::current(),
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $settings = SiteSetting::current();

        $validated = $request->validate([
            'website_name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_address' => ['nullable', 'string', 'max:180'],
            'office_hours' => ['nullable', 'string', 'max:120'],
            'admin_name' => ['nullable', 'string', 'max:120'],
            'admin_email' => ['nullable', 'email', 'max:120'],
            'admin_phone' => ['nullable', 'string', 'max:30'],
            'admin_current_password' => ['nullable', 'string'],
            'admin_new_password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (filled($validated['admin_new_password'] ?? null)) {
            if (blank($validated['admin_current_password'] ?? null) || ! Hash::check($validated['admin_current_password'], $request->user()->password)) {
                return back()
                    ->withErrors(['admin_current_password' => 'Current password is incorrect.'])
                    ->withInput($request->except(['admin_current_password', 'admin_new_password', 'admin_new_password_confirmation', 'logo']));
            }

            $request->user()->update([
                'password' => $validated['admin_new_password'],
            ]);
        }

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk(config('filesystems.public_uploads_disk'))->delete($settings->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('site', config('filesystems.public_uploads_disk'));
        }

        unset(
            $validated['logo'],
            $validated['admin_current_password'],
            $validated['admin_new_password'],
            $validated['admin_new_password_confirmation'],
        );

        $settings->update($validated);

        return back()->with('success', filled($request->input('admin_new_password')) ? 'General settings and admin password updated.' : 'General settings updated.');
    }

    public function downloadDatabaseBackup(Request $request)
    {
        $settings = SiteSetting::current();

        if (! $settings->database_backup_password_hash) {
            return redirect()
                ->to(route('admin.settings').'#database-backup')
                ->withErrors(['database_backup_password' => 'Set a database backup password before downloading backups.']);
        }

        $validator = Validator::make($request->all(), [
            'database_backup_password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->to(route('admin.settings').'#database-backup')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        if (! Hash::check($validated['database_backup_password'], $settings->database_backup_password_hash)) {
            return redirect()
                ->to(route('admin.settings').'#database-backup')
                ->withErrors(['database_backup_password' => 'Database backup password is incorrect.']);
        }

        try {
            $backupPath = $this->createDatabaseBackup();
        } catch (Throwable $exception) {
            Log::error('Database backup download failed.', [
                'admin_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->to(route('admin.settings').'#database-backup')
                ->withErrors(['database_backup' => $exception->getMessage() ?: 'Database backup failed.']);
        }

        return response()
            ->download($backupPath, basename($backupPath))
            ->deleteFileAfterSend(true);
    }

    public function uploadDatabaseBackup(Request $request)
    {
        $settings = SiteSetting::current();

        if (! $settings->database_backup_password_hash) {
            return redirect()
                ->to(route('admin.settings').'#database-backup')
                ->withErrors(['database_backup_password' => 'Set a database backup password before uploading backups.']);
        }

        $validator = Validator::make($request->all(), [
            'database_backup_password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->to(route('admin.settings').'#database-backup')
                ->withErrors($validator)
                ->withInput();
        }

        if (! Hash::check($validator->validated()['database_backup_password'], $settings->database_backup_password_hash)) {
            return redirect()
                ->to(route('admin.settings').'#database-backup')
                ->withErrors(['database_backup_password' => 'Database backup password is incorrect.']);
        }

        try {
            $backupPath = $this->createDatabaseBackup();
            $remotePath = $this->storeDatabaseBackupRemotely($backupPath);

            return redirect()
                ->to(route('admin.settings').'#database-backup')
                ->with('success', 'Database backup uploaded to private cloud storage: '.$remotePath);
        } catch (Throwable $exception) {
            Log::error('Cloud database backup failed.', [
                'admin_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->to(route('admin.settings').'#database-backup')
                ->withErrors(['database_backup' => $exception->getMessage() ?: 'Cloud database backup failed.']);
        } finally {
            if (isset($backupPath) && is_file($backupPath)) {
                @unlink($backupPath);
            }
        }
    }

    public function updateDatabaseBackupPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'database_backup_admin_password' => ['required', 'string'],
            'database_backup_new_password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->to(route('admin.settings').'#database-backup')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        if (! Hash::check($validated['database_backup_admin_password'], $request->user()->password)) {
            return redirect()
                ->to(route('admin.settings').'#database-backup')
                ->withErrors(['database_backup_admin_password' => 'Admin password is incorrect.']);
        }

        SiteSetting::current()->update([
            'database_backup_password_hash' => Hash::make($validated['database_backup_new_password']),
        ]);

        return redirect()
            ->to(route('admin.settings').'#database-backup')
            ->with('success', 'Database backup password updated.');
    }

    public function updateUserAccess(Request $request, User $user)
    {
        abort_if($user->role === 'admin', 403);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['restrict', 'clear'])],
            'restriction_days' => ['required_if:action,restrict', 'nullable', 'integer', 'min:1', 'max:365'],
            'access_restriction_reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['action'] === 'clear') {
            $previousUntil = $user->access_restricted_until;
            $previousReason = $user->access_restriction_reason;

            $user->update([
                'access_restricted_until' => null,
                'access_restriction_reason' => null,
            ]);

            $this->recordAccessHistory($request, $user, 'account_restriction_cleared', [
                'restricted_until' => $previousUntil,
                'reason' => $previousReason,
            ]);

            return back()->with('success', $user->display_name.' access restriction cleared.');
        }

        $days = (int) $validated['restriction_days'];
        $restrictedUntil = now()->addDays($days);
        $reason = $validated['access_restriction_reason'] ?: 'Restricted by admin.';

        $user->update([
            'access_restricted_until' => $restrictedUntil,
            'access_restriction_reason' => $reason,
        ]);

        $this->recordAccessHistory($request, $user, 'account_restricted', [
            'duration_days' => $days,
            'restricted_until' => $restrictedUntil,
            'reason' => $reason,
        ]);

        return back()->with('success', $user->display_name.' restricted for '.$days.' day'.($days === 1 ? '' : 's').'.');
    }

    public function updateStaffPages(Request $request, User $user)
    {
        abort_unless($user->role === 'staff', 404);

        $validated = $request->validate([
            'restricted_pages' => ['nullable', 'array'],
            'restricted_pages.*' => ['string', Rule::in(array_keys(self::STAFF_PAGES))],
        ]);

        $previousPages = collect($user->staff_restricted_pages ?? [])->sort()->values()->all();
        $restrictedPages = collect($validated['restricted_pages'] ?? [])->sort()->values()->all();

        $user->update([
            'staff_restricted_pages' => $restrictedPages,
        ]);

        if ($previousPages !== $restrictedPages) {
            $this->recordAccessHistory($request, $user, 'staff_pages_updated', [
                'restricted_pages' => $restrictedPages,
                'meta' => [
                    'previous_pages' => $previousPages,
                ],
            ]);
        }

        return back()->with('success', $user->display_name.' staff page access updated.');
    }

    private function recordAccessHistory(Request $request, User $target, string $action, array $data = []): void
    {
        AccessRestrictionHistory::create([
            'target_user_id' => $target->id,
            'actor_user_id' => $request->user()?->id,
            'target_name' => $target->display_name,
            'target_email' => $target->email,
            'target_role' => $target->role,
            'action' => $action,
            'duration_days' => $data['duration_days'] ?? null,
            'restricted_until' => $data['restricted_until'] ?? null,
            'reason' => $data['reason'] ?? null,
            'restricted_pages' => $data['restricted_pages'] ?? null,
            'meta' => $data['meta'] ?? null,
        ]);
    }

    public function createDatabaseBackup(): string
    {
        $connectionName = DB::getDefaultConnection();
        $connection = config("database.connections.{$connectionName}");
        $driver = $connection['driver'] ?? null;
        $backupDirectory = storage_path('app/backups');

        if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0755, true) && ! is_dir($backupDirectory)) {
            throw new RuntimeException('Could not create the database backup directory.');
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $baseName = 'cleanflow-'.$connectionName.'-'.$timestamp;

        return match ($driver) {
            'sqlite' => $this->backupSqliteDatabase($connection, $backupDirectory, $baseName),
            'pgsql' => $this->backupPostgresDatabase($connection, $backupDirectory, $baseName),
            'mysql', 'mariadb' => $this->backupMysqlDatabase($connection, $backupDirectory, $baseName),
            default => throw new RuntimeException("Database backups are not configured for the {$driver} driver."),
        };
    }

    public function storeDatabaseBackupRemotely(string $backupPath): string
    {
        $diskName = (string) config('filesystems.database_backup_disk');
        $diskConfig = (array) config('filesystems.disks.'.$diskName, []);
        $driver = $diskConfig['driver'] ?? null;

        if ($driver === 'local' && ! app()->environment('testing')) {
            throw new RuntimeException('Cloud backup storage is not configured. Set DATABASE_BACKUP_DISK to a private S3-compatible disk.');
        }

        if (! is_file($backupPath) || filesize($backupPath) === 0) {
            throw new RuntimeException('The generated database backup is empty or missing.');
        }

        $prefix = trim((string) config('filesystems.database_backup_prefix', 'database-backups'), '/');
        $remotePath = ($prefix !== '' ? $prefix.'/' : '').basename($backupPath);
        $storage = Storage::disk($diskName);
        $stream = fopen($backupPath, 'rb');

        if (! is_resource($stream)) {
            throw new RuntimeException('The generated database backup could not be opened for upload.');
        }

        try {
            if (! $storage->put($remotePath, $stream)) {
                throw new RuntimeException('The cloud storage upload returned a failure.');
            }
        } finally {
            fclose($stream);
        }

        if (! $storage->exists($remotePath)) {
            throw new RuntimeException('Cloud storage did not confirm the uploaded backup.');
        }

        $retentionCount = (int) config('filesystems.database_backup_retention_count', 30);
        $backupFiles = collect($storage->files($prefix))
            ->filter(fn (string $path) => str_ends_with($path, '.dump') || str_ends_with($path, '.sql') || str_ends_with($path, '.sqlite'))
            ->sortByDesc(function (string $path) use ($storage): int {
                try {
                    return (int) $storage->lastModified($path);
                } catch (Throwable) {
                    return 0;
                }
            })
            ->values();

        foreach ($backupFiles->slice($retentionCount) as $oldBackup) {
            $storage->delete($oldBackup);
        }

        return $remotePath;
    }

    private function backupSqliteDatabase(array $connection, string $backupDirectory, string $baseName): string
    {
        $database = $connection['database'] ?? null;

        if (! $database) {
            throw new RuntimeException('SQLite database path is not configured.');
        }

        if ($database === ':memory:') {
            return $this->backupInMemorySqliteDatabase($backupDirectory, $baseName);
        }

        $sourcePath = $this->absoluteDatabasePath($database);

        if (! is_file($sourcePath)) {
            throw new RuntimeException('The SQLite database file could not be found.');
        }

        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$baseName.'.sqlite';

        if (! copy($sourcePath, $backupPath)) {
            throw new RuntimeException('Could not create the SQLite database backup.');
        }

        return $backupPath;
    }

    private function backupInMemorySqliteDatabase(string $backupDirectory, string $baseName): string
    {
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$baseName.'.sql';
        $pdo = DB::connection()->getPdo();
        $handle = fopen($backupPath, 'wb');

        if (! $handle) {
            throw new RuntimeException('Could not create the SQLite database backup.');
        }

        fwrite($handle, "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n");

        $tables = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll();

        foreach ($tables as $table) {
            if (! empty($table['sql'])) {
                fwrite($handle, $table['sql'].";\n");
            }

            $tableName = $table['name'];
            $quotedTable = $this->quoteSqliteIdentifier($tableName);
            $rows = $pdo->query("SELECT * FROM {$quotedTable}");

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $columns = array_map(fn ($column) => $this->quoteSqliteIdentifier($column), array_keys($row));
                $values = array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value), array_values($row));

                fwrite($handle, 'INSERT INTO '.$quotedTable.' ('.implode(', ', $columns).') VALUES ('.implode(', ', $values).");\n");
            }
        }

        $schemaObjects = $pdo->query("SELECT sql FROM sqlite_master WHERE type IN ('index', 'trigger', 'view') AND sql IS NOT NULL AND name NOT LIKE 'sqlite_%' ORDER BY type, name")->fetchAll();

        foreach ($schemaObjects as $object) {
            fwrite($handle, $object['sql'].";\n");
        }

        fwrite($handle, "COMMIT;\n");
        fclose($handle);

        return $backupPath;
    }

    private function backupPostgresDatabase(array $connection, string $backupDirectory, string $baseName): string
    {
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$baseName.'.dump';

        try {
            $pgDump = $this->findExecutable('pg_dump', [
                config('filesystems.database_backup_pg_dump_path'),
                'C:\Program Files\PostgreSQL\18\bin\pg_dump.exe',
                'C:\Program Files\PostgreSQL\17\bin\pg_dump.exe',
                'C:\Program Files\PostgreSQL\16\bin\pg_dump.exe',
                'C:\Program Files\PostgreSQL\15\bin\pg_dump.exe',
                'C:\Program Files\PostgreSQL\14\bin\pg_dump.exe',
            ]);
        } catch (RuntimeException $exception) {
            Log::warning('pg_dump is unavailable; falling back to Laravel PostgreSQL SQL backup.', [
                'error' => $exception->getMessage(),
            ]);

            return $this->backupPostgresDatabaseWithPdo($backupDirectory, $baseName);
        }

        try {
            $passwordFile = $this->createPostgresPasswordFile($connection, $backupDirectory);
            $process = new Process(array_filter([
                $pgDump,
                '--host='.($connection['host'] ?? '127.0.0.1'),
                '--port='.($connection['port'] ?? '5432'),
                '--username='.($connection['username'] ?? ''),
                '--format=custom',
                '--no-owner',
                '--no-acl',
                '--file='.$backupPath,
                $connection['database'] ?? '',
            ], fn ($value) => $value !== ''));

            return $this->runDumpProcess($process, [
                'PGPASSFILE' => $passwordFile,
            ], $backupPath, 'pg_dump');
        } finally {
            if (isset($passwordFile)) {
                @unlink($passwordFile);
            }
        }
    }

    private function backupPostgresDatabaseWithPdo(string $backupDirectory, string $baseName): string
    {
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$baseName.'.sql';
        $pdo = DB::connection()->getPdo();
        $handle = fopen($backupPath, 'wb');

        if (! $handle) {
            throw new RuntimeException('Could not create the PostgreSQL database backup.');
        }

        fwrite($handle, "-- CleanFlow PostgreSQL backup\n");
        fwrite($handle, '-- Generated at '.now()->toDateTimeString()."\n\n");
        fwrite($handle, "SET client_encoding = 'UTF8';\n");
        fwrite($handle, "SET standard_conforming_strings = on;\n");
        fwrite($handle, "BEGIN;\n\n");

        $tables = $pdo->query("
            SELECT schemaname, tablename
            FROM pg_tables
            WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
            ORDER BY schemaname, tablename
        ")->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($tables as $table) {
            $schema = $table['schemaname'];
            $tableName = $table['tablename'];
            $qualifiedTable = $this->quotePostgresIdentifier($schema).'.'.$this->quotePostgresIdentifier($tableName);

            fwrite($handle, "DROP TABLE IF EXISTS {$qualifiedTable} CASCADE;\n");
            fwrite($handle, "CREATE TABLE {$qualifiedTable} (\n");

            $columns = $this->postgresColumns($pdo, $schema, $tableName);
            $definitions = [];

            foreach ($columns as $column) {
                $definition = '    '.$this->quotePostgresIdentifier($column['column_name']).' '.$this->postgresColumnType($column);

                if ($column['column_default'] !== null) {
                    $definition .= ' DEFAULT '.$column['column_default'];
                }

                if ($column['is_nullable'] === 'NO') {
                    $definition .= ' NOT NULL';
                }

                $definitions[] = $definition;
            }

            fwrite($handle, implode(",\n", $definitions)."\n);\n\n");
        }

        foreach ($tables as $table) {
            $this->writePostgresTableRows($handle, $pdo, $table['schemaname'], $table['tablename']);
        }

        foreach ($tables as $table) {
            $this->writePostgresPrimaryKey($handle, $pdo, $table['schemaname'], $table['tablename']);
            $this->writePostgresSequenceResets($handle, $pdo, $table['schemaname'], $table['tablename']);
        }

        fwrite($handle, "\nCOMMIT;\n");
        fclose($handle);

        return $backupPath;
    }

    private function backupMysqlDatabase(array $connection, string $backupDirectory, string $baseName): string
    {
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$baseName.'.sql';
        $mysqlDump = $this->findExecutable('mysqldump', [
            config('filesystems.database_backup_mysqldump_path'),
            'C:\Program Files\MySQL\MySQL Server 9.0\bin\mysqldump.exe',
            'C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqldump.exe',
            'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe',
            'C:\Program Files\MariaDB 11.4\bin\mysqldump.exe',
            'C:\Program Files\MariaDB 10.11\bin\mysqldump.exe',
        ]);

        $process = new Process(array_filter([
            $mysqlDump,
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.($connection['port'] ?? '3306'),
            '--user='.($connection['username'] ?? ''),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--result-file='.$backupPath,
            $connection['database'] ?? '',
        ], fn ($value) => $value !== ''));

        return $this->runDumpProcess($process, [
            'MYSQL_PWD' => $connection['password'] ?? '',
        ], $backupPath, 'mysqldump');
    }

    private function runDumpProcess(Process $process, array $environment, string $backupPath, string $toolName): string
    {
        $process->setTimeout(120);
        $process->run(null, $environment);

        if (! $process->isSuccessful() || ! is_file($backupPath) || filesize($backupPath) === 0) {
            @unlink($backupPath);

            $error = trim($process->getErrorOutput()) ?: trim($process->getOutput());
            $error = $error !== '' ? $error : 'No error details were returned by the dump tool.';
            $message = "Database backup failed while running {$toolName} (exit code {$process->getExitCode()}).";

            throw new RuntimeException($message.' '.$error);
        }

        return $backupPath;
    }

    private function findExecutable(string $binary, array $candidatePaths = []): string
    {
        foreach ($candidatePaths as $candidatePath) {
            if ($candidatePath && is_file($candidatePath)) {
                return $candidatePath;
            }
        }

        $locator = PHP_OS_FAMILY === 'Windows'
            ? new Process(['where.exe', $binary])
            : new Process(['sh', '-lc', 'command -v '.escapeshellarg($binary)]);

        $locator->run();

        if ($locator->isSuccessful()) {
            $paths = preg_split('/\r\n|\r|\n/', trim($locator->getOutput()));
            $path = $paths[0] ?? null;

            if ($path && is_file($path)) {
                return $path;
            }
        }

        throw new RuntimeException("Database backup failed. {$binary} was not found. Add it to PATH or set DB_BACKUP_".strtoupper($binary === 'pg_dump' ? 'PG_DUMP' : 'MYSQLDUMP').'_PATH in .env.');
    }

    private function postgresConnectionUri(array $connection): string
    {
        $username = rawurlencode((string) ($connection['username'] ?? ''));
        $password = rawurlencode((string) ($connection['password'] ?? ''));
        $host = $connection['host'] ?? '127.0.0.1';
        $port = $connection['port'] ?? '5432';
        $database = rawurlencode((string) ($connection['database'] ?? ''));
        $sslMode = rawurlencode((string) ($connection['sslmode'] ?? 'prefer'));

        return "postgresql://{$username}:{$password}@{$host}:{$port}/{$database}?sslmode={$sslMode}";
    }

    private function createPostgresPasswordFile(array $connection, string $backupDirectory): string
    {
        $path = $backupDirectory.DIRECTORY_SEPARATOR.'pgpass-'.bin2hex(random_bytes(8)).'.conf';
        $line = implode(':', [
            $this->escapePostgresPasswordFileValue((string) ($connection['host'] ?? '127.0.0.1')),
            $this->escapePostgresPasswordFileValue((string) ($connection['port'] ?? '5432')),
            $this->escapePostgresPasswordFileValue((string) ($connection['database'] ?? '*')),
            $this->escapePostgresPasswordFileValue((string) ($connection['username'] ?? '*')),
            $this->escapePostgresPasswordFileValue((string) ($connection['password'] ?? '')),
        ]);

        if (file_put_contents($path, $line.PHP_EOL) === false) {
            throw new RuntimeException('Could not create the temporary PostgreSQL password file.');
        }

        @chmod($path, 0600);

        return $path;
    }

    private function escapePostgresPasswordFileValue(string $value): string
    {
        return str_replace(['\\', ':'], ['\\\\', '\\:'], $value);
    }

    private function postgresColumns(\PDO $pdo, string $schema, string $table): array
    {
        $statement = $pdo->prepare('
            SELECT column_name, column_default, is_nullable, data_type, udt_name,
                   character_maximum_length, numeric_precision, numeric_scale,
                   datetime_precision
            FROM information_schema.columns
            WHERE table_schema = :schema AND table_name = :table
            ORDER BY ordinal_position
        ');
        $statement->execute(['schema' => $schema, 'table' => $table]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function postgresColumnType(array $column): string
    {
        return match ($column['data_type']) {
            'character varying' => $column['character_maximum_length']
                ? 'varchar('.$column['character_maximum_length'].')'
                : 'varchar',
            'character' => $column['character_maximum_length']
                ? 'char('.$column['character_maximum_length'].')'
                : 'char',
            'numeric' => $column['numeric_precision']
                ? 'numeric('.$column['numeric_precision'].($column['numeric_scale'] ? ','.$column['numeric_scale'] : '').')'
                : 'numeric',
            'timestamp without time zone',
            'timestamp with time zone',
            'time without time zone',
            'time with time zone' => $column['datetime_precision'] !== null
                ? $column['data_type'].'('.$column['datetime_precision'].')'
                : $column['data_type'],
            'ARRAY' => $this->postgresArrayType($column['udt_name']),
            'USER-DEFINED' => $this->quotePostgresIdentifier($column['udt_name']),
            default => $column['data_type'],
        };
    }

    private function postgresArrayType(string $udtName): string
    {
        $baseType = ltrim($udtName, '_');

        return $this->quotePostgresIdentifier($baseType).'[]';
    }

    private function writePostgresTableRows($handle, \PDO $pdo, string $schema, string $table): void
    {
        $columns = $this->postgresColumns($pdo, $schema, $table);

        if ($columns === []) {
            return;
        }

        $qualifiedTable = $this->quotePostgresIdentifier($schema).'.'.$this->quotePostgresIdentifier($table);
        $columnNames = array_column($columns, 'column_name');
        $quotedColumns = array_map(fn ($column) => $this->quotePostgresIdentifier($column), $columnNames);
        $rows = $pdo->query('SELECT '.implode(', ', $quotedColumns).' FROM '.$qualifiedTable);

        while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
            $values = [];

            foreach ($columnNames as $columnName) {
                $values[] = $this->postgresLiteral($pdo, $row[$columnName]);
            }

            fwrite($handle, 'INSERT INTO '.$qualifiedTable.' ('.implode(', ', $quotedColumns).') VALUES ('.implode(', ', $values).");\n");
        }

        fwrite($handle, "\n");
    }

    private function writePostgresPrimaryKey($handle, \PDO $pdo, string $schema, string $table): void
    {
        $statement = $pdo->prepare("
            SELECT conname, array_agg(att.attname ORDER BY cols.ordinality) AS columns
            FROM pg_constraint con
            JOIN pg_class cls ON cls.oid = con.conrelid
            JOIN pg_namespace ns ON ns.oid = cls.relnamespace
            JOIN unnest(con.conkey) WITH ORDINALITY AS cols(attnum, ordinality) ON true
            JOIN pg_attribute att ON att.attrelid = cls.oid AND att.attnum = cols.attnum
            WHERE con.contype = 'p' AND ns.nspname = :schema AND cls.relname = :table
            GROUP BY con.conname
            LIMIT 1
        ");
        $statement->execute(['schema' => $schema, 'table' => $table]);
        $primaryKey = $statement->fetch(\PDO::FETCH_ASSOC);

        if (! $primaryKey) {
            return;
        }

        $qualifiedTable = $this->quotePostgresIdentifier($schema).'.'.$this->quotePostgresIdentifier($table);
        $columns = trim($primaryKey['columns'], '{}');
        $quotedColumns = array_map(
            fn ($column) => $this->quotePostgresIdentifier($column),
            str_getcsv($columns),
        );

        fwrite(
            $handle,
            'ALTER TABLE ONLY '.$qualifiedTable.' ADD CONSTRAINT '.$this->quotePostgresIdentifier($primaryKey['conname']).' PRIMARY KEY ('.implode(', ', $quotedColumns).");\n"
        );
    }

    private function writePostgresSequenceResets($handle, \PDO $pdo, string $schema, string $table): void
    {
        foreach ($this->postgresColumns($pdo, $schema, $table) as $column) {
            if (! str_contains((string) $column['column_default'], 'nextval(')) {
                continue;
            }

            $qualifiedTable = $this->quotePostgresIdentifier($schema).'.'.$this->quotePostgresIdentifier($table);
            $quotedColumn = $this->quotePostgresIdentifier($column['column_name']);
            $tableLiteral = $pdo->quote($schema.'.'.$table);
            $columnLiteral = $pdo->quote($column['column_name']);

            fwrite(
                $handle,
                "SELECT setval(pg_get_serial_sequence({$tableLiteral}, {$columnLiteral}), COALESCE((SELECT MAX({$quotedColumn}) FROM {$qualifiedTable}), 1), (SELECT MAX({$quotedColumn}) IS NOT NULL FROM {$qualifiedTable}));\n"
            );
        }
    }

    private function postgresLiteral(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        return $pdo->quote((string) $value);
    }

    private function quotePostgresIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    private function absoluteDatabasePath(string $database): string
    {
        if (preg_match('/^([A-Za-z]:)?[\/\\\\]/', $database) === 1) {
            return $database;
        }

        return base_path($database);
    }

    private function quoteSqliteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
