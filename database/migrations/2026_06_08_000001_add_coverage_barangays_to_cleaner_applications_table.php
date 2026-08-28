<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->json('coverage_barangays')->nullable()->after('service_area');
        });
    }

    public function down(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->dropColumn('coverage_barangays');
        });
    }
};
