<?php

use App\Enums\ShipmentStatus;

test('the happy path transitions are allowed', function (ShipmentStatus $from, ShipmentStatus $to) {
    expect($from->canTransitionTo($to))->toBeTrue();
})->with([
    'draft to published' => [ShipmentStatus::Draft, ShipmentStatus::Published],
    'published to quoted' => [ShipmentStatus::Published, ShipmentStatus::Quoted],
    'quoted to accepted' => [ShipmentStatus::Quoted, ShipmentStatus::Accepted],
    'accepted to driver assigned' => [ShipmentStatus::Accepted, ShipmentStatus::DriverAssigned],
    'driver assigned to picked up' => [ShipmentStatus::DriverAssigned, ShipmentStatus::PickedUp],
    'picked up to in transit' => [ShipmentStatus::PickedUp, ShipmentStatus::InTransit],
    'in transit to delivered' => [ShipmentStatus::InTransit, ShipmentStatus::Delivered],
    'delivered to completed' => [ShipmentStatus::Delivered, ShipmentStatus::Completed],
]);

test('illegal transitions are blocked', function (ShipmentStatus $from, ShipmentStatus $to) {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    'delivered back to published' => [ShipmentStatus::Delivered, ShipmentStatus::Published],
    'draft straight to accepted' => [ShipmentStatus::Draft, ShipmentStatus::Accepted],
    'published straight to accepted' => [ShipmentStatus::Published, ShipmentStatus::Accepted],
    'in transit to cancelled' => [ShipmentStatus::InTransit, ShipmentStatus::Cancelled],
    'completed to anything' => [ShipmentStatus::Completed, ShipmentStatus::Published],
    'cancelled to published' => [ShipmentStatus::Cancelled, ShipmentStatus::Published],
    'expired to quoted' => [ShipmentStatus::Expired, ShipmentStatus::Quoted],
]);

test('terminal statuses have no transitions', function (ShipmentStatus $status) {
    expect($status->isTerminal())->toBeTrue()
        ->and($status->allowedTransitions())->toBeEmpty();
})->with([
    'completed' => [ShipmentStatus::Completed],
    'cancelled' => [ShipmentStatus::Cancelled],
    'expired' => [ShipmentStatus::Expired],
]);

test('only published and quoted shipments are open for quotes', function () {
    $open = array_filter(ShipmentStatus::cases(), fn (ShipmentStatus $status) => $status->isOpenForQuotes());

    expect(array_values($open))->toBe([ShipmentStatus::Published, ShipmentStatus::Quoted]);
});
