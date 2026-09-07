<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaner_application_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cleaner_application_id')->constrained('cleaner_applications')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('description', 255);
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();

            $table->index(['cleaner_application_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleaner_application_activity_logs');
    }
};
