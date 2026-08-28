<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Basic Clean',
                'description' => 'Routine cleaning package for regularly maintained spaces, including general dusting, sweeping, mopping, and bathroom refresh work.',
                'price' => 35,
                'duration_minutes' => 60,
                'is_active' => 1,
            ],
            [
                'name' => 'Deep Clean',
                'description' => 'Detailed cleaning package for homes that need extra attention, focused scrubbing, and extended surface treatment across key living areas.',
                'price' => 95,
                'duration_minutes' => 180,
                'is_active' => 1,
            ],
            [
                'name' => 'Move-in/Move-out Clean',
                'description' => 'Comprehensive turnover cleaning package for move-ins, move-outs, and property handovers that require full-space preparation.',
                'price' => 80,
                'duration_minutes' => 240,
                'is_active' => 1,
            ],
            [
                'name' => 'Post Construction Cleaning',
                'description' => 'Deep post-construction cleanup package that removes dust residue, debris traces, and renovation buildup across key living areas.',
                'price' => 105,
                'duration_minutes' => 240,
                'is_active' => 1,
            ],
            [
                'name' => 'Office Cleaning (Basic)',
                'description' => 'Basic office cleaning for floors, visible surfaces, work areas, and common office touchpoints.',
                'price' => 30,
                'duration_minutes' => 120,
                'is_active' => 1,
            ],
            [
                'name' => 'Office Cleaning (Standard)',
                'description' => 'Commercial cleaning package for offices and business spaces, including reception areas, work zones, and common facilities.',
                'price' => 35,
                'duration_minutes' => 180,
                'is_active' => 1,
            ],
            [
                'name' => 'Office Cleaning (Deep)',
                'description' => 'Deep office cleaning for workstations, floors, restrooms, pantry areas, fixtures, and high-touch surfaces.',
                'price' => 60,
                'duration_minutes' => 240,
                'is_active' => 1,
            ],
            [
                'name' => 'General/Regular Cleaning',
                'description' => 'General or regular cleaning session for routine upkeep, up to 4 hours.',
                'price' => 500,
                'duration_minutes' => 240,
                'is_active' => 1,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['slug' => Service::canonicalSlugForName($service['name'])],
                $service + ['slug' => Service::canonicalSlugForName($service['name'])]
            );
        }

        $this->call([
            StaffSeeder::class,
            TestUserSeeder::class,
        ]);
    }
}
