<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use MatanYadaev\EloquentSpatial\Objects\Point;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'status' => ShipmentStatus::Draft,
            'origin_address' => fake()->streetAddress(),
            'origin' => new Point(fake()->latitude(13.0, 16.0), fake()->longitude(-89.3, -83.2)),
            'destination_address' => fake()->streetAddress(),
            'destination' => new Point(fake()->latitude(13.0, 16.0), fake()->longitude(-89.3, -83.2)),
            'pickup_date' => fake()->dateTimeBetween('+1 day', '+7 days'),
            'cargo_type' => fake()->randomElement(['general', 'perishable', 'construction', 'livestock', 'fragile']),
            'weight_kg' => fake()->numberBetween(100, 25000),
            'currency' => 'HNL',
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function quoted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentStatus::Quoted,
            'published_at' => now(),
        ]);
    }
}
