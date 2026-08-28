<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('provider_payout_reference', 120)->nullable()->after('provider_payout_status');
            $table->timestamp('provider_payout_paid_at')->nullable()->after('provider_payout_reference');
            $table->foreignId('provider_payout_processed_by')->nullable()->after('provider_payout_paid_at')->constrained('users')->nullOnDelete();
            $table->string('provider_payout_proof_path')->nullable()->after('provider_payout_processed_by');
            $table->string('provider_payout_proof_original_filename')->nullable()->after('provider_payout_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provider_payout_processed_by');
            $table->dropColumn([
                'provider_payout_reference',
                'provider_payout_paid_at',
                'provider_payout_proof_path',
                'provider_payout_proof_original_filename',
            ]);
        });
    }
};
