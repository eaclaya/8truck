<?php

use App\Actions\Shipments\FindReturnTripsAction;
use App\Models\Shipment;
use MatanYadaev\EloquentSpatial\Objects\Point;

const TEGUCIGALPA = [14.0723, -87.1921];
const SAN_PEDRO_SULA = [15.5042, -88.0250];
const LA_CEIBA = [15.7597, -86.7822];

function shipmentBetween(array $from, array $to, string $pickupDate): Shipment
{
    return Shipment::factory()->published()->create([
        'origin' => new Point($from[0], $from[1]),
        'destination' => new Point($to[0], $to[1]),
        'pickup_date' => $pickupDate,
    ]);
}

test('a reverse-route shipment within the window is a return trip match', function () {
    $outbound = shipmentBetween(TEGUCIGALPA, SAN_PEDRO_SULA, now()->addDay()->toDateString());
    $returnLoad = shipmentBetween(SAN_PEDRO_SULA, TEGUCIGALPA, now()->addDays(2)->toDateString());

    $matches = app(FindReturnTripsAction::class)->execute($outbound);

    expect($matches->pluck('id')->all())->toBe([$returnLoad->id]);
});

test('shipments outside the radius do not match', function () {
    $outbound = shipmentBetween(TEGUCIGALPA, SAN_PEDRO_SULA, now()->addDay()->toDateString());
    shipmentBetween(LA_CEIBA, TEGUCIGALPA, now()->addDays(2)->toDateString());

    $matches = app(FindReturnTripsAction::class)->execute($outbound);

    expect($matches)->toBeEmpty();
});

test('shipments outside the pickup window do not match', function () {
    $outbound = shipmentBetween(TEGUCIGALPA, SAN_PEDRO_SULA, now()->addDay()->toDateString());
    shipmentBetween(SAN_PEDRO_SULA, TEGUCIGALPA, now()->addDays(6)->toDateString());

    $matches = app(FindReturnTripsAction::class)->execute($outbound);

    expect($matches)->toBeEmpty();
});

test('draft shipments are never offered as return trips', function () {
    $outbound = shipmentBetween(TEGUCIGALPA, SAN_PEDRO_SULA, now()->addDay()->toDateString());
    Shipment::factory()->create([
        'origin' => new Point(...SAN_PEDRO_SULA),
        'destination' => new Point(...TEGUCIGALPA),
        'pickup_date' => now()->addDays(2)->toDateString(),
    ]);

    $matches = app(FindReturnTripsAction::class)->execute($outbound);

    expect($matches)->toBeEmpty();
});

test('the same-direction duplicate is not a return trip', function () {
    $outbound = shipmentBetween(TEGUCIGALPA, SAN_PEDRO_SULA, now()->addDay()->toDateString());
    shipmentBetween(TEGUCIGALPA, SAN_PEDRO_SULA, now()->addDays(2)->toDateString());

    $matches = app(FindReturnTripsAction::class)->execute($outbound);

    expect($matches)->toBeEmpty();
});
