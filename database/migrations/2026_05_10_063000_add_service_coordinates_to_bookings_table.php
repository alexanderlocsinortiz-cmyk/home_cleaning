<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('service_latitude', 10, 7)->nullable()->after('street_address');
            $table->decimal('service_longitude', 10, 7)->nullable()->after('service_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['service_latitude', 'service_longitude']);
        });
    }
};
