<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_enrollment_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('device_enrollment_requests', 'consent_token')) {
                $table->string('consent_token', 80)->nullable()->unique()->after('status');
            }

            if (! Schema::hasColumn('device_enrollment_requests', 'consent_requested_at')) {
                $table->timestamp('consent_requested_at')->nullable()->after('consent_token');
            }

            if (! Schema::hasColumn('device_enrollment_requests', 'consent_accepted_at')) {
                $table->timestamp('consent_accepted_at')->nullable()->after('consent_requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('device_enrollment_requests', function (Blueprint $table) {
            if (Schema::hasColumn('device_enrollment_requests', 'consent_token')) {
                $table->dropUnique(['consent_token']);
            }

            $table->dropColumn([
                'consent_token',
                'consent_requested_at',
                'consent_accepted_at',
            ]);
        });
    }
};
