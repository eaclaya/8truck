<?php

namespace Database\Factories;

use App\Models\OperatingRegion;
use App\Models\TransporterProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use MatanYadaev\EloquentSpatial\Objects\Point;

/**
 * @extends Factory<OperatingRegion>
 */
class OperatingRegionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transporter_profile_id' => TransporterProfile::factory(),
            'name' => fake()->city(),
            'center' => new Point(fake()->latitude(13.0, 16.0), fake()->longitude(-89.3, -83.2)),
            'radius_m' => 50000,
        ];
    }
}
