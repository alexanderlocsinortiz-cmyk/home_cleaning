<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('heading', 8, 2)->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();
        });

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (! Schema::hasColumn('bookings', 'current_latitude')) {
                    $table->decimal('current_latitude', 10, 7)->nullable();
                }

                if (! Schema::hasColumn('bookings', 'current_longitude')) {
                    $table->decimal('current_longitude', 10, 7)->nullable();
                }

                if (! Schema::hasColumn('bookings', 'location_updated_at')) {
                    $table->timestamp('location_updated_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_locations');

        if (Schema::hasTable('bookings')) {
            $columns = array_values(array_filter([
                Schema::hasColumn('bookings', 'current_latitude') ? 'current_latitude' : null,
                Schema::hasColumn('bookings', 'current_longitude') ? 'current_longitude' : null,
                Schema::hasColumn('bookings', 'location_updated_at') ? 'location_updated_at' : null,
            ]));

            if ($columns !== []) {
                Schema::table('bookings', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};
