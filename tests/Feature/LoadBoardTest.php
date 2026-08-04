<?php

use App\Enums\ShipmentStatus;
use App\Models\OperatingRegion;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\Truck;
use App\Models\User;
use MatanYadaev\EloquentSpatial\Objects\Point;

function transporterWithRegionAt(float $lat, float $lng): TransporterProfile
{
    $profile = TransporterProfile::factory()->verified()->create();

    OperatingRegion::factory()->for($profile)->create([
        'center' => new Point($lat, $lng),
        'radius_m' => 50000,
    ]);

    return $profile;
}

function publishedShipmentFrom(float $lat, float $lng): Shipment
{
    return Shipment::factory()->published()->create([
        'origin' => new Point($lat, $lng),
        'pickup_date' => now()->addDays(2)->toDateString(),
    ]);
}

test('the load board shows only loads inside the transporter regions by default', function () {
    $profile = transporterWithRegionAt(14.0723, -87.1921);
    $nearby = publishedShipmentFrom(14.09, -87.20);
    publishedShipmentFrom(15.7597, -86.7822);

    $response = $this->actingAs($profile->user)->get(route('loads.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('loads/Index')
            ->has('loads.data', 1)
            ->where('loads.data.0.id', $nearby->id)
        );
});

test('the load board can show all loads nationwide', function () {
    $profile = transporterWithRegionAt(14.0723, -87.1921);
    publishedShipmentFrom(14.09, -87.20);
    publishedShipmentFrom(15.7597, -86.7822);

    $response = $this->actingAs($profile->user)->get(route('loads.index', ['all' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->has('loads.data', 2));
});

test('a transporter never sees their own shipments as loads', function () {
    $profile = transporterWithRegionAt(14.0723, -87.1921);
    Shipment::factory()->published()->create([
        'customer_id' => $profile->user_id,
        'origin' => new Point(14.09, -87.20),
        'pickup_date' => now()->addDays(2)->toDateString(),
    ]);

    $response = $this->actingAs($profile->user)->get(route('loads.index', ['all' => 1]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->has('loads.data', 0));
});

test('users without a transporter profile cannot open the load board', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('loads.index'))
        ->assertForbidden();
});

test('a transporter can submit a quote from the load page', function () {
    $profile = transporterWithRegionAt(14.0723, -87.1921);
    $shipment = publishedShipmentFrom(14.09, -87.20);

    $response = $this->actingAs($profile->user)->post(route('loads.quote', $shipment), [
        'amount' => 12500,
    ]);

    $response->assertRedirect();

    expect($shipment->refresh()->status)->toBe(ShipmentStatus::Quoted)
        ->and($shipment->quotes()->where('transporter_profile_id', $profile->id)->exists())->toBeTrue();
});

test('submitting a second quote surfaces the domain error', function () {
    $profile = transporterWithRegionAt(14.0723, -87.1921);
    $shipment = publishedShipmentFrom(14.09, -87.20);
    Quote::factory()->for($shipment)->create(['transporter_profile_id' => $profile->id]);

    $response = $this->actingAs($profile->user)->post(route('loads.quote', $shipment), [
        'amount' => 9000,
    ]);

    $response->assertSessionHasErrors('quote');
});

test('a quote cannot reference another transporter\'s truck', function () {
    $profile = transporterWithRegionAt(14.0723, -87.1921);
    $shipment = publishedShipmentFrom(14.09, -87.20);
    $foreignTruck = Truck::factory()->create();

    $response = $this->actingAs($profile->user)->post(route('loads.quote', $shipment), [
        'amount' => 9000,
        'truck_id' => $foreignTruck->id,
    ]);

    $response->assertSessionHasErrors('truck_id');
});
