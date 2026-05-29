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
                'description' => 'Regular sweeping, mopping, and dusting of all rooms.',
                'price' => 500,
                'duration_minutes' => 60,
                'is_active' => 1,
            ],
            [
                'name' => 'Deep Clean',
                'description' => 'Thorough scrubbing, appliances, cabinets, and hard-to-reach areas.',
                'price' => 95,
                'duration_minutes' => 180,
                'is_active' => 1,
            ],
            [
                'name' => 'Move-in/Move-out Clean',
                'description' => 'Full property cleaning for moving in or moving out.',
                'price' => 3500,
                'duration_minutes' => 240,
                'is_active' => 1,
            ],
            [
                'name' => 'Post Construction Cleaning',
                'description' => 'Detailed post-construction cleanup for dust, debris, and renovation residue.',
                'price' => 105,
                'duration_minutes' => 240,
                'is_active' => 1,
            ],
            [
                'name' => 'Office and Commercial Cleaning',
                'description' => 'Structured cleaning support for offices, stores, and other commercial spaces.',
                'price' => 1600,
                'duration_minutes' => 180,
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
