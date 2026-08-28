<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_service_proofs', function (Blueprint $table) {
            $table->string('capture_source')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('booking_service_proofs', function (Blueprint $table) {
            $table->dropColumn('capture_source');
        });
    }
};
