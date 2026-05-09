<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'staff'])->id,
            'employee_id' => $this->faker->unique()->numerify('EMP###'),
            'hourly_rate' => $this->faker->randomFloat(2, 100, 500),
            'bio' => $this->faker->sentence(),
            'years_of_experience' => $this->faker->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}
