<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ratings') && ! $this->indexExists('ratings', 'ratings_booking_id_unique')) {
            $duplicateBookings = DB::table('ratings')
                ->select('booking_id')
                ->groupBy('booking_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('booking_id');

            if ($duplicateBookings->isNotEmpty()) {
                throw new RuntimeException(
                    'Cannot enforce one rating per booking. Duplicate rating booking IDs: '.$duplicateBookings->implode(', ')
                );
            }

            Schema::table('ratings', function (Blueprint $table): void {
                $table->unique('booking_id', 'ratings_booking_id_unique');
            });
        }

        if (Schema::hasTable('cleaner_applications')
            && Schema::hasColumn('cleaner_applications', 'user_id')
            && ! $this->indexExists('cleaner_applications', 'cleaner_applications_user_id_unique')) {
            $duplicateUsers = DB::table('cleaner_applications')
                ->whereNotNull('user_id')
                ->select('user_id')
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('user_id');

            if ($duplicateUsers->isNotEmpty()) {
                throw new RuntimeException(
                    'Cannot enforce one cleaner application per user. Duplicate user IDs: '.$duplicateUsers->implode(', ')
                );
            }

            Schema::table('cleaner_applications', function (Blueprint $table): void {
                // Multiple anonymous applications remain valid because NULLs are not equal in the supported databases.
                $table->unique('user_id', 'cleaner_applications_user_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ratings') && $this->indexExists('ratings', 'ratings_booking_id_unique')) {
            Schema::table('ratings', function (Blueprint $table): void {
                $table->dropUnique('ratings_booking_id_unique');
            });
        }

        if (Schema::hasTable('cleaner_applications')
            && $this->indexExists('cleaner_applications', 'cleaner_applications_user_id_unique')) {
            Schema::table('cleaner_applications', function (Blueprint $table): void {
                $table->dropUnique('cleaner_applications_user_id_unique');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists();
        }

        if ($driver === 'sqlite') {
            return DB::select("PRAGMA index_list('$table')")
                && collect(DB::select("PRAGMA index_list('$table')"))->contains(fn (object $row): bool => $row->name === $index);
        }

        return DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index]) !== [];
    }
};
