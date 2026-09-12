<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaner_team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cleaner_application_id')->constrained('cleaner_applications')->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('current_address')->nullable();
            $table->string('government_id_type', 40)->nullable();
            $table->string('government_id_number')->nullable();
            $table->string('government_id_front_document_path')->nullable();
            $table->string('government_id_front_document_original_filename')->nullable();
            $table->string('government_id_back_document_path')->nullable();
            $table->string('government_id_back_document_original_filename')->nullable();
            $table->string('nbi_clearance_number')->nullable();
            $table->string('nbi_clearance_document_path')->nullable();
            $table->string('nbi_clearance_document_original_filename')->nullable();
            $table->string('selfie_with_id_path')->nullable();
            $table->string('selfie_with_id_original_filename')->nullable();
            $table->string('status', 30)->default('invited');
            $table->text('verification_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('verification_token_hash', 64)->nullable()->unique();
            $table->timestamp('verification_token_expires_at')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('availability_status', 20)->default('available');
            $table->text('availability_notes')->nullable();
            $table->timestamps();

            $table->index(['cleaner_application_id', 'status']);
            $table->index(['cleaner_application_id', 'availability_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleaner_team_members');
    }
};
