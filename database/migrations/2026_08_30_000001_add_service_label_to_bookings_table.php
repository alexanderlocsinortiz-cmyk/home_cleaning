<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bookings', 'service_label')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->string('service_label')->nullable()->after('service_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'service_label')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->dropColumn('service_label');
            });
        }
    }
};
