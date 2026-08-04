<?php

use App\Actions\Quotes\AcceptQuoteAction;
use App\Enums\ShipmentStatus;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\User;

function acceptedJobFor(TransporterProfile $profile): Shipment
{
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create(['transporter_profile_id' => $profile->id]);

    app(AcceptQuoteAction::class)->execute($shipment->customer, $quote);

    return $shipment->refresh();
}

test('the jobs page lists assigned shipments with the next step', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    acceptedJobFor($profile);

    $response = $this->actingAs($profile->user)->get(route('jobs.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('jobs/Index')
            ->has('jobs.data', 1)
            ->where('jobs.data.0.status', 'accepted')
            ->where('jobs.data.0.next_status', 'driver_assigned')
        );
});

test('the assigned transporter can advance a job through delivery', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = acceptedJobFor($profile);

    foreach (['driver_assigned', 'picked_up', 'in_transit', 'delivered'] as $status) {
        $this->actingAs($profile->user)
            ->post(route('jobs.advance', $shipment), ['status' => $status])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    expect($shipment->refresh()->status)->toBe(ShipmentStatus::Delivered)
        ->and($shipment->delivered_at)->not->toBeNull();
});

test('a transporter cannot skip steps when advancing', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = acceptedJobFor($profile);

    $response = $this->actingAs($profile->user)
        ->post(route('jobs.advance', $shipment), ['status' => 'delivered']);

    $response->assertSessionHasErrors('status');
    expect($shipment->refresh()->status)->toBe(ShipmentStatus::Accepted);
});

test('only the assigned transporter can advance a job', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = acceptedJobFor($profile);
    $other = TransporterProfile::factory()->verified()->create();

    $this->actingAs($other->user)
        ->post(route('jobs.advance', $shipment), ['status' => 'driver_assigned'])
        ->assertForbidden();
});

test('the customer completes a delivered shipment and the ledger records it', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = acceptedJobFor($profile);

    foreach (['driver_assigned', 'picked_up', 'in_transit', 'delivered'] as $status) {
        $this->actingAs($profile->user)->post(route('jobs.advance', $shipment), ['status' => $status]);
    }

    $response = $this->actingAs($shipment->customer)
        ->post(route('shipments.complete', $shipment));

    $response->assertRedirect();

    expect($shipment->refresh()->status)->toBe(ShipmentStatus::Completed)
        ->and($shipment->commission)->not->toBeNull();
});

test('a shipment cannot be completed before delivery', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = acceptedJobFor($profile);

    $this->actingAs($shipment->customer)
        ->post(route('shipments.complete', $shipment))
        ->assertForbidden();
});

test('only the owner can complete a shipment', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = acceptedJobFor($profile);

    foreach (['driver_assigned', 'picked_up', 'in_transit', 'delivered'] as $status) {
        $this->actingAs($profile->user)->post(route('jobs.advance', $shipment), ['status' => $status]);
    }

    $this->actingAs(User::factory()->create())
        ->post(route('shipments.complete', $shipment))
        ->assertForbidden();
});
