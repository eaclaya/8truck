<?php

use App\Enums\TruckAvailability;
use App\Models\TransporterProfile;
use App\Models\Truck;
use App\Models\TruckType;
use App\Models\User;

test('users without a transporter profile cannot manage trucks', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('trucks.index'))
        ->assertForbidden();
});

test('a transporter can register a truck', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    $type = TruckType::factory()->create();

    $response = $this->actingAs($profile->user)->post(route('trucks.store'), [
        'truck_type_id' => $type->id,
        'plate_number' => 'HAB 4521',
        'capacity_kg' => 12000,
    ]);

    $response->assertRedirect(route('trucks.index'));

    expect($profile->trucks()->where('plate_number', 'HAB 4521')->exists())->toBeTrue();
});

test('a transporter can change truck availability', function () {
    $truck = Truck::factory()->create();

    $response = $this->actingAs($truck->transporterProfile->user)
        ->patch(route('trucks.update', $truck), ['availability' => 'busy']);

    $response->assertRedirect();
    expect($truck->refresh()->availability)->toBe(TruckAvailability::Busy);
});

test('a transporter cannot modify another transporter\'s truck', function () {
    $truck = Truck::factory()->create();
    $other = TransporterProfile::factory()->verified()->create();

    $this->actingAs($other->user)
        ->patch(route('trucks.update', $truck), ['availability' => 'busy'])
        ->assertForbidden();

    $this->actingAs($other->user)
        ->delete(route('trucks.destroy', $truck))
        ->assertForbidden();
});

test('a transporter can delete their own truck', function () {
    $truck = Truck::factory()->create();

    $this->actingAs($truck->transporterProfile->user)
        ->delete(route('trucks.destroy', $truck))
        ->assertRedirect(route('trucks.index'));

    expect(Truck::query()->find($truck->id))->toBeNull();
});
