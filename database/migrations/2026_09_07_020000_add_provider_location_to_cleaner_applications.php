<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cleaner_applications', 'location_area')) {
            Schema::table('cleaner_applications', function (Blueprint $table) {
                $table->string('location_area', 100)->nullable()->after('current_address');
                $table->decimal('location_latitude', 10, 7)->nullable()->after('location_area');
                $table->decimal('location_longitude', 10, 7)->nullable()->after('location_latitude');
                $table->index('location_area');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cleaner_applications', 'location_area')) {
            Schema::table('cleaner_applications', function (Blueprint $table) {
                $table->dropIndex(['location_area']);
                $table->dropColumn(['location_area', 'location_latitude', 'location_longitude']);
            });
        }
    }
};
