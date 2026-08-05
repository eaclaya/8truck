<?php

use App\Models\City;
use App\Models\OperatingRegion;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\User;
use MatanYadaev\EloquentSpatial\Objects\Point;

test('users without a transporter profile cannot manage regions', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('regions.index'))
        ->assertForbidden();
});

test('a transporter can add an operating region from a city', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $city = City::factory()->create(['location' => new Point(14.0723, -87.1921)]);

    $response = $this->actingAs($profile->user)->post(route('regions.store'), [
        'city_id' => $city->id,
        'radius_km' => 75,
    ]);

    $response->assertRedirect();

    $region = $profile->operatingRegions()->first();

    expect($region)->not->toBeNull()
        ->and($region->name)->toBe($city->name)
        ->and($region->radius_m)->toBe(75000)
        ->and($region->center->latitude)->toEqualWithDelta(14.0723, 0.0001);
});

test('adding the same city twice does not duplicate the region', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $city = City::factory()->create();

    $this->actingAs($profile->user)->post(route('regions.store'), [
        'city_id' => $city->id, 'radius_km' => 50,
    ]);
    $this->actingAs($profile->user)->post(route('regions.store'), [
        'city_id' => $city->id, 'radius_km' => 100,
    ]);

    expect($profile->operatingRegions()->count())->toBe(1);
});

test('a transporter can delete their own region but not others', function () {
    $mine = OperatingRegion::factory()->create();
    $other = OperatingRegion::factory()->create();

    $this->actingAs($mine->transporterProfile->user)
        ->delete(route('regions.destroy', $other))
        ->assertForbidden();

    $this->actingAs($mine->transporterProfile->user)
        ->delete(route('regions.destroy', $mine))
        ->assertRedirect();

    expect(OperatingRegion::query()->find($mine->id))->toBeNull()
        ->and(OperatingRegion::query()->find($other->id))->not->toBeNull();
});

test('a new region makes nearby loads appear in the my-regions tab', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $tegucigalpa = City::factory()->create(['location' => new Point(14.0723, -87.1921)]);

    $load = Shipment::factory()->published()->create([
        'origin' => new Point(14.09, -87.20),
        'pickup_date' => now()->addDays(2)->toDateString(),
    ]);

    $this->actingAs($profile->user)->get(route('loads.index'))
        ->assertInertia(fn ($page) => $page
            ->has('loads.data', 0)
            ->where('hasRegions', false)
        );

    $this->actingAs($profile->user)->post(route('regions.store'), [
        'city_id' => $tegucigalpa->id,
        'radius_km' => 50,
    ]);

    $this->actingAs($profile->user)->get(route('loads.index'))
        ->assertInertia(fn ($page) => $page
            ->has('loads.data', 1)
            ->where('loads.data.0.id', $load->id)
            ->where('hasRegions', true)
        );
});
