<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings') || Schema::hasColumn('bookings', 'required_cleaners')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('required_cleaners')->nullable()->after('floor_area');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'required_cleaners')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->dropColumn('required_cleaners');
            });
        }
    }
};
