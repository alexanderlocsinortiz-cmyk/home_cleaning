<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'payments_booking_unique';

    public function up(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'booking_id')) {
            return;
        }

        $duplicates = DB::table('payments')
            ->select('booking_id')
            ->groupBy('booking_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('booking_id');

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                'Cannot enforce one payment per booking. Duplicate payment rows exist for booking IDs: '.$duplicates->implode(', ')
            );
        }

        Schema::table('payments', function ($table): void {
            $table->unique('booking_id', self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function ($table): void {
            $table->dropUnique(self::INDEX);
        });
    }
};
