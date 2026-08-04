<?php

namespace Database\Seeders;

use App\Actions\Quotes\AcceptQuoteAction;
use App\Actions\Quotes\SubmitQuoteAction;
use App\Actions\Shipments\CompleteShipmentAction;
use App\Actions\Shipments\CreateShipmentAction;
use App\Actions\Shipments\PublishShipmentAction;
use App\Actions\Shipments\TransitionShipmentStatusAction;
use App\Enums\ShipmentStatus;
use App\Models\City;
use App\Models\Company;
use App\Models\OperatingRegion;
use App\Models\Quote;
use App\Models\Rating;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\Truck;
use App\Models\TruckType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use MatanYadaev\EloquentSpatial\Objects\Point;

/**
 * Populates a realistic marketplace snapshot for demos and manual testing.
 *
 * Fixed accounts (password: "password"):
 *   customer@demo.test / transporter@demo.test / admin@demo.test
 *
 * Every shipment is driven through the real Actions so it carries a full
 * status history, and completed ones carry commissions and ratings. Includes
 * a guaranteed Tegucigalpa <-> San Pedro Sula return-trip pair.
 *
 * Run with: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function __construct(
        private CreateShipmentAction $createShipment,
        private PublishShipmentAction $publishShipment,
        private SubmitQuoteAction $submitQuote,
        private AcceptQuoteAction $acceptQuote,
        private TransitionShipmentStatusAction $transition,
        private CompleteShipmentAction $completeShipment,
    ) {}

    public function run(): void
    {
        if (User::query()->where('email', 'customer@demo.test')->exists()) {
            $this->command->warn('Demo data already present, skipping.');

            return;
        }

        $this->call([
            RoleSeeder::class,
            TruckTypeSeeder::class,
            CitySeeder::class,
        ]);

        $customers = $this->seedCustomers();
        $transporters = $this->seedTransporters();
        $this->seedAdmin();

        $this->seedMarketplaceActivity($customers, $transporters);
        $this->seedReturnTripShowcase($customers);
        $this->refreshTransporterRatings();

        $this->command->info('Demo accounts: customer@demo.test, transporter@demo.test, admin@demo.test (password: "password").');
    }

    /**
     * @return Collection<int, User>
     */
    private function seedCustomers(): Collection
    {
        $demoCustomer = User::factory()->create([
            'name' => 'Cliente Demo',
            'email' => 'customer@demo.test',
        ]);
        $demoCustomer->assignRole('customer');

        Company::factory()->for($demoCustomer)->create([
            'name' => 'Distribuidora Demo S. de R.L.',
            'city_id' => City::query()->where('name', 'Tegucigalpa')->value('id'),
        ]);

        $customers = User::factory()->count(6)->create();

        foreach ($customers as $customer) {
            $customer->assignRole('customer');
            Company::factory()->for($customer)->create([
                'city_id' => City::query()->inRandomOrder()->value('id'),
            ]);
        }

        return $customers->prepend($demoCustomer);
    }

    /**
     * @return Collection<int, TransporterProfile>
     */
    private function seedTransporters(): Collection
    {
        $demoUser = User::factory()->create([
            'name' => 'Transportista Demo',
            'email' => 'transporter@demo.test',
        ]);
        $demoUser->assignRole('transporter');

        $demoProfile = TransporterProfile::factory()->verified()->for($demoUser)->create([
            'years_of_experience' => 12,
        ]);

        foreach (['camion-seco', 'plataforma'] as $slug) {
            Truck::factory()->for($demoProfile)->create([
                'truck_type_id' => TruckType::query()->where('slug', $slug)->value('id'),
            ]);
        }

        foreach (['Tegucigalpa', 'San Pedro Sula'] as $cityName) {
            $city = City::query()->where('name', $cityName)->firstOrFail();
            OperatingRegion::factory()->for($demoProfile)->create([
                'city_id' => $city->id,
                'name' => $city->name,
                'center' => $city->location,
                'radius_m' => 60000,
            ]);
        }

        $profiles = collect();

        foreach (range(1, 8) as $i) {
            $user = User::factory()->create();
            $user->assignRole('transporter');

            $profile = TransporterProfile::factory()->verified()->for($user)->create();
            Truck::factory()->count(fake()->numberBetween(1, 2))->for($profile)->create([
                'truck_type_id' => TruckType::query()->inRandomOrder()->value('id'),
            ]);

            $city = City::query()->inRandomOrder()->firstOrFail();
            OperatingRegion::factory()->for($profile)->create([
                'city_id' => $city->id,
                'name' => $city->name,
                'center' => $city->location,
                'radius_m' => 60000,
            ]);

            $profiles->push($profile);
        }

        return $profiles->prepend($demoProfile);
    }

    private function seedAdmin(): void
    {
        User::factory()->create([
            'name' => 'Admin Demo',
            'email' => 'admin@demo.test',
        ])->assignRole('admin');
    }

    /**
     * Seed shipments deterministically across every lifecycle stage so the
     * demo always shows the full workflow.
     *
     * @param  Collection<int, User>  $customers
     * @param  Collection<int, TransporterProfile>  $transporters
     */
    private function seedMarketplaceActivity(Collection $customers, Collection $transporters): void
    {
        $stages = [
            ShipmentStatus::Draft,
            ShipmentStatus::Draft,
            ShipmentStatus::Published,
            ShipmentStatus::Published,
            ShipmentStatus::Published,
            ShipmentStatus::Quoted,
            ShipmentStatus::Quoted,
            ShipmentStatus::Quoted,
            ShipmentStatus::Accepted,
            ShipmentStatus::DriverAssigned,
            ShipmentStatus::PickedUp,
            ShipmentStatus::InTransit,
            ShipmentStatus::Delivered,
            ShipmentStatus::Completed,
            ShipmentStatus::Completed,
            ShipmentStatus::Cancelled,
            ShipmentStatus::Expired,
        ];

        foreach ($stages as $stage) {
            $this->seedShipmentAtStage($customers->random(), $stage, $transporters);
        }
    }

    /**
     * @param  Collection<int, TransporterProfile>  $transporters
     */
    private function seedShipmentAtStage(User $customer, ShipmentStatus $stage, Collection $transporters): void
    {
        $cities = City::query()->inRandomOrder()->take(2)->get();
        $originCity = $cities->first();
        $destinationCity = $cities->last();

        $pickupDate = $stage->isOpenForQuotes() || $stage === ShipmentStatus::Draft || $stage === ShipmentStatus::Accepted
            ? now()->addDays(fake()->numberBetween(1, 5))
            : now()->subDays(fake()->numberBetween(1, 7));

        $shipment = $this->createShipment->execute($customer, [
            'origin_address' => fake()->streetAddress().', '.$originCity->name,
            'origin_city_id' => $originCity->id,
            'origin' => $this->pointNear($originCity),
            'destination_address' => fake()->streetAddress().', '.$destinationCity->name,
            'destination_city_id' => $destinationCity->id,
            'destination' => $this->pointNear($destinationCity),
            'pickup_date' => $pickupDate->toDateString(),
            'cargo_type' => fake()->randomElement(['general', 'perishable', 'construction', 'livestock', 'fragile']),
            'weight_kg' => fake()->numberBetween(500, 25000),
            'truck_type_id' => TruckType::query()->inRandomOrder()->value('id'),
            'budget_amount' => fake()->optional(0.6)->randomFloat(2, 5000, 40000),
        ]);

        if ($stage === ShipmentStatus::Draft) {
            return;
        }

        $this->publishShipment->execute($shipment, $customer);

        if ($stage === ShipmentStatus::Published) {
            return;
        }

        if ($stage === ShipmentStatus::Cancelled) {
            $this->transition->execute($shipment, ShipmentStatus::Cancelled, $customer, 'Cancelado por el cliente');

            return;
        }

        if ($stage === ShipmentStatus::Expired) {
            $this->transition->execute($shipment, ShipmentStatus::Expired, null, 'Expirado sin cotizaciones');

            return;
        }

        $quotes = $this->quoteShipment($shipment, $customer, $transporters);

        if ($stage === ShipmentStatus::Quoted) {
            return;
        }

        $winner = $quotes->sortBy('amount')->first();
        $this->acceptQuote->execute($customer, $winner);
        $shipment->refresh();

        if ($stage === ShipmentStatus::Accepted) {
            return;
        }

        $driver = $winner->transporterProfile->user;
        $path = [
            ShipmentStatus::DriverAssigned,
            ShipmentStatus::PickedUp,
            ShipmentStatus::InTransit,
            ShipmentStatus::Delivered,
        ];

        foreach ($path as $step) {
            $this->transition->execute($shipment, $step, $driver);

            if ($stage === $step) {
                return;
            }
        }

        $this->completeShipment->execute($shipment, $customer);
        $this->rateBothParties($shipment, $customer, $driver);
    }

    /**
     * @param  Collection<int, TransporterProfile>  $transporters
     * @return Collection<int, Quote>
     */
    private function quoteShipment(Shipment $shipment, User $customer, Collection $transporters): Collection
    {
        $quoters = $transporters
            ->filter(fn (TransporterProfile $profile) => $profile->user_id !== $customer->id)
            ->shuffle()
            ->take(fake()->numberBetween(2, 3));

        return $quoters->map(function (TransporterProfile $profile) use ($shipment) {
            return $this->submitQuote->execute($profile, $shipment, [
                'amount' => fake()->randomFloat(2, 8000, 45000),
                'truck_id' => $profile->trucks()->value('id'),
                'estimated_pickup_at' => $shipment->pickup_date->setHour(8),
                'estimated_delivery_at' => $shipment->pickup_date->addDay()->setHour(17),
                'notes' => fake()->optional(0.5)->sentence(),
            ]);
        });
    }

    /**
     * The showcase pair: the demo transporter hauls Tegucigalpa -> San Pedro
     * Sula, and another customer has a published load going back, so the
     * return-trip matcher always has a hit to demonstrate.
     *
     * @param  Collection<int, User>  $customers
     */
    private function seedReturnTripShowcase(Collection $customers): void
    {
        $tegucigalpa = City::query()->where('name', 'Tegucigalpa')->firstOrFail();
        $sanPedroSula = City::query()->where('name', 'San Pedro Sula')->firstOrFail();

        $demoCustomer = User::query()->where('email', 'customer@demo.test')->firstOrFail();
        $demoTransporter = TransporterProfile::query()
            ->whereRelation('user', 'email', 'transporter@demo.test')
            ->firstOrFail();

        $outbound = $this->createShipment->execute($demoCustomer, [
            'origin_address' => 'Col. Kennedy, Tegucigalpa',
            'origin_city_id' => $tegucigalpa->id,
            'origin' => $tegucigalpa->location,
            'destination_address' => 'Zona Industrial, San Pedro Sula',
            'destination_city_id' => $sanPedroSula->id,
            'destination' => $sanPedroSula->location,
            'pickup_date' => now()->addDay()->toDateString(),
            'cargo_type' => 'general',
            'weight_kg' => 8000,
            'special_instructions' => 'Demo: viaje de ida con retorno disponible',
        ]);

        $this->publishShipment->execute($outbound, $demoCustomer);

        $quote = $this->submitQuote->execute($demoTransporter, $outbound, [
            'amount' => 15000,
            'truck_id' => $demoTransporter->trucks()->value('id'),
            'estimated_pickup_at' => now()->addDay()->setHour(8),
            'estimated_delivery_at' => now()->addDay()->setHour(16),
        ]);

        $this->acceptQuote->execute($demoCustomer, $quote);

        $returnCustomer = $customers->last();

        $returnLoad = $this->createShipment->execute($returnCustomer, [
            'origin_address' => 'Barrio Guamilito, San Pedro Sula',
            'origin_city_id' => $sanPedroSula->id,
            'origin' => $sanPedroSula->location,
            'destination_address' => 'Mercado Zonal Belén, Tegucigalpa',
            'destination_city_id' => $tegucigalpa->id,
            'destination' => $tegucigalpa->location,
            'pickup_date' => now()->addDays(2)->toDateString(),
            'cargo_type' => 'general',
            'weight_kg' => 6000,
            'special_instructions' => 'Demo: carga de retorno',
        ]);

        $this->publishShipment->execute($returnLoad, $returnCustomer);
    }

    private function rateBothParties(Shipment $shipment, User $customer, User $driver): void
    {
        Rating::create([
            'shipment_id' => $shipment->id,
            'rater_id' => $customer->id,
            'ratee_id' => $driver->id,
            'score' => fake()->numberBetween(4, 5),
            'comment' => fake()->randomElement(['Excelente servicio', 'Entrega puntual', 'Muy profesional']),
        ]);

        Rating::create([
            'shipment_id' => $shipment->id,
            'rater_id' => $driver->id,
            'ratee_id' => $customer->id,
            'score' => fake()->numberBetween(3, 5),
            'comment' => fake()->randomElement(['Buen cliente', 'Carga bien empacada', 'Todo en orden']),
        ]);
    }

    private function refreshTransporterRatings(): void
    {
        TransporterProfile::query()->with('user')->get()->each(function (TransporterProfile $profile) {
            $received = Rating::query()->where('ratee_id', $profile->user_id);
            $count = $received->count();

            if ($count > 0) {
                $profile->update([
                    'rating_avg' => round((float) $received->avg('score'), 2),
                    'rating_count' => $count,
                ]);
            }
        });
    }

    private function pointNear(City $city): Point
    {
        return new Point(
            $city->location->latitude + fake()->randomFloat(4, -0.02, 0.02),
            $city->location->longitude + fake()->randomFloat(4, -0.02, 0.02),
        );
    }
}
