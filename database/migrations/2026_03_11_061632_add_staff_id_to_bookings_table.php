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
        if (! Schema::hasTable('bookings') || Schema::hasColumn('bookings', 'staff_id')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('staff_id')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'staff_id')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staff_id');
        });
    }
};
