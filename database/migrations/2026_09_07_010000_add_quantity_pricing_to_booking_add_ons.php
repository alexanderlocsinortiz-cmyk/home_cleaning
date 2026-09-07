<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('service_add_ons', 'pricing_unit')) {
            Schema::table('service_add_ons', function (Blueprint $table) {
                $table->string('pricing_unit', 60)->default('per booking')->after('price');
            });
        }

        if (! Schema::hasColumn('bookings', 'add_on_quantities')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->json('add_on_quantities')->nullable()->after('add_ons');
            });
        }

        $now = now();
        $catalog = [
            'refrigerator' => [
                'label' => "Refrigerator Interior Cleaning \u{2013} Small",
                'price' => 350,
                'pricing_unit' => 'per unit',
                'description' => 'Interior cleaning for a small refrigerator.',
            ],
            'sofa_deep_cleaning' => [
                'label' => 'Sofa Deep Cleaning',
                'price' => 300,
                'pricing_unit' => 'per seat',
                'description' => 'Deep cleaning for fabric sofa seats.',
            ],
            'mattress_single' => [
                'label' => "Mattress Cleaning \u{2013} Single",
                'price' => 900,
                'pricing_unit' => 'per mattress',
                'description' => 'Deep cleaning for one single mattress.',
            ],
            'mattress_double' => [
                'label' => "Mattress Cleaning \u{2013} Double",
                'price' => 1200,
                'pricing_unit' => 'per mattress',
                'description' => 'Deep cleaning for one double mattress.',
            ],
            'mattress_queen' => [
                'label' => "Mattress Cleaning \u{2013} Queen",
                'price' => 1500,
                'pricing_unit' => 'per mattress',
                'description' => 'Deep cleaning for one queen mattress.',
            ],
            'mattress_king' => [
                'label' => "Mattress Cleaning \u{2013} King",
                'price' => 1800,
                'pricing_unit' => 'per mattress',
                'description' => 'Deep cleaning for one king mattress.',
            ],
            'refrigerator_regular' => [
                'label' => "Refrigerator Interior Cleaning \u{2013} Regular",
                'price' => 650,
                'pricing_unit' => 'per unit',
                'description' => 'Interior cleaning for a regular two-door refrigerator.',
            ],
            'carpet_small' => [
                'label' => "Carpet Cleaning \u{2013} Small",
                'price' => 800,
                'pricing_unit' => 'per carpet',
                'description' => "Cleaning for a carpet below 2\u{00D7}3 meters.",
            ],
            'closet_cleaning' => [
                'label' => 'Closet Cleaning & Arrangement',
                'price' => 150,
                'pricing_unit' => 'per cabinet/closet',
                'description' => 'Cleaning and arrangement for one cabinet or closet.',
            ],
        ];

        foreach ($catalog as $key => $addOn) {
            $existing = DB::table('service_add_ons')->where('key', $key)->first();

            if ($existing) {
                DB::table('service_add_ons')->where('key', $key)->update([
                    'label' => $addOn['label'],
                    'price' => $addOn['price'],
                    'pricing_unit' => $addOn['pricing_unit'],
                    'description' => $addOn['description'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('service_add_ons')->insert([
                    'key' => $key,
                    'label' => $addOn['label'],
                    'description' => $addOn['description'],
                    'price' => $addOn['price'],
                    'pricing_unit' => $addOn['pricing_unit'],
                    'sort_order' => DB::table('service_add_ons')->max('sort_order') + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'add_on_quantities')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('add_on_quantities');
            });
        }

        if (Schema::hasColumn('service_add_ons', 'pricing_unit')) {
            Schema::table('service_add_ons', function (Blueprint $table) {
                $table->dropColumn('pricing_unit');
            });
        }
    }
};
