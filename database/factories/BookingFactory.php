<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'client'])->id,
            'service_id' => Service::factory()->create()->id,
            'staff_id' => null,
            'status' => 'pending',
            'scheduled_date' => $this->faker->dateTimeBetween('now', '+7 days'),
            'scheduled_time' => $this->faker->time(),
            'base_price' => $this->faker->randomFloat(2, 500, 3000),
            'property_adjustment' => $this->faker->randomFloat(2, 0, 500),
            'room_bathroom_fees' => $this->faker->randomFloat(2, 0, 300),
            'floor_area_fees' => $this->faker->randomFloat(2, 0, 200),
            'add_on_fees' => $this->faker->randomFloat(2, 0, 150),
            'property_type' => $this->faker->randomElement(['apartment', 'house', 'condo']),
            'rooms' => $this->faker->numberBetween(1, 5),
            'bathrooms' => $this->faker->numberBetween(1, 3),
            'floor_area' => $this->faker->randomFloat(2, 50, 500),
            'manual_review_status' => 'not_required',
            'preferred_staff_status' => 'none',
            'payment_method' => $this->faker->randomElement(['on_site_cash', 'gcash', 'maya']),
            'payment_status' => 'pending',
        ];
    }
}
