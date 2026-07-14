<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $startH = fake()->numberBetween(6, 14);

        return [
            'name'       => 'Línea '.fake()->numberBetween(1, 16).' – '.fake()->streetName(),
            'start_time' => "2025-01-13T{$startH}:00:00",
            'end_time'   => '2025-01-13T'.($startH + 9).':30:00',
            // A short but valid Google Encoded Polyline for testing
            'polyline'   => '_p~iF~ps|U_ulLnnqC_mqNvxq`@',
        ];
    }
}
