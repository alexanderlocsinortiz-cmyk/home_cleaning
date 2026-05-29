<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'daily_room_name')) {
                $table->string('daily_room_name')->nullable()->after('location_updated_at');
            }

            if (! Schema::hasColumn('bookings', 'daily_room_url')) {
                $table->string('daily_room_url')->nullable()->after('daily_room_name');
            }

            if (! Schema::hasColumn('bookings', 'daily_room_expires_at')) {
                $table->timestamp('daily_room_expires_at')->nullable()->after('daily_room_url');
            }

            if (! Schema::hasColumn('bookings', 'live_video_started_at')) {
                $table->timestamp('live_video_started_at')->nullable()->after('daily_room_expires_at');
            }

            if (! Schema::hasColumn('bookings', 'live_video_ended_at')) {
                $table->timestamp('live_video_ended_at')->nullable()->after('live_video_started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            foreach ([
                'live_video_ended_at',
                'live_video_started_at',
                'daily_room_expires_at',
                'daily_room_url',
                'daily_room_name',
            ] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
