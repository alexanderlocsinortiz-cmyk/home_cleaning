<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        ['bookings', ['staff_id', 'status', 'scheduled_date', 'scheduled_time'], 'bookings_staff_schedule_lookup_idx'],
        ['bookings', ['user_id', 'status', 'scheduled_date', 'scheduled_time'], 'bookings_user_schedule_lookup_idx'],
        ['bookings', ['status', 'scheduled_date', 'scheduled_time'], 'bookings_schedule_conflict_lookup_idx'],
        ['bookings', ['status', 'created_at'], 'bookings_status_created_lookup_idx'],
        ['bookings', ['service_id'], 'bookings_service_id_lookup_idx'],
        ['attendance_logs', ['user_id', 'device_id', 'logged_at'], 'attendance_user_device_logged_idx'],
        ['attendance_logs', ['punch_type', 'logged_at'], 'attendance_punch_logged_idx'],
        ['booking_locations', ['booking_id', 'created_at'], 'booking_locations_booking_created_idx'],
        ['booking_activity_logs', ['created_at'], 'booking_activity_created_at_idx'],
        ['notifications', ['user_id', 'read_at', 'created_at'], 'notifications_user_read_created_idx'],
        ['device_enrollment_requests', ['template_id', 'status'], 'enrollment_template_status_idx'],
    ];

    public function up(): void
    {
        foreach ($this->indexesByTable() as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $missingIndexes = array_filter($indexes, function (array $index) use ($table): bool {
                [$columns, $name] = $index;

                return ! collect($columns)->contains(fn (string $column): bool => ! Schema::hasColumn($table, $column))
                    && ! $this->indexExists($table, $name);
            });

            if ($missingIndexes === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($missingIndexes): void {
                foreach ($missingIndexes as [$columns, $name]) {
                    $blueprint->index($columns, $name);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexesByTable() as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $existingIndexes = array_filter($indexes, fn (array $index): bool => $this->indexExists($table, $index[1]));

            if ($existingIndexes === []) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($existingIndexes): void {
                foreach ($existingIndexes as [, $name]) {
                    $blueprint->dropIndex($name);
                }
            });
        }
    }

    private function indexesByTable(): array
    {
        $byTable = [];

        foreach ($this->indexes as [$table, $columns, $name]) {
            $byTable[$table][] = [$columns, $name];
        }

        return $byTable;
    }

    private function indexExists(string $table, string $index): bool
    {
        return match (DB::getDriverName()) {
            'pgsql' => DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists(),
            'sqlite' => collect(DB::select("PRAGMA index_list('$table')"))
                ->contains(fn (object $row): bool => $row->name === $index),
            default => DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index]) !== [],
        };
    }
};
