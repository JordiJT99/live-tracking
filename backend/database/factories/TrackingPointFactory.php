<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrackingPointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'latitude'   => fake()->latitude(41.3, 41.5),
            'longitude'  => fake()->longitude(2.1, 2.3),
            'created_at' => now(),
        ];
    }
}
