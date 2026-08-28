<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('provider_gross_amount', 10, 2)->nullable()->after('provider_assignment_notes');
            $table->decimal('platform_commission_rate', 5, 4)->nullable()->after('provider_gross_amount');
            $table->decimal('platform_commission_amount', 10, 2)->nullable()->after('platform_commission_rate');
            $table->decimal('provider_payout_amount', 10, 2)->nullable()->after('platform_commission_amount');
            $table->string('provider_payout_status', 20)->nullable()->after('provider_payout_amount');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'provider_gross_amount',
                'platform_commission_rate',
                'platform_commission_amount',
                'provider_payout_amount',
                'provider_payout_status',
            ]);
        });
    }
};
