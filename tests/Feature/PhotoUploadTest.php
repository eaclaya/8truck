<?php

use App\Actions\Quotes\AcceptQuoteAction;
use App\Actions\Shipments\TransitionShipmentStatusAction;
use App\Enums\DocumentStatus;
use App\Enums\ShipmentStatus;
use App\Models\City;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

function shipmentInTransitFor(TransporterProfile $profile): Shipment
{
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create(['transporter_profile_id' => $profile->id]);

    app(AcceptQuoteAction::class)->execute($shipment->customer, $quote);
    $shipment->refresh();

    $transition = app(TransitionShipmentStatusAction::class);
    $transition->execute($shipment, ShipmentStatus::DriverAssigned);
    $transition->execute($shipment, ShipmentStatus::PickedUp);
    $transition->execute($shipment, ShipmentStatus::InTransit);

    return $shipment;
}

test('cargo photos are attached when creating a shipment from the web', function () {
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
        'photos' => [
            UploadedFile::fake()->image('caja1.jpg', 800, 600),
            UploadedFile::fake()->image('caja2.jpg', 800, 600),
        ],
    ]);

    $shipment = Shipment::query()->where('customer_id', $customer->id)->firstOrFail();

    $response->assertRedirect(route('shipments.show', $shipment));

    expect($shipment->getMedia('cargo'))->toHaveCount(2)
        ->and($shipment->getFirstMedia('cargo')->getUrl('thumb'))->toContain('conversions');
});

test('the assigned transporter can upload proof of delivery', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = shipmentInTransitFor($profile);

    $this->actingAs($profile->user)->post(route('jobs.pod', $shipment), [
        'photos' => [UploadedFile::fake()->image('entrega.jpg')],
    ])->assertRedirect();

    expect($shipment->getMedia('pod'))->toHaveCount(1);
});

test('proof of delivery is rejected before pickup and from strangers', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = Shipment::factory()->quoted()->create(['assigned_transporter_id' => $profile->id]);

    $this->actingAs($profile->user)->post(route('jobs.pod', $shipment), [
        'photos' => [UploadedFile::fake()->image('entrega.jpg')],
    ])->assertForbidden();

    $inTransit = shipmentInTransitFor($profile);
    $stranger = TransporterProfile::factory()->verified()->create();

    $this->actingAs($stranger->user)->post(route('jobs.pod', $inTransit), [
        'photos' => [UploadedFile::fake()->image('entrega.jpg')],
    ])->assertForbidden();
});

test('a transporter can upload and list verification documents', function () {
    $profile = TransporterProfile::factory()->verified()->create();

    $this->actingAs($profile->user)->post(route('documents.store'), [
        'type' => 'driver_license',
        'file' => UploadedFile::fake()->image('licencia.jpg'),
    ])->assertRedirect();

    $document = $profile->documents()->firstOrFail();

    expect($document->status)->toBe(DocumentStatus::Pending);
    Storage::disk('local')->assertExists($document->path);

    $this->actingAs($profile->user)->get(route('documents.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('documents/Index')
            ->has('documents', 1)
        );
});

test('document downloads are restricted to the owner and admins', function () {
    $this->seed(RoleSeeder::class);

    $profile = TransporterProfile::factory()->verified()->create();

    $this->actingAs($profile->user)->post(route('documents.store'), [
        'type' => 'national_id',
        'file' => UploadedFile::fake()->image('id.jpg'),
    ]);

    $document = $profile->documents()->firstOrFail();

    $this->actingAs($profile->user)->get(route('documents.download', $document))->assertOk();

    $this->actingAs(User::factory()->create())
        ->get(route('documents.download', $document))
        ->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('documents.download', $document))->assertOk();
});

test('photos flow through the api with urls in the resource', function () {
    $customer = User::factory()->create();
    $shipment = Shipment::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs($customer)->postJson("/api/v1/shipments/{$shipment->id}/photos", [
        'photos' => [UploadedFile::fake()->image('caja.jpg')],
    ])->assertOk()->assertJsonCount(1, 'data.photos');

    $this->actingAs($customer)->getJson("/api/v1/shipments/{$shipment->id}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['photos' => [['id', 'url', 'thumb']]]]);
});

test('proof of delivery uploads through the api', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $shipment = shipmentInTransitFor($profile);

    $this->actingAs($profile->user)->postJson("/api/v1/jobs/{$shipment->id}/pod", [
        'photos' => [UploadedFile::fake()->image('entrega.jpg')],
    ])->assertOk()->assertJsonCount(1, 'data.pod_photos');
});

test('documents upload through the api and are listed', function () {
    $profile = TransporterProfile::factory()->verified()->create();

    $this->actingAs($profile->user)->postJson('/api/v1/documents', [
        'type' => 'insurance',
        'file' => UploadedFile::fake()->image('seguro.jpg'),
        'expires_at' => now()->addYear()->toDateString(),
    ])->assertCreated();

    $this->actingAs($profile->user)->getJson('/api/v1/documents')
        ->assertOk()
        ->assertJsonPath('data.0.type', 'insurance')
        ->assertJsonPath('data.0.status', 'pending');
});
