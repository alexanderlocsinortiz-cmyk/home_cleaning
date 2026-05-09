<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('services')->where('slug', 'minorpest')->delete();

        $services = [
            [
                'name' => 'Post Construction Cleaning',
                'slug' => 'postconstruction',
                'description' => 'Detailed post-construction cleanup for dust, debris, and renovation residue.',
                'price' => 1800.0,
            ],
            [
                'name' => 'Office and Commercial Cleaning',
                'slug' => 'commercial',
                'description' => 'Structured cleaning support for offices, stores, and other commercial spaces.',
                'price' => 1600.0,
            ],
            [
                'name' => 'Weekly Maintenance Plan',
                'slug' => 'weeklymaintenance',
                'description' => 'Recurring weekly cleaning plan for consistent upkeep and readiness.',
                'price' => 900.0,
            ],
        ];

        foreach ($services as $service) {
            $existing = DB::table('services')->where('slug', $service['slug'])->exists();

            if ($existing) {
                DB::table('services')
                    ->where('slug', $service['slug'])
                    ->update(array_merge($service, [
                        'is_active' => true,
                        'updated_at' => now(),
                    ]));

                continue;
            }

            DB::table('services')->insert(array_merge($service, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        DB::table('services')
            ->whereIn('slug', ['postconstruction', 'commercial', 'weeklymaintenance'])
            ->delete();

        $minorPestExists = DB::table('services')->where('slug', 'minorpest')->exists();
        $minorPestPayload = [
            'name' => 'Minor Pest Control',
            'description' => 'Specialty service for minor pest concerns in residential properties, including targeted treatment and preventive inspection guidance.',
            'price' => 950.0,
            'is_active' => true,
            'updated_at' => now(),
        ];

        if ($minorPestExists) {
            DB::table('services')->where('slug', 'minorpest')->update($minorPestPayload);

            return;
        }

        DB::table('services')->insert(array_merge(
            ['slug' => 'minorpest'],
            $minorPestPayload,
            ['created_at' => now()]
        ));
    }
};
