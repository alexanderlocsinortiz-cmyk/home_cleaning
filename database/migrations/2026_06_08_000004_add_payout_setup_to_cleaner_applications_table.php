<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->string('payout_method', 30)->nullable()->after('max_daily_bookings');
            $table->string('payout_account_name')->nullable()->after('payout_method');
            $table->string('payout_account_number', 100)->nullable()->after('payout_account_name');
            $table->string('payout_verification_status', 20)->default('pending')->after('payout_account_number');
            $table->boolean('valid_id_submitted')->default(false)->after('payout_verification_status');
            $table->boolean('business_permit_submitted')->default(false)->after('valid_id_submitted');
            $table->boolean('payout_account_proof_submitted')->default(false)->after('business_permit_submitted');
            $table->timestamp('payout_verified_at')->nullable()->after('payout_account_proof_submitted');
            $table->foreignId('payout_verified_by')->nullable()->after('payout_verified_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cleaner_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payout_verified_by');
            $table->dropColumn([
                'payout_method',
                'payout_account_name',
                'payout_account_number',
                'payout_verification_status',
                'valid_id_submitted',
                'business_permit_submitted',
                'payout_account_proof_submitted',
                'payout_verified_at',
            ]);
        });
    }
};
