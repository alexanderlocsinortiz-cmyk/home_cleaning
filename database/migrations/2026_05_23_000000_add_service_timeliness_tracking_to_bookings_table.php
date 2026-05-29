<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'expected_started_at')) {
                $table->timestamp('expected_started_at')->nullable()->after('duration_minutes');
            }

            if (! Schema::hasColumn('bookings', 'expected_completed_at')) {
                $table->timestamp('expected_completed_at')->nullable()->after('expected_started_at');
            }

            if (! Schema::hasColumn('bookings', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('expected_completed_at');
            }

            if (! Schema::hasColumn('bookings', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }

            if (! Schema::hasColumn('bookings', 'started_late_minutes')) {
                $table->unsignedInteger('started_late_minutes')->default(0)->after('completed_at');
            }

            if (! Schema::hasColumn('bookings', 'completed_late_minutes')) {
                $table->unsignedInteger('completed_late_minutes')->default(0)->after('started_late_minutes');
            }

            if (! Schema::hasColumn('bookings', 'on_time_status')) {
                $table->string('on_time_status', 32)->nullable()->after('completed_late_minutes');
            }

            if (! Schema::hasColumn('bookings', 'on_time_notes')) {
                $table->string('on_time_notes')->nullable()->after('on_time_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $columns = [
                'expected_started_at',
                'expected_completed_at',
                'started_at',
                'completed_at',
                'started_late_minutes',
                'completed_late_minutes',
                'on_time_status',
                'on_time_notes',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
