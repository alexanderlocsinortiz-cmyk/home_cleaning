<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'preferred_staff_id')) {
                $table->foreignId('preferred_staff_id')->nullable()->after('staff_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'preferred_staff_status')) {
                $table->string('preferred_staff_status', 30)->default('none')->after('preferred_staff_id');
            }
        });

        DB::table('bookings')
            ->whereNull('preferred_staff_status')
            ->update(['preferred_staff_status' => 'none']);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preferred_staff_id');
            $table->dropColumn('preferred_staff_status');
        });
    }
};
