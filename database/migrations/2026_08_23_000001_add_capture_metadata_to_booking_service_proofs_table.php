<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_service_proofs', function (Blueprint $table) {
            $table->timestamp('captured_at')->nullable()->after('original_name');
            $table->decimal('latitude', 10, 7)->nullable()->after('captured_at');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('booking_service_proofs', function (Blueprint $table) {
            $table->dropColumn(['captured_at', 'latitude', 'longitude']);
        });
    }
};
