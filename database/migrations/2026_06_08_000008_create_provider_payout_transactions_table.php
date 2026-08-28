<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_payout_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('cleaner_application_id')->nullable()->constrained('cleaner_applications')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->decimal('provider_gross_amount', 10, 2)->nullable();
            $table->decimal('platform_commission_amount', 10, 2)->nullable();
            $table->decimal('provider_payout_amount', 10, 2)->nullable();
            $table->string('payout_reference', 120)->nullable();
            $table->timestamp('payout_paid_at')->nullable();
            $table->string('payout_proof_path')->nullable();
            $table->string('payout_proof_original_filename')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_payout_transactions');
    }
};
