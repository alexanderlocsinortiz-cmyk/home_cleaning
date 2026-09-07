<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('cash_proof_path')->nullable()->after('receipt_notes');
            $table->string('cash_proof_original_name')->nullable()->after('cash_proof_path');
            $table->string('cash_proof_mime_type', 120)->nullable()->after('cash_proof_original_name');
            $table->unsignedInteger('cash_proof_size')->nullable()->after('cash_proof_mime_type');
            $table->string('cash_proof_status', 20)->nullable()->after('cash_proof_size');
            $table->timestamp('cash_proof_submitted_at')->nullable()->after('cash_proof_status');
            $table->timestamp('cash_proof_reviewed_at')->nullable()->after('cash_proof_submitted_at');
            $table->foreignId('cash_proof_reviewed_by')->nullable()->after('cash_proof_reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('cash_proof_rejection_reason')->nullable()->after('cash_proof_reviewed_by');
            $table->index(['cash_proof_status', 'cash_proof_submitted_at'], 'payments_cash_proof_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['cash_proof_reviewed_by']);
            $table->dropIndex('payments_cash_proof_status_idx');
            $table->dropColumn([
                'cash_proof_path', 'cash_proof_original_name', 'cash_proof_mime_type',
                'cash_proof_size', 'cash_proof_status', 'cash_proof_submitted_at',
                'cash_proof_reviewed_at', 'cash_proof_reviewed_by', 'cash_proof_rejection_reason',
            ]);
        });
    }
};
