<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->create()->id,
            'client_id' => User::factory()->create(['role' => 'client'])->id,
            'staff_id' => User::factory()->create(['role' => 'staff'])->id,
            'stars' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence(),
            'photo' => null,
        ];
    }
}
