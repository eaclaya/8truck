<?php

use App\Actions\Shipments\FindReturnTripsAction;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\User;
use Database\Seeders\DemoSeeder;

test('the demo seeder builds a marketplace snapshot covering every lifecycle stage', function () {
    $this->seed(DemoSeeder::class);

    foreach (['customer@demo.test', 'transporter@demo.test', 'admin@demo.test'] as $email) {
        expect(User::query()->where('email', $email)->exists())->toBeTrue("missing fixed account {$email}");
    }

    foreach (ShipmentStatus::cases() as $status) {
        expect(Shipment::query()->where('status', $status)->exists())
            ->toBeTrue("no shipment in status {$status->value}");
    }

    $completed = Shipment::query()->where('status', ShipmentStatus::Completed)->with('commission')->get();

    expect($completed)->not->toBeEmpty()
        ->and($completed->every(fn (Shipment $shipment) => $shipment->commission !== null))->toBeTrue()
        ->and($completed->every(fn (Shipment $shipment) => $shipment->ratings()->count() === 2))->toBeTrue();

    $rated = TransporterProfile::query()->where('rating_count', '>', 0)->exists();
    expect($rated)->toBeTrue();
});

test('the demo transporter has a guaranteed return trip match', function () {
    $this->seed(DemoSeeder::class);

    $profile = TransporterProfile::query()
        ->whereRelation('user', 'email', 'transporter@demo.test')
        ->firstOrFail();

    $outbound = Shipment::query()
        ->where('assigned_transporter_id', $profile->id)
        ->where('status', ShipmentStatus::Accepted)
        ->firstOrFail();

    $matches = app(FindReturnTripsAction::class)->execute($outbound);

    expect($matches)->not->toBeEmpty()
        ->and($matches->pluck('special_instructions'))->toContain('Demo: carga de retorno');
});

test('the demo seeder is idempotent', function () {
    $this->seed(DemoSeeder::class);
    $shipments = Shipment::count();
    $users = User::count();

    $this->seed(DemoSeeder::class);

    expect(Shipment::count())->toBe($shipments)
        ->and(User::count())->toBe($users);
});
