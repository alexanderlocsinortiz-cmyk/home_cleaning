<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('staff_id');
            $table->index(['scheduled_date', 'scheduled_time']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['staff_id']);
            $table->dropIndex(['scheduled_date', 'scheduled_time']);
            $table->dropIndex(['status']);
        });
    }
};
