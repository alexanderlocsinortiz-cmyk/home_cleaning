<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = \DB::getDriverName();

        // Add foreign key constraints for data integrity (PostgreSQL and MySQL only)
        if (in_array($driver, ['pgsql', 'mysql'])) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                if (!$this->hasConstraint('attendance_logs', 'attendance_logs_device_id_foreign')) {
                    $table->foreign('device_id')
                        ->references('id')
                        ->on('devices')
                        ->onDelete('restrict');
                }
            });

            Schema::table('device_enrollment_requests', function (Blueprint $table) {
                if (!$this->hasConstraint('device_enrollment_requests', 'device_enrollment_requests_device_id_foreign')) {
                    $table->foreign('device_id')
                        ->references('id')
                        ->on('devices')
                        ->onDelete('cascade');
                }
            });
        }

        // Add indexes for query performance
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->index(['user_id', 'logged_at']);
            $table->index(['device_id', 'logged_at']);
            $table->index('punch_type');
            $table->index('status');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index(['staff_id', 'status']);
            $table->index(['status', 'scheduled_date']);
            $table->index('created_at');
            $table->index('manual_review_status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('email_verified_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'logged_at']);
            $table->dropIndex(['device_id', 'logged_at']);
            $table->dropIndex(['punch_type']);
            $table->dropIndex(['status']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['staff_id', 'status']);
            $table->dropIndex(['status', 'scheduled_date']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['manual_review_status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['email_verified_at']);
            $table->dropIndex(['created_at']);
        });
    }

    private function hasConstraint(string $table, string $constraint): bool
    {
        // Check if constraint exists in PostgreSQL
        $result = \DB::selectOne(
            "SELECT constraint_name FROM information_schema.table_constraints WHERE table_name = ? AND constraint_name = ?",
            [$table, $constraint]
        );
        return $result !== null;
    }
};
