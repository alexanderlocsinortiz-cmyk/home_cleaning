<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COLUMNS = [
        'daily_room_name',
        'daily_room_url',
        'daily_room_expires_at',
        'live_video_started_at',
        'live_video_ended_at',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('booking_video_sessions')) {
            Schema::create('booking_video_sessions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
                $table->string('daily_room_name')->nullable();
                $table->string('daily_room_url')->nullable();
                $table->timestamp('daily_room_expires_at')->nullable();
                $table->timestamp('live_video_started_at')->nullable();
                $table->timestamp('live_video_ended_at')->nullable();
                $table->timestamps();

                $table->index('daily_room_expires_at');
            });
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'daily_room_name')) {
            DB::table('bookings')
                ->where(function ($query): void {
                    foreach (self::COLUMNS as $column) {
                        $query->orWhereNotNull($column);
                    }
                })
                ->orderBy('id')
                ->chunkById(500, function ($bookings): void {
                    foreach ($bookings as $booking) {
                        $attributes = ['booking_id' => $booking->id];
                        foreach (self::COLUMNS as $column) {
                            $attributes[$column] = $booking->{$column};
                        }
                        $attributes['created_at'] = $booking->created_at ?? now();
                        $attributes['updated_at'] = $booking->updated_at ?? now();
                        DB::table('booking_video_sessions')->updateOrInsert(['booking_id' => $booking->id], $attributes);
                    }
                });

            Schema::table('bookings', function (Blueprint $table): void {
                $columns = array_values(array_filter(
                    self::COLUMNS,
                    fn (string $column): bool => Schema::hasColumn('bookings', $column),
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table): void {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    continue;
                }
                if (in_array($column, ['daily_room_name', 'daily_room_url'], true)) {
                    $table->string($column)->nullable();
                } else {
                    $table->timestamp($column)->nullable();
                }
            }
        });

        if (Schema::hasTable('booking_video_sessions')) {
            DB::table('booking_video_sessions')->orderBy('id')->chunkById(500, function ($sessions): void {
                foreach ($sessions as $session) {
                    DB::table('bookings')->where('id', $session->booking_id)->update([
                        'daily_room_name' => $session->daily_room_name,
                        'daily_room_url' => $session->daily_room_url,
                        'daily_room_expires_at' => $session->daily_room_expires_at,
                        'live_video_started_at' => $session->live_video_started_at,
                        'live_video_ended_at' => $session->live_video_ended_at,
                    ]);
                }
            });
            Schema::drop('booking_video_sessions');
        }
    }
};
