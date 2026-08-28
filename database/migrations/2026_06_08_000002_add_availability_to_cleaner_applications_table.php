<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->string('availability_status', 20)->default('available')->after('activated_at');
            $table->text('availability_notes')->nullable()->after('availability_status');
            $table->unsignedTinyInteger('max_daily_bookings')->nullable()->after('availability_notes');
        });
    }

    public function down(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->dropColumn([
                'availability_status',
                'availability_notes',
                'max_daily_bookings',
            ]);
        });
    }
};
