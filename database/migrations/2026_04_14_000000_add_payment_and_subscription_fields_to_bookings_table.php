<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'payment_method')) {
                $table->string('payment_method')->default('on_site_cash')->after('add_ons_fee');
            }
            if (!Schema::hasColumn('bookings', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('payment_method');
            }
            if (!Schema::hasColumn('bookings', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('bookings', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_reference');
            }
            if (!Schema::hasColumn('bookings', 'service_plan')) {
                $table->string('service_plan')->default('one_time')->after('notes');
            }
            if (!Schema::hasColumn('bookings', 'subscription_frequency')) {
                $table->string('subscription_frequency')->nullable()->after('service_plan');
            }
            if (!Schema::hasColumn('bookings', 'subscription_occurrences')) {
                $table->unsignedInteger('subscription_occurrences')->nullable()->after('subscription_frequency');
            }
            if (!Schema::hasColumn('bookings', 'subscription_group_id')) {
                $table->string('subscription_group_id')->nullable()->after('subscription_occurrences');
            }
            if (!Schema::hasColumn('bookings', 'subscription_sequence')) {
                $table->unsignedInteger('subscription_sequence')->default(1)->after('subscription_group_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_status',
                'payment_reference',
                'paid_at',
                'service_plan',
                'subscription_frequency',
                'subscription_occurrences',
                'subscription_group_id',
                'subscription_sequence',
            ]);
        });
    }
};
