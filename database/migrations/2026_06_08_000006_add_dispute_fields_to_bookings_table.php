<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('dispute_status', 20)->nullable()->after('provider_payout_status');
            $table->string('dispute_reason', 80)->nullable()->after('dispute_status');
            $table->text('dispute_description')->nullable()->after('dispute_reason');
            $table->timestamp('disputed_at')->nullable()->after('dispute_description');
            $table->string('dispute_resolution', 40)->nullable()->after('disputed_at');
            $table->text('dispute_admin_notes')->nullable()->after('dispute_resolution');
            $table->foreignId('dispute_reviewed_by')->nullable()->after('dispute_admin_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('dispute_resolved_at')->nullable()->after('dispute_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dispute_reviewed_by');
            $table->dropColumn([
                'dispute_status',
                'dispute_reason',
                'dispute_description',
                'disputed_at',
                'dispute_resolution',
                'dispute_admin_notes',
                'dispute_resolved_at',
            ]);
        });
    }
};
