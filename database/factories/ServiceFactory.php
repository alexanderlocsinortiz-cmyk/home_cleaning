<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name' => $name,
            // Factory-created services frequently coexist in one test. Keep
            // their slugs unique instead of relying on Faker's random words.
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(8)),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 100, 2000),
            'duration_minutes' => $this->faker->randomElement([60, 90, 120, 180, 240]),
            'is_active' => true,
        ];
    }
}
