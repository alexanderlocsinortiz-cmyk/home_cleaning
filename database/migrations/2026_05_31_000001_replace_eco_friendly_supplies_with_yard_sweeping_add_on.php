<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_add_ons')) {
            return;
        }

        $now = now();
        $yardSweepingExists = DB::table('service_add_ons')->where('key', 'yard_sweeping')->exists();

        if ($yardSweepingExists) {
            DB::table('service_add_ons')->where('key', 'eco_friendly_supplies')->delete();
        } else {
            DB::table('service_add_ons')
                ->where('key', 'eco_friendly_supplies')
                ->update(['key' => 'yard_sweeping']);
        }

        DB::table('service_add_ons')
            ->where('key', 'yard_sweeping')
            ->update([
                'label' => 'Yard Sweeping',
                'description' => 'Sweeping for walkways, patios, and accessible yard areas.',
                'price' => 250,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_add_ons')) {
            return;
        }

        $now = now();
        $ecoFriendlyExists = DB::table('service_add_ons')->where('key', 'eco_friendly_supplies')->exists();

        if ($ecoFriendlyExists) {
            DB::table('service_add_ons')->where('key', 'yard_sweeping')->delete();
        } else {
            DB::table('service_add_ons')
                ->where('key', 'yard_sweeping')
                ->update(['key' => 'eco_friendly_supplies']);
        }

        DB::table('service_add_ons')
            ->where('key', 'eco_friendly_supplies')
            ->update([
                'label' => 'Eco-Friendly Supplies',
                'description' => 'Use greener, lower-residue cleaning products when available.',
                'price' => 150,
                'updated_at' => $now,
            ]);
    }
};
