<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align persisted catalog rows with the canonical package and add-on catalog.
     *
     * Existing custom add-ons are intentionally preserved. This migration changes
     * only the standard options that the application itself advertises.
     */
    public function up(): void
    {
        $now = now();

        if (Schema::hasTable('services')) {
            $services = [
                ['slug' => 'basic', 'name' => 'Basic Clean', 'description' => 'Routine cleaning package for regularly maintained spaces, including general dusting, sweeping, mopping, and bathroom refresh work.', 'price' => 35.0, 'duration_minutes' => 60],
                ['slug' => 'deep', 'name' => 'Deep Clean', 'description' => 'Detailed cleaning package for homes that need extra attention, focused scrubbing, and extended surface treatment across key living areas.', 'price' => 95.0, 'duration_minutes' => 180],
                ['slug' => 'moveinout', 'name' => 'Move-in/Move-out Clean', 'description' => 'Comprehensive turnover cleaning package for move-ins, move-outs, and property handovers that require full-space preparation.', 'price' => 80.0, 'duration_minutes' => 240],
                ['slug' => 'postconstruction', 'name' => 'Post Construction Cleaning', 'description' => 'Deep post-construction cleanup package that removes dust residue, debris traces, and renovation buildup across key living areas.', 'price' => 105.0, 'duration_minutes' => 240],
                ['slug' => 'commercial', 'name' => 'Office Cleaning (Standard)', 'description' => 'Commercial cleaning package for offices and business spaces, including reception areas, work zones, and common facilities.', 'price' => 35.0, 'duration_minutes' => 180],
                ['slug' => 'office-basic', 'name' => 'Office Cleaning (Basic)', 'description' => 'Basic office cleaning for floors, visible surfaces, work areas, and common office touchpoints.', 'price' => 30.0, 'duration_minutes' => 120],
                ['slug' => 'office-deep', 'name' => 'Office Cleaning (Deep)', 'description' => 'Deep office cleaning for workstations, floors, restrooms, pantry areas, fixtures, and high-touch surfaces.', 'price' => 60.0, 'duration_minutes' => 240],
                ['slug' => 'weeklymaintenance', 'name' => 'General/Regular Cleaning', 'description' => 'General or regular cleaning session for routine upkeep, covering common household cleaning tasks within a standard session of up to 4 hours.', 'price' => 500.0, 'duration_minutes' => 240],
            ];

            foreach ($services as $service) {
                $exists = DB::table('services')->where('slug', $service['slug'])->exists();
                $payload = $service + ['is_active' => true, 'updated_at' => $now];

                if ($exists) {
                    DB::table('services')->where('slug', $service['slug'])->update($payload);
                } else {
                    DB::table('services')->insert($payload + ['created_at' => $now]);
                }
            }
        }

        if (Schema::hasTable('service_add_ons')) {
            $addOns = [
                ['key' => 'window_glass', 'label' => 'Window Glass Cleaning', 'description' => 'Interior glass panels and reachable windows.', 'price' => 200.0, 'sort_order' => 1],
                ['key' => 'refrigerator', 'label' => 'Refrigerator Cleaning', 'description' => 'Deep wipe-down for the inside of the refrigerator.', 'price' => 350.0, 'sort_order' => 2],
                ['key' => 'inside_cabinets', 'label' => 'Inside Cabinet Cleaning', 'description' => 'Interior shelf and cabinet surface cleaning.', 'price' => 300.0, 'sort_order' => 3],
                ['key' => 'sofa_vacuum', 'label' => 'Sofa Vacuuming', 'description' => 'Dust and crumb removal for fabric seating.', 'price' => 400.0, 'sort_order' => 4],
                ['key' => 'pet_hair_removal', 'label' => 'Pet Hair Removal', 'description' => 'Extra removal for fur on floors, rugs, and furniture.', 'price' => 300.0, 'sort_order' => 5],
                ['key' => 'yard_sweeping', 'label' => 'Yard Sweeping', 'description' => 'Sweeping for walkways, patios, and accessible yard areas.', 'price' => 250.0, 'sort_order' => 6],
            ];

            foreach ($addOns as $addOn) {
                $exists = DB::table('service_add_ons')->where('key', $addOn['key'])->exists();
                $payload = $addOn + ['is_active' => true, 'updated_at' => $now];

                if ($exists) {
                    DB::table('service_add_ons')->where('key', $addOn['key'])->update($payload);
                } else {
                    DB::table('service_add_ons')->insert($payload + ['created_at' => $now]);
                }
            }
        }
    }

    public function down(): void
    {
        // A safe rollback cannot restore unknown administrator-edited prices.
        // Keep the approved catalog in place rather than reintroducing drift.
    }
};
