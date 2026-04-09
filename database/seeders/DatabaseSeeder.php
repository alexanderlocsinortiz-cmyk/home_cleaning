<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Service;

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
                'is_active' => 1,
            ],
            [
                'name' => 'Deep Clean',
                'description' => 'Thorough scrubbing, appliances, cabinets, and hard-to-reach areas.',
                'price' => 1200,
                'is_active' => 1,
            ],
            [
                'name' => 'Move-in/Move-out Clean',
                'description' => 'Full property cleaning for moving in or moving out.',
                'price' => 2000,
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
