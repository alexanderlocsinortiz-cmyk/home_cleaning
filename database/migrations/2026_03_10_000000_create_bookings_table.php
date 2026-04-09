<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            return;
        }

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('service_type');
            $table->string('barangay');
            $table->string('street_address');
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->text('notes')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('status', ['pending','confirmed','in_progress','completed','cancelled'])->default('pending');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('address')->nullable();
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};