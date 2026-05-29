<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'access_restricted_until')) {
                $table->timestamp('access_restricted_until')->nullable()->after('fingerprint_template_id');
            }

            if (! Schema::hasColumn('users', 'access_restriction_reason')) {
                $table->string('access_restriction_reason')->nullable()->after('access_restricted_until');
            }

            if (! Schema::hasColumn('users', 'staff_restricted_pages')) {
                $table->json('staff_restricted_pages')->nullable()->after('access_restriction_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'access_restricted_until',
                'access_restriction_reason',
                'staff_restricted_pages',
            ]);
        });
    }
};
