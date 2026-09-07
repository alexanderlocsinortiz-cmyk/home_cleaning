<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'cleaner_applications_active_email_unique';

    public function up(): void
    {
        if (! Schema::hasTable('cleaner_applications') || $this->indexExists()) {
            return;
        }

        $duplicates = DB::table('cleaner_applications')
            ->whereIn('status', ['pending', 'approved'])
            ->selectRaw('LOWER(TRIM(email)) AS normalized_email')
            ->groupByRaw('LOWER(TRIM(email))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_email');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot enforce one active cleaner application per email. Duplicate emails: '.$duplicates->implode(', ')
            );
        }

        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            $indexSql = sprintf(
                "CREATE UNIQUE INDEX %s ON cleaner_applications (LOWER(TRIM(email))) WHERE status IN ('pending', 'approved')",
                self::INDEX_NAME
            );
            DB::unprepared($indexSql);

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared(
                sprintf(
                    "ALTER TABLE cleaner_applications ADD COLUMN active_email VARCHAR(150) GENERATED ALWAYS AS (IF(status IN ('pending', 'approved'), LOWER(TRIM(email)), NULL)) STORED"
                )
            );
            DB::unprepared(sprintf('CREATE UNIQUE INDEX %s ON cleaner_applications (active_email)', self::INDEX_NAME));

            return;
        }

        // SQL Server has no portable partial-index equivalent in Laravel's schema API.
        // The composite fallback still blocks duplicate active records with the same status.
        DB::unprepared(sprintf('CREATE UNIQUE INDEX %s ON cleaner_applications (email, status)', self::INDEX_NAME));
    }

    public function down(): void
    {
        if (! Schema::hasTable('cleaner_applications') || ! $this->indexExists()) {
            return;
        }

        DB::statement('DROP INDEX '.self::INDEX_NAME);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
            && Schema::hasColumn('cleaner_applications', 'active_email')) {
            DB::statement('ALTER TABLE cleaner_applications DROP COLUMN active_email');
        }
    }

    private function indexExists(): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('tablename', 'cleaner_applications')
                ->where('indexname', self::INDEX_NAME)
                ->exists();
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('cleaner_applications')"))
                ->contains(fn (object $row): bool => $row->name === self::INDEX_NAME);
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return DB::select('SHOW INDEX FROM cleaner_applications WHERE Key_name = ?', [self::INDEX_NAME]) !== [];
        }

        return false;
    }
};
