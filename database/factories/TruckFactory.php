<?php

namespace Database\Factories;

use App\Enums\TruckAvailability;
use App\Models\TransporterProfile;
use App\Models\Truck;
use App\Models\TruckType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Truck>
 */
class TruckFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transporter_profile_id' => TransporterProfile::factory(),
            'truck_type_id' => TruckType::factory(),
            'plate_number' => fake()->unique()->bothify('??? ####'),
            'capacity_kg' => fake()->numberBetween(1000, 30000),
            'length_cm' => fake()->numberBetween(300, 1500),
            'width_cm' => fake()->numberBetween(180, 260),
            'height_cm' => fake()->numberBetween(180, 400),
            'availability' => TruckAvailability::Available,
            'insurance_expires_at' => fake()->dateTimeBetween('+1 month', '+2 years'),
        ];
    }
}
