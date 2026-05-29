<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_checkout_session_id')->nullable()->after('payment_reference');
            $table->index('payment_checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['payment_checkout_session_id']);
            $table->dropColumn('payment_checkout_session_id');
        });
    }
};
