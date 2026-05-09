<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 30)->nullable();
            $table->string('actor_name')->nullable();
            $table->string('action', 50);
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'created_at'], 'booking_activity_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_activity_logs');
    }
};
