<?php

use App\Actions\Quotes\AcceptQuoteAction;
use App\Actions\Quotes\SubmitQuoteAction;
use App\Actions\Ratings\RateShipmentAction;
use App\Actions\Shipments\CompleteShipmentAction;
use App\Actions\Shipments\PublishShipmentAction;
use App\Actions\Shipments\TransitionShipmentStatusAction;
use App\Enums\ShipmentStatus;
use App\Models\OperatingRegion;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\Truck;
use App\Models\TruckType;
use App\Notifications\NewLoadAvailable;
use App\Notifications\QuoteAcceptedNotification;
use App\Notifications\QuoteReceived;
use App\Notifications\RatingReceived;
use App\Notifications\ShipmentCompletedNotification;
use App\Notifications\ShipmentStatusUpdated;
use Illuminate\Support\Facades\Notification;
use MatanYadaev\EloquentSpatial\Objects\Point;

function transporterCovering(float $lat, float $lng): TransporterProfile
{
    $profile = TransporterProfile::factory()->verified()->create();

    OperatingRegion::factory()->for($profile)->create([
        'center' => new Point($lat, $lng),
        'radius_m' => 50000,
    ]);

    return $profile;
}

test('publishing fans out to transporters covering the origin', function () {
    Notification::fake();

    $near = transporterCovering(14.0723, -87.1921);
    $far = transporterCovering(15.7597, -86.7822);

    $shipment = Shipment::factory()->create(['origin' => new Point(14.09, -87.20)]);

    app(PublishShipmentAction::class)->execute($shipment, $shipment->customer);

    Notification::assertSentTo($near->user, NewLoadAvailable::class);
    Notification::assertNotSentTo($far->user, NewLoadAvailable::class);
});

test('a transporter is not notified about their own shipment', function () {
    Notification::fake();

    $profile = transporterCovering(14.0723, -87.1921);
    $shipment = Shipment::factory()->create([
        'customer_id' => $profile->user_id,
        'origin' => new Point(14.09, -87.20),
    ]);

    app(PublishShipmentAction::class)->execute($shipment, $profile->user);

    Notification::assertNotSentTo($profile->user, NewLoadAvailable::class);
});

test('the truck type requirement filters the fan-out', function () {
    Notification::fake();

    $requestedType = TruckType::factory()->create();
    $withTruck = transporterCovering(14.0723, -87.1921);
    Truck::factory()->for($withTruck)->create(['truck_type_id' => $requestedType->id]);
    $withoutTruck = transporterCovering(14.0723, -87.1921);

    $shipment = Shipment::factory()->create([
        'origin' => new Point(14.09, -87.20),
        'truck_type_id' => $requestedType->id,
    ]);

    app(PublishShipmentAction::class)->execute($shipment, $shipment->customer);

    Notification::assertSentTo($withTruck->user, NewLoadAvailable::class);
    Notification::assertNotSentTo($withoutTruck->user, NewLoadAvailable::class);
});

test('the customer is notified when a quote arrives', function () {
    Notification::fake();

    $shipment = Shipment::factory()->published()->create();
    $transporter = TransporterProfile::factory()->create();

    app(SubmitQuoteAction::class)->execute($transporter, $shipment, ['amount' => 9000]);

    Notification::assertSentTo($shipment->customer, QuoteReceived::class);
});

test('the transporter is notified when their quote is accepted', function () {
    Notification::fake();

    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();

    app(AcceptQuoteAction::class)->execute($shipment->customer, $quote);

    Notification::assertSentTo($quote->transporterProfile->user, QuoteAcceptedNotification::class);
});

test('the customer is notified when the transporter advances the job', function () {
    Notification::fake();

    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();
    app(AcceptQuoteAction::class)->execute($shipment->customer, $quote);
    $shipment->refresh();

    $this->actingAs($quote->transporterProfile->user)
        ->post(route('jobs.advance', $shipment), ['status' => 'driver_assigned'])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($shipment->customer, ShipmentStatusUpdated::class);
});

test('the transporter is notified on completion and the ratee on rating', function () {
    Notification::fake();

    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();
    app(AcceptQuoteAction::class)->execute($shipment->customer, $quote);
    $shipment->refresh();

    $transition = app(TransitionShipmentStatusAction::class);
    foreach ([ShipmentStatus::DriverAssigned, ShipmentStatus::PickedUp, ShipmentStatus::InTransit, ShipmentStatus::Delivered] as $status) {
        $transition->execute($shipment, $status);
    }
    app(CompleteShipmentAction::class)->execute($shipment);

    Notification::assertSentTo($quote->transporterProfile->user, ShipmentCompletedNotification::class);

    app(RateShipmentAction::class)->execute($shipment->customer, $shipment->refresh(), 5, 'Excelente');

    Notification::assertSentTo($quote->transporterProfile->user, RatingReceived::class);
});

test('notifications land in the database and power the bell', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();

    app(AcceptQuoteAction::class)->execute($shipment->customer, $quote);

    $transporterUser = $quote->transporterProfile->user;

    expect($transporterUser->notifications()->count())->toBe(1);

    $notification = $transporterUser->notifications()->first();

    expect($notification->data['title'])->not->toBeEmpty()
        ->and($notification->data['url'])->toBe(route('jobs.index', absolute: false));

    $this->actingAs($transporterUser)->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('notifications.unread', 1)
            ->has('notifications.items', 1)
        );

    $this->actingAs($transporterUser)->post(route('notifications.readAll'));

    expect($transporterUser->unreadNotifications()->count())->toBe(0);
});

test('opening a notification marks it read and redirects to its url', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();
    app(AcceptQuoteAction::class)->execute($shipment->customer, $quote);

    $transporterUser = $quote->transporterProfile->user;
    $notification = $transporterUser->notifications()->first();

    $this->actingAs($transporterUser)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('jobs.index', absolute: false));

    expect($notification->fresh()->read_at)->not->toBeNull();
});
