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
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // This migration is a duplicate create-table artifact and should not
        // manage the lifecycle of the canonical bookings table.
    }
};
