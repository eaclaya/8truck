<?php

use App\Enums\ShipmentStatus;
use App\Models\City;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\User;

test('the shipments index lists only the customer\'s shipments', function () {
    $customer = User::factory()->create();
    Shipment::factory()->count(2)->create(['customer_id' => $customer->id]);
    Shipment::factory()->create();

    $response = $this->actingAs($customer)->get(route('shipments.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('shipments/Index')
            ->has('shipments.data', 2)
        );
});

test('a customer can create a draft shipment from the web form', function () {
    $customer = User::factory()->create();
    $origin = City::factory()->create();
    $destination = City::factory()->create();

    $response = $this->actingAs($customer)->post(route('shipments.store'), [
        'origin_city_id' => $origin->id,
        'origin_address' => 'Bodega central',
        'destination_city_id' => $destination->id,
        'destination_address' => 'Plaza principal',
        'pickup_date' => now()->addDays(3)->toDateString(),
        'cargo_type' => 'general',
        'weight_kg' => 4500,
    ]);

    $shipment = Shipment::query()->where('customer_id', $customer->id)->firstOrFail();

    $response->assertRedirect(route('shipments.show', $shipment));

    expect($shipment->status)->toBe(ShipmentStatus::Draft)
        ->and($shipment->origin_city_id)->toBe($origin->id)
        ->and($shipment->origin->latitude)->toEqualWithDelta($origin->location->latitude, 0.0001);
});

test('origin and destination cities must differ', function () {
    $customer = User::factory()->create();
    $city = City::factory()->create();

    $response = $this->actingAs($customer)->post(route('shipments.store'), [
        'origin_city_id' => $city->id,
        'origin_address' => 'Bodega central',
        'destination_city_id' => $city->id,
        'destination_address' => 'Plaza principal',
        'pickup_date' => now()->addDays(3)->toDateString(),
        'cargo_type' => 'general',
    ]);

    $response->assertSessionHasErrors('destination_city_id');
});

test('a stranger cannot view another customer\'s shipment', function () {
    $shipment = Shipment::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('shipments.show', $shipment))
        ->assertForbidden();
});

test('the owner can publish a draft shipment from the web', function () {
    $shipment = Shipment::factory()->create();

    $response = $this->actingAs($shipment->customer)
        ->post(route('shipments.publish', $shipment));

    $response->assertRedirect();
    expect($shipment->refresh()->status)->toBe(ShipmentStatus::Published);
});

test('a published shipment cannot be published again', function () {
    $shipment = Shipment::factory()->published()->create();

    $this->actingAs($shipment->customer)
        ->post(route('shipments.publish', $shipment))
        ->assertForbidden();
});

test('the owner can accept a quote from the web', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $winner = Quote::factory()->for($shipment)->create();
    $loser = Quote::factory()->for($shipment)->create();

    $response = $this->actingAs($shipment->customer)
        ->post(route('quotes.accept', $winner));

    $response->assertRedirect();

    expect($shipment->refresh()->status)->toBe(ShipmentStatus::Accepted)
        ->and($shipment->accepted_quote_id)->toBe($winner->id)
        ->and($loser->refresh()->status->value)->toBe('rejected');
});

test('a stranger cannot accept quotes on another customer\'s shipment', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('quotes.accept', $quote))
        ->assertForbidden();
});

test('the shipment detail page shows quotes and history to the owner', function () {
    $shipment = Shipment::factory()->quoted()->create();
    Quote::factory()->for($shipment)->count(2)->create();

    $response = $this->actingAs($shipment->customer)
        ->get(route('shipments.show', $shipment));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('shipments/Show')
            ->has('quotes', 2)
            ->where('shipment.id', $shipment->id)
            ->where('can.acceptQuote', true)
            ->where('can.publish', false)
        );
});

test('the create page prefills from an owned shipment', function () {
    $shipment = Shipment::factory()->create(['cargo_type' => 'perishable', 'weight_kg' => 7500]);

    $this->actingAs($shipment->customer)
        ->get(route('shipments.create', ['from' => $shipment->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('prefill.cargo_type', 'perishable')
            ->where('prefill.weight_kg', 7500)
            ->where('prefill.origin_address', $shipment->origin_address)
        );
});

test('the create page ignores prefill from shipments the user does not own', function () {
    $shipment = Shipment::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get(route('shipments.create', ['from' => $shipment->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('prefill', null));
});
