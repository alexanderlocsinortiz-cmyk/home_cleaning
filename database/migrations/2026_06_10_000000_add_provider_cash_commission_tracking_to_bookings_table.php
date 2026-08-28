<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('cash_collected_amount', 10, 2)->nullable()->after('provider_payout_proof_original_filename');
            $table->decimal('provider_commission_due', 10, 2)->nullable()->after('cash_collected_amount');
            $table->string('provider_commission_status', 20)->nullable()->after('provider_commission_due');
            $table->string('provider_commission_reference', 120)->nullable()->after('provider_commission_status');
            $table->timestamp('provider_commission_paid_at')->nullable()->after('provider_commission_reference');
            $table->foreignId('provider_commission_collected_by')->nullable()->after('provider_commission_paid_at')->constrained('users')->nullOnDelete();
            $table->string('provider_commission_proof_path')->nullable()->after('provider_commission_collected_by');
            $table->string('provider_commission_proof_original_filename')->nullable()->after('provider_commission_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('provider_commission_collected_by');
            $table->dropColumn([
                'cash_collected_amount',
                'provider_commission_due',
                'provider_commission_status',
                'provider_commission_reference',
                'provider_commission_paid_at',
                'provider_commission_proof_path',
                'provider_commission_proof_original_filename',
            ]);
        });
    }
};
