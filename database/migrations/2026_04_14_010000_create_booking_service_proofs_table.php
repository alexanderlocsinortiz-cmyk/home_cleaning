<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_service_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stage', 20);
            $table->string('media_type', 20);
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'stage', 'media_type'], 'booking_proofs_stage_media_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_service_proofs');
    }
};
