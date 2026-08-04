<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory()->quoted(),
            'transporter_profile_id' => TransporterProfile::factory(),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'currency' => 'HNL',
            'estimated_pickup_at' => fake()->dateTimeBetween('+1 day', '+3 days'),
            'estimated_delivery_at' => fake()->dateTimeBetween('+3 days', '+7 days'),
            'status' => QuoteStatus::Pending,
        ];
    }
}
