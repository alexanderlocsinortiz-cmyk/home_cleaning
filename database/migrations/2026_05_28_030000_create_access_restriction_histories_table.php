<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_restriction_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_name');
            $table->string('target_email');
            $table->string('target_role', 32);
            $table->string('action', 48);
            $table->unsignedInteger('duration_days')->nullable();
            $table->timestamp('restricted_until')->nullable();
            $table->string('reason')->nullable();
            $table->json('restricted_pages')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['target_user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_restriction_histories');
    }
};
