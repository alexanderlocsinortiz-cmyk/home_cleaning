<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_cleaner_team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('cleaner_team_member_id')->constrained('cleaner_team_members')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'cleaner_team_member_id'], 'booking_team_member_unique');
            $table->index(['cleaner_team_member_id', 'assigned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_cleaner_team_members');
    }
};
