<?php

use App\Actions\Quotes\AcceptQuoteAction;
use App\Enums\ShipmentStatus;
use App\Models\OperatingRegion;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Notifications\ShipmentStatusUpdated;
use Illuminate\Support\Facades\Notification;
use MatanYadaev\EloquentSpatial\Objects\Point;

test('stale published and quoted shipments expire and the customer is notified', function () {
    Notification::fake();

    $stalePublished = Shipment::factory()->published()->create(['expires_at' => now()->subHour()]);
    $staleQuoted = Shipment::factory()->quoted()->create(['expires_at' => now()->subHour()]);

    $this->artisan('shipments:expire')
        ->expectsOutputToContain('Expired 2 shipment(s).')
        ->assertSuccessful();

    expect($stalePublished->refresh()->status)->toBe(ShipmentStatus::Expired)
        ->and($staleQuoted->refresh()->status)->toBe(ShipmentStatus::Expired)
        ->and($stalePublished->statusHistories()->where('to_status', 'expired')->exists())->toBeTrue();

    Notification::assertSentTo($stalePublished->customer, ShipmentStatusUpdated::class);
    Notification::assertSentTo($staleQuoted->customer, ShipmentStatusUpdated::class);
});

test('future-dated, unexpired, and progressed shipments are left alone', function () {
    $future = Shipment::factory()->published()->create(['expires_at' => now()->addDay()]);
    $noExpiry = Shipment::factory()->published()->create(['expires_at' => null]);
    $draft = Shipment::factory()->create(['expires_at' => now()->subHour()]);

    $accepted = Shipment::factory()->quoted()->create(['expires_at' => now()->subHour()]);
    $quote = Quote::factory()->for($accepted)->create();
    app(AcceptQuoteAction::class)->execute($accepted->customer, $quote);

    $this->artisan('shipments:expire')->assertSuccessful();

    expect($future->refresh()->status)->toBe(ShipmentStatus::Published)
        ->and($noExpiry->refresh()->status)->toBe(ShipmentStatus::Published)
        ->and($draft->refresh()->status)->toBe(ShipmentStatus::Draft)
        ->and($accepted->refresh()->status)->toBe(ShipmentStatus::Accepted);
});

test('expired shipments disappear from the load board', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    OperatingRegion::factory()->for($profile)->create([
        'center' => new Point(14.0723, -87.1921),
        'radius_m' => 50000,
    ]);

    Shipment::factory()->published()->create([
        'origin' => new Point(14.09, -87.20),
        'pickup_date' => now()->addDay()->toDateString(),
        'expires_at' => now()->subHour(),
    ]);

    $this->artisan('shipments:expire')->assertSuccessful();

    $this->actingAs($profile->user)->get(route('loads.index'))
        ->assertInertia(fn ($page) => $page->has('loads.data', 0));
});
