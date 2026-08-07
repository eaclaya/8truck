<?php

use App\Enums\ShipmentStatus;
use App\Models\City;
use App\Models\OperatingRegion;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\Truck;
use App\Models\User;
use MatanYadaev\EloquentSpatial\Objects\Point;

function apiCustomer(): User
{
    return User::factory()->create();
}

function apiTransporter(): TransporterProfile
{
    $profile = TransporterProfile::factory()->verified()->create();

    OperatingRegion::factory()->for($profile)->create([
        'center' => new Point(14.0723, -87.1921),
        'radius_m' => 50000,
    ]);

    Truck::factory()->for($profile)->create();

    return $profile;
}

test('the full marketplace journey works over the api', function () {
    $customer = apiCustomer();
    $transporter = apiTransporter();

    $tgu = City::factory()->create(['location' => new Point(14.0723, -87.1921)]);
    $sps = City::factory()->create(['location' => new Point(15.5042, -88.0250)]);

    // Customer creates and publishes a shipment.
    $shipmentId = $this->actingAs($customer)->postJson('/api/v1/shipments', [
        'origin_city_id' => $tgu->id,
        'origin_address' => 'Col. Kennedy',
        'destination_city_id' => $sps->id,
        'destination_address' => 'Zona Industrial',
        'pickup_date' => now()->addDays(2)->toDateString(),
        'cargo_type' => 'general',
        'weight_kg' => 5000,
    ])->assertCreated()->json('data.id');

    $this->actingAs($customer)->postJson("/api/v1/shipments/{$shipmentId}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    // Transporter finds it on the load board (region match) and quotes.
    $this->actingAs($transporter->user)->getJson('/api/v1/loads')
        ->assertOk()
        ->assertJsonPath('data.0.id', $shipmentId);

    $quoteId = $this->actingAs($transporter->user)->postJson("/api/v1/loads/{$shipmentId}/quotes", [
        'amount' => 14500,
        'truck_id' => $transporter->trucks()->value('id'),
    ])->assertCreated()->json('data.id');

    // Customer sees the quote and accepts it.
    $this->actingAs($customer)->getJson("/api/v1/shipments/{$shipmentId}")
        ->assertOk()
        ->assertJsonCount(1, 'data.quotes');

    $this->actingAs($customer)->postJson("/api/v1/quotes/{$quoteId}/accept")
        ->assertOk()
        ->assertJsonPath('data.status', 'accepted');

    // Transporter advances the job to delivered.
    foreach (['driver_assigned', 'picked_up', 'in_transit', 'delivered'] as $status) {
        $this->actingAs($transporter->user)->postJson("/api/v1/jobs/{$shipmentId}/advance", [
            'status' => $status,
        ])->assertOk();
    }

    // Customer completes and rates.
    $this->actingAs($customer)->postJson("/api/v1/shipments/{$shipmentId}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $this->actingAs($customer)->postJson("/api/v1/shipments/{$shipmentId}/ratings", [
        'score' => 5,
        'comment' => 'Excelente',
    ])->assertCreated();

    expect(Shipment::query()->findOrFail($shipmentId)->status)->toBe(ShipmentStatus::Completed);
});

test('domain errors surface as 422 json', function () {
    $shipment = Shipment::factory()->published()->create();
    $transporter = apiTransporter();
    $truckId = $transporter->trucks()->value('id');

    $this->actingAs($transporter->user)->postJson("/api/v1/loads/{$shipment->id}/quotes", [
        'amount' => 1000,
        'truck_id' => $truckId,
    ]);

    $this->actingAs($transporter->user)->postJson("/api/v1/loads/{$shipment->id}/quotes", [
        'amount' => 900,
        'truck_id' => $truckId,
    ])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('marketplace.already_quoted'));
});

test('the api rejects a quote without a truck', function () {
    $shipment = Shipment::factory()->published()->create();
    $transporter = apiTransporter();

    $this->actingAs($transporter->user)->postJson("/api/v1/loads/{$shipment->id}/quotes", ['amount' => 1000])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('truck_id');
});

test('authorization is enforced over the api', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();
    $stranger = apiCustomer();

    $this->actingAs($stranger)->getJson("/api/v1/shipments/{$shipment->id}")->assertForbidden();
    $this->actingAs($stranger)->postJson("/api/v1/quotes/{$quote->id}/accept")->assertForbidden();
    $this->actingAs($stranger)->getJson('/api/v1/loads')->assertForbidden();
});

test('notifications are readable and markable over the api', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();

    $this->actingAs($shipment->customer)->postJson("/api/v1/quotes/{$quote->id}/accept")->assertOk();

    $transporterUser = $quote->transporterProfile->user;

    $response = $this->actingAs($transporterUser)->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('unread', 1);

    $notificationId = $response->json('data.0.id');

    $this->actingAs($transporterUser)->postJson("/api/v1/notifications/{$notificationId}/read")->assertOk();

    $this->actingAs($transporterUser)->getJson('/api/v1/notifications')->assertJsonPath('unread', 0);
});

test('catalogs are available to authenticated users', function () {
    City::factory()->count(2)->create();

    $this->getJson('/api/v1/cities')->assertUnauthorized();

    $this->actingAs(apiCustomer())->getJson('/api/v1/cities')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'department', 'lat', 'lng']]]);
});
