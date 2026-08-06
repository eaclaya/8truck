<?php

use App\Events\QuoteAccepted;
use App\Events\QuoteSubmitted;
use App\Events\ShipmentStatusAdvanced;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\User;

beforeEach(function () {
    // phpunit boots with the null broadcaster, so channel callbacks were
    // registered there; point the default at reverb and re-register them.
    config()->set('broadcasting.default', 'reverb');
    require base_path('routes/channels.php');
});

test('shipment events broadcast on the shipment private channel', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();

    expect((new QuoteSubmitted($quote))->broadcastOn()->name)->toBe("private-shipments.{$shipment->id}")
        ->and((new QuoteAccepted($quote))->broadcastOn()->name)->toBe("private-shipments.{$shipment->id}")
        ->and((new ShipmentStatusAdvanced($shipment))->broadcastOn()->name)->toBe("private-shipments.{$shipment->id}")
        ->and((new QuoteSubmitted($quote))->broadcastWith())->toBe(['shipment_id' => $shipment->id]);
});

test('the shipment owner can authorize the private channel', function () {
    $shipment = Shipment::factory()->create();

    $this->actingAs($shipment->customer)
        ->post('/broadcasting/auth', [
            'channel_name' => "private-shipments.{$shipment->id}",
            'socket_id' => '123.456',
        ])
        ->assertOk();
});

test('the assigned transporter can authorize the private channel', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = Shipment::factory()->quoted()->create(['assigned_transporter_id' => $profile->id]);

    $this->actingAs($profile->user)
        ->post('/broadcasting/auth', [
            'channel_name' => "private-shipments.{$shipment->id}",
            'socket_id' => '123.456',
        ])
        ->assertOk();
});

test('strangers cannot authorize the shipment channel', function () {
    $shipment = Shipment::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post('/broadcasting/auth', [
            'channel_name' => "private-shipments.{$shipment->id}",
            'socket_id' => '123.456',
        ])
        ->assertForbidden();
});

test('a channel for a missing shipment is rejected', function () {
    $this->actingAs(User::factory()->create())
        ->post('/broadcasting/auth', [
            'channel_name' => 'private-shipments.999999',
            'socket_id' => '123.456',
        ])
        ->assertForbidden();
});
