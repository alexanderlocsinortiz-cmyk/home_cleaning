<?php

namespace Database\Factories;

use App\Models\AttendanceLog;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceLogFactory extends Factory
{
    protected $model = AttendanceLog::class;

    public function definition(): array
    {
        return [
            'staff_id' => Staff::factory()->create()->id,
            'punch_type' => $this->faker->randomElement(['in', 'out']),
            'punched_at' => $this->faker->dateTime(),
            'fingerprint_template_id' => null,
        ];
    }
}
