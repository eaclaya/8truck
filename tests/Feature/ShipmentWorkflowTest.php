<?php

use App\Actions\Quotes\AcceptQuoteAction;
use App\Actions\Quotes\SubmitQuoteAction;
use App\Actions\Shipments\CompleteShipmentAction;
use App\Actions\Shipments\CreateShipmentAction;
use App\Actions\Shipments\PublishShipmentAction;
use App\Actions\Shipments\TransitionShipmentStatusAction;
use App\Enums\QuoteStatus;
use App\Enums\ShipmentStatus;
use App\Exceptions\ShipmentException;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\Truck;
use App\Models\User;
use MatanYadaev\EloquentSpatial\Objects\Point;

test('a customer can create a draft shipment with an initial history entry', function () {
    $customer = User::factory()->create();

    $shipment = app(CreateShipmentAction::class)->execute($customer, [
        'origin_address' => 'Col. Kennedy, Tegucigalpa',
        'origin' => new Point(14.0723, -87.1921),
        'destination_address' => 'Barrio Guamilito, San Pedro Sula',
        'destination' => new Point(15.5042, -88.0250),
        'pickup_date' => now()->addDays(2)->toDateString(),
        'cargo_type' => 'general',
        'weight_kg' => 5000,
    ]);

    expect($shipment->status)->toBe(ShipmentStatus::Draft)
        ->and($shipment->reference)->toBeUlid()
        ->and($shipment->customer_id)->toBe($customer->id);

    $this->assertDatabaseHas('shipment_status_histories', [
        'shipment_id' => $shipment->id,
        'from_status' => null,
        'to_status' => ShipmentStatus::Draft->value,
        'actor_id' => $customer->id,
    ]);
});

test('publishing a draft shipment opens it for quotes and stamps expiry', function () {
    $shipment = Shipment::factory()->create();

    app(PublishShipmentAction::class)->execute($shipment, $shipment->customer);

    expect($shipment->refresh()->status)->toBe(ShipmentStatus::Published)
        ->and($shipment->published_at)->not->toBeNull()
        ->and($shipment->expires_at->toDateString())->toBe($shipment->pickup_date->toDateString());
});

test('a shipment cannot skip the state machine', function () {
    $shipment = Shipment::factory()->create();

    app(TransitionShipmentStatusAction::class)->execute($shipment, ShipmentStatus::Delivered);
})->throws(ShipmentException::class);

test('the first quote moves a published shipment to quoted', function () {
    $shipment = Shipment::factory()->published()->create();
    $transporter = TransporterProfile::factory()->create();

    $quote = app(SubmitQuoteAction::class)->execute($transporter, $shipment, [
        'amount' => 12500,
    ]);

    expect($quote->status)->toBe(QuoteStatus::Pending)
        ->and($quote->currency)->toBe('HNL')
        ->and($shipment->refresh()->status)->toBe(ShipmentStatus::Quoted);
});

test('a transporter cannot quote their own shipment', function () {
    $transporter = TransporterProfile::factory()->create();
    $shipment = Shipment::factory()->published()->create(['customer_id' => $transporter->user_id]);

    app(SubmitQuoteAction::class)->execute($transporter, $shipment, ['amount' => 1000]);
})->throws(ShipmentException::class, 'cannot quote their own shipment');

test('a transporter cannot quote the same shipment twice', function () {
    $shipment = Shipment::factory()->published()->create();
    $transporter = TransporterProfile::factory()->create();
    $submit = app(SubmitQuoteAction::class);

    $submit->execute($transporter, $shipment, ['amount' => 1000]);
    $submit->execute($transporter, $shipment, ['amount' => 900]);
})->throws(ShipmentException::class, 'already submitted a quote');

test('quotes are rejected while the shipment is not open', function () {
    $shipment = Shipment::factory()->create();
    $transporter = TransporterProfile::factory()->create();

    app(SubmitQuoteAction::class)->execute($transporter, $shipment, ['amount' => 1000]);
})->throws(ShipmentException::class, 'cannot be submitted');

test('accepting a quote assigns the transporter and rejects competitors', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $winner = Quote::factory()->for($shipment)->create();
    $loserA = Quote::factory()->for($shipment)->create();
    $loserB = Quote::factory()->for($shipment)->create();

    app(AcceptQuoteAction::class)->execute($shipment->customer, $winner);

    $shipment->refresh();

    expect($shipment->status)->toBe(ShipmentStatus::Accepted)
        ->and($shipment->accepted_quote_id)->toBe($winner->id)
        ->and($shipment->assigned_transporter_id)->toBe($winner->transporter_profile_id)
        ->and($winner->refresh()->status)->toBe(QuoteStatus::Accepted)
        ->and($loserA->refresh()->status)->toBe(QuoteStatus::Rejected)
        ->and($loserB->refresh()->status)->toBe(QuoteStatus::Rejected);
});

test('a quote on an already-accepted shipment cannot be accepted', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $winner = Quote::factory()->for($shipment)->create();
    $loser = Quote::factory()->for($shipment)->create();
    $accept = app(AcceptQuoteAction::class);

    $accept->execute($shipment->customer, $winner);
    $accept->execute($shipment->customer, $loser);
})->throws(ShipmentException::class);

test('only the shipment owner can accept a quote', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();
    $stranger = User::factory()->create();

    app(AcceptQuoteAction::class)->execute($stranger, $quote);
})->throws(ShipmentException::class, 'Only the shipment owner');

test('completing a delivered shipment records the commission shadow ledger', function () {
    config()->set('marketplace.commission_rate', 0.0);

    $shipment = Shipment::factory()->quoted()->create();
    $truck = Truck::factory()->create();
    $quote = Quote::factory()->for($shipment)->create([
        'transporter_profile_id' => $truck->transporter_profile_id,
        'truck_id' => $truck->id,
        'amount' => 18000,
    ]);

    app(AcceptQuoteAction::class)->execute($shipment->customer, $quote);
    $shipment->refresh();

    $transition = app(TransitionShipmentStatusAction::class);
    $transition->execute($shipment, ShipmentStatus::DriverAssigned);
    $transition->execute($shipment, ShipmentStatus::PickedUp);
    $transition->execute($shipment, ShipmentStatus::InTransit);
    $transition->execute($shipment, ShipmentStatus::Delivered);

    app(CompleteShipmentAction::class)->execute($shipment);

    expect($shipment->refresh()->status)->toBe(ShipmentStatus::Completed)
        ->and($shipment->completed_at)->not->toBeNull()
        ->and($shipment->statusHistories()->count())->toBe(6);

    $this->assertDatabaseHas('commissions', [
        'shipment_id' => $shipment->id,
        'transporter_profile_id' => $quote->transporter_profile_id,
        'base_amount' => 18000,
        'fee_amount' => 0,
    ]);
});
