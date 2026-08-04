<?php

use App\Enums\DocumentStatus;
use App\Enums\ShipmentStatus;
use App\Filament\Resources\Documents\Pages\ListDocuments;
use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Filament\Resources\TransporterProfiles\Pages\ListTransporterProfiles;
use App\Models\Document;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function adminUser(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

test('guests are redirected to the admin login', function () {
    $this->get('/admin')->assertRedirect();
});

test('non-admin users cannot access the panel', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)->get('/admin')->assertForbidden();
});

test('admins can access the panel dashboard', function () {
    $this->actingAs(adminUser())->get('/admin')->assertOk();
});

test('the shipments list shows records to admins', function () {
    $shipments = Shipment::factory()->count(3)->create();

    $this->actingAs(adminUser());

    Livewire::test(ListShipments::class)
        ->assertOk()
        ->assertCanSeeTableRecords($shipments);
});

test('an admin can cancel an open shipment from the panel', function () {
    $shipment = Shipment::factory()->published()->create();

    $this->actingAs(adminUser());

    Livewire::test(ListShipments::class)
        ->callAction(TestAction::make('cancel')->table($shipment));

    expect($shipment->refresh()->status)->toBe(ShipmentStatus::Cancelled)
        ->and($shipment->statusHistories()->where('to_status', 'cancelled')->exists())->toBeTrue();
});

test('an admin can verify a transporter profile', function () {
    $profile = TransporterProfile::factory()->create();

    $this->actingAs(adminUser());

    Livewire::test(ListTransporterProfiles::class)
        ->callAction(TestAction::make('verify')->table($profile));

    expect($profile->refresh()->isVerified())->toBeTrue();
});

test('an admin can approve a pending document', function () {
    $document = Document::factory()->create();
    $admin = adminUser();

    $this->actingAs($admin);

    Livewire::test(ListDocuments::class)
        ->callAction(TestAction::make('approve')->table($document));

    $document->refresh();

    expect($document->status)->toBe(DocumentStatus::Approved)
        ->and($document->reviewed_by)->toBe($admin->id)
        ->and($document->reviewed_at)->not->toBeNull();
});

test('an admin can reject a pending document with notes', function () {
    $document = Document::factory()->create();

    $this->actingAs(adminUser());

    Livewire::test(ListDocuments::class)
        ->callAction(
            TestAction::make('reject')->table($document),
            ['notes' => 'Documento ilegible'],
        );

    $document->refresh();

    expect($document->status)->toBe(DocumentStatus::Rejected)
        ->and($document->notes)->toBe('Documento ilegible');
});
