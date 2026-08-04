<?php

use App\Actions\Quotes\AcceptQuoteAction;
use App\Actions\Shipments\CompleteShipmentAction;
use App\Actions\Shipments\TransitionShipmentStatusAction;
use App\Enums\ShipmentStatus;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\User;
use MatanYadaev\EloquentSpatial\Objects\Point;

function completedShipmentFor(TransporterProfile $profile): Shipment
{
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create(['transporter_profile_id' => $profile->id]);

    app(AcceptQuoteAction::class)->execute($shipment->customer, $quote);
    $shipment->refresh();

    $transition = app(TransitionShipmentStatusAction::class);
    $transition->execute($shipment, ShipmentStatus::DriverAssigned);
    $transition->execute($shipment, ShipmentStatus::PickedUp);
    $transition->execute($shipment, ShipmentStatus::InTransit);
    $transition->execute($shipment, ShipmentStatus::Delivered);
    app(CompleteShipmentAction::class)->execute($shipment);

    return $shipment->refresh();
}

test('the customer can rate the transporter and aggregates update', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = completedShipmentFor($profile);

    $response = $this->actingAs($shipment->customer)->post(route('shipments.rate', $shipment), [
        'score' => 5,
        'comment' => 'Excelente servicio',
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    $profile->refresh();

    expect($shipment->ratings()->count())->toBe(1)
        ->and($shipment->ratings()->first()->ratee_id)->toBe($profile->user_id)
        ->and($profile->rating_count)->toBe(1)
        ->and((float) $profile->rating_avg)->toBe(5.0);
});

test('the transporter can rate the customer', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = completedShipmentFor($profile);

    $response = $this->actingAs($profile->user)->post(route('shipments.rate', $shipment), [
        'score' => 4,
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    expect($shipment->ratings()->first()->ratee_id)->toBe($shipment->customer_id);
});

test('a shipment cannot be rated before completion', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = Shipment::factory()->quoted()->create();

    $this->actingAs($shipment->customer)
        ->post(route('shipments.rate', $shipment), ['score' => 5])
        ->assertForbidden();
});

test('a participant cannot rate the same shipment twice', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = completedShipmentFor($profile);

    $this->actingAs($shipment->customer)->post(route('shipments.rate', $shipment), ['score' => 5]);

    $this->actingAs($shipment->customer)
        ->post(route('shipments.rate', $shipment), ['score' => 1])
        ->assertForbidden();
});

test('a stranger cannot rate a shipment', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = completedShipmentFor($profile);

    $this->actingAs(User::factory()->create())
        ->post(route('shipments.rate', $shipment), ['score' => 5])
        ->assertForbidden();
});

test('the score must be between one and five', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = completedShipmentFor($profile);

    $this->actingAs($shipment->customer)
        ->post(route('shipments.rate', $shipment), ['score' => 6])
        ->assertSessionHasErrors('score');
});

test('the shipment page shows ratings and the rate ability to the owner', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = completedShipmentFor($profile);

    $this->actingAs($profile->user)->post(route('shipments.rate', $shipment), ['score' => 4]);

    $response = $this->actingAs($shipment->customer)->get(route('shipments.show', $shipment));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('ratings', 1)
            ->where('can.rate', true)
        );
});

test('the jobs page surfaces reverse-route return loads for active jobs', function () {
    $profile = TransporterProfile::factory()->verified()->create();

    $outbound = Shipment::factory()->quoted()->create([
        'origin' => new Point(14.0723, -87.1921),
        'destination' => new Point(15.5042, -88.0250),
        'pickup_date' => now()->addDay()->toDateString(),
    ]);
    $quote = Quote::factory()->for($outbound)->create(['transporter_profile_id' => $profile->id]);
    app(AcceptQuoteAction::class)->execute($outbound->customer, $quote);

    $returnLoad = Shipment::factory()->published()->create([
        'origin' => new Point(15.5042, -88.0250),
        'destination' => new Point(14.0723, -87.1921),
        'pickup_date' => now()->addDays(2)->toDateString(),
    ]);

    $response = $this->actingAs($profile->user)->get(route('jobs.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobs.data.0.return_trips', 1)
            ->where('jobs.data.0.return_trips.0.id', $returnLoad->id)
        );
});
