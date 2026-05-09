<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'risk_reasons')) {
                $table->json('risk_reasons')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('bookings', 'manual_review_status')) {
                $table->string('manual_review_status', 20)->default('not_required')->after('risk_reasons');
            }
            if (!Schema::hasColumn('bookings', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('manual_review_status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bookings', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });

        DB::table('bookings')
            ->whereNull('manual_review_status')
            ->update(['manual_review_status' => 'not_required']);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'risk_reasons',
                'manual_review_status',
                'reviewed_at',
            ]);
        });
    }
};
