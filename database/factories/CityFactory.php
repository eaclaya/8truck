<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use MatanYadaev\EloquentSpatial\Objects\Point;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city(),
            'department' => fake()->randomElement(['Cortés', 'Francisco Morazán', 'Atlántida', 'Choluteca', 'Comayagua']),
            'location' => new Point(fake()->latitude(13.0, 16.0), fake()->longitude(-89.3, -83.2)),
        ];
    }
}
