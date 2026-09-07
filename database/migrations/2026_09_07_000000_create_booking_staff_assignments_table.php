<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users')->restrictOnDelete();
            $table->string('task_group', 60);
            $table->text('task_notes')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'staff_id']);
            $table->index(['staff_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_staff_assignments');
    }
};
