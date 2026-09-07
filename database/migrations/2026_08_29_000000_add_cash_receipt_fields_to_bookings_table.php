<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'cash_receipt_number')) {
                $table->string('cash_receipt_number')->nullable()->unique()->after('payment_reference');
            }
            if (! Schema::hasColumn('bookings', 'payment_collected_amount')) {
                $table->decimal('payment_collected_amount', 10, 2)->nullable()->after('cash_receipt_number');
            }
            if (! Schema::hasColumn('bookings', 'payment_collected_at')) {
                $table->timestamp('payment_collected_at')->nullable()->after('payment_collected_amount');
            }
            if (! Schema::hasColumn('bookings', 'payment_collected_by')) {
                $table->foreignId('payment_collected_by')->nullable()->after('payment_collected_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('bookings', 'payment_receipt_notes')) {
                $table->text('payment_receipt_notes')->nullable()->after('payment_collected_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $columns = [
                'payment_receipt_notes',
                'payment_collected_by',
                'payment_collected_at',
                'payment_collected_amount',
                'cash_receipt_number',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
