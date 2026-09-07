<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments') || Schema::hasColumn('payments', 'collected_amount')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->decimal('collected_amount', 10, 2)->nullable()->after('amount');
        });

        // The first normalization migration stored the legacy collection value
        // in amount. Preserve that value for already-paid cash records, while
        // keeping pending and online payments visibly uncollected.
        DB::table('payments')
            ->where('method', 'on_site_cash')
            ->where('status', 'paid')
            ->update(['collected_amount' => DB::raw('amount')]);
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'collected_amount')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->dropColumn('collected_amount');
            });
        }
    }
};
