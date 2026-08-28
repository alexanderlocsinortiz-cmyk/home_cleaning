<?php

namespace Database\Factories;

use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceLogFactory extends Factory
{
    protected $model = AttendanceLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'staff'])->id,
            'punch_type' => $this->faker->randomElement(['in', 'out']),
            'logged_at' => $this->faker->dateTime(),
        ];
    }
}
