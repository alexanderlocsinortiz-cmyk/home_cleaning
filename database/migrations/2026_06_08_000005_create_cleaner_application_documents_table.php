<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaner_application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cleaner_application_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cleaner_application_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleaner_application_documents');
    }
};
