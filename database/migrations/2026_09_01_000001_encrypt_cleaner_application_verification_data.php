<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cleaner_applications')) {
            return;
        }

        $activeEmailIndexWasPresent = $this->activeEmailIndexExists();

        // SQLite rebuilds the table for change(), and cannot recreate an expression
        // index from PRAGMA metadata. Temporarily remove and restore our index.
        if ($activeEmailIndexWasPresent) {
            $this->dropActiveEmailIndex();
        }

        try {
            Schema::table('cleaner_applications', function (Blueprint $table): void {
                $table->text('government_id_number')->nullable()->change();
                $table->text('nbi_clearance_number')->nullable()->change();
                $table->timestamp('sensitive_data_purged_at')->nullable();
            });

            DB::table('cleaner_applications')
                ->select(['id', 'government_id_number', 'nbi_clearance_number'])
                ->where(function ($query): void {
                    $query->whereNotNull('government_id_number')
                        ->orWhereNotNull('nbi_clearance_number');
                })
                ->orderBy('id')
                ->get()
                ->each(function (object $application): void {
                    $updates = [];

                    if (filled($application->government_id_number)) {
                        $updates['government_id_number'] = Crypt::encryptString((string) $application->government_id_number);
                    }

                    if (filled($application->nbi_clearance_number)) {
                        $updates['nbi_clearance_number'] = Crypt::encryptString((string) $application->nbi_clearance_number);
                    }

                    if ($updates !== []) {
                        DB::table('cleaner_applications')
                            ->where('id', $application->id)
                            ->update($updates);
                    }
                });
        } finally {
            if ($activeEmailIndexWasPresent) {
                $this->restoreActiveEmailIndex();
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cleaner_applications')) {
            return;
        }

        // Encrypted values cannot safely be converted back to plaintext during rollback.
        Schema::table('cleaner_applications', function (Blueprint $table): void {
            $table->dropColumn('sensitive_data_purged_at');
        });
    }

    private function activeEmailIndexExists(): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('cleaner_applications')"))
                ->contains(fn (object $row): bool => $row->name === 'cleaner_applications_active_email_unique');
        }

        if (DB::getDriverName() === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('tablename', 'cleaner_applications')
                ->where('indexname', 'cleaner_applications_active_email_unique')
                ->exists();
        }

        return DB::select('SHOW INDEX FROM cleaner_applications WHERE Key_name = ?', ['cleaner_applications_active_email_unique']) !== [];
    }

    private function dropActiveEmailIndex(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::unprepared('ALTER TABLE cleaner_applications DROP INDEX cleaner_applications_active_email_unique');

            return;
        }

        DB::unprepared('DROP INDEX IF EXISTS cleaner_applications_active_email_unique');
    }

    private function restoreActiveEmailIndex(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::unprepared(
                "CREATE UNIQUE INDEX cleaner_applications_active_email_unique ON cleaner_applications (LOWER(TRIM(email))) WHERE status IN ('pending', 'approved')"
            );

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared('CREATE UNIQUE INDEX cleaner_applications_active_email_unique ON cleaner_applications (active_email)');

            return;
        }

        DB::unprepared('CREATE UNIQUE INDEX cleaner_applications_active_email_unique ON cleaner_applications (email, status)');
    }
};
