<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $services = [
            [
                'slug' => 'office-basic',
                'name' => 'Office Cleaning (Basic)',
                'description' => 'Light office cleaning for routine workspace upkeep.',
                'price' => 30,
                'duration_minutes' => 120,
                'is_active' => true,
            ],
            [
                'slug' => 'commercial',
                'name' => 'Office Cleaning (Standard)',
                'description' => 'Structured standard cleaning support for offices and business spaces.',
                'price' => 35,
                'duration_minutes' => 180,
                'is_active' => true,
            ],
            [
                'slug' => 'office-deep',
                'name' => 'Office Cleaning (Deep)',
                'description' => 'Detailed office cleaning for heavier buildup and high-touch zones.',
                'price' => 60,
                'duration_minutes' => 240,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            DB::table('services')->updateOrInsert(
                ['slug' => $service['slug']],
                array_merge($service, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        DB::table('services')->whereIn('slug', ['office-basic', 'office-deep'])->delete();

        DB::table('services')
            ->where('slug', 'commercial')
            ->update([
                'name' => 'Office and Commercial Cleaning',
                'description' => 'Structured cleaning support for offices, stores, and other commercial spaces.',
                'price' => 1600,
                'duration_minutes' => 180,
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
