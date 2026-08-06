<?php

use App\Actions\Quotes\AcceptQuoteAction;
use App\Models\DeviceToken;
use App\Models\OperatingRegion;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\User;
use App\Notifications\QuoteAcceptedNotification;
use Illuminate\Support\Facades\Hash;
use MatanYadaev\EloquentSpatial\Objects\Point;
use NotificationChannels\Fcm\FcmChannel;

test('a device token can be registered, reassigned, and removed', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA)->postJson('/api/v1/devices', [
        'token' => 'fcm-token-1',
        'platform' => 'android',
    ])->assertCreated();

    expect($userA->deviceTokens()->count())->toBe(1);

    // Same physical device logs into another account: token moves.
    $this->actingAs($userB)->postJson('/api/v1/devices', ['token' => 'fcm-token-1'])->assertCreated();

    expect(DeviceToken::query()->where('token', 'fcm-token-1')->value('user_id'))->toBe($userB->id)
        ->and($userA->deviceTokens()->count())->toBe(0);

    $this->actingAs($userB)->deleteJson('/api/v1/devices', ['token' => 'fcm-token-1'])->assertOk();

    expect(DeviceToken::query()->count())->toBe(0);
});

test('notifications include the fcm channel only when enabled', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();
    $notification = new QuoteAcceptedNotification($quote);

    config()->set('services.fcm.enabled', false);
    expect($notification->via($quote->transporterProfile->user))->not->toContain(FcmChannel::class);

    config()->set('services.fcm.enabled', true);
    expect($notification->via($quote->transporterProfile->user))->toContain(FcmChannel::class);

    $fcm = $notification->toFcm($quote->transporterProfile->user);
    expect($fcm->notification->title)->not->toBeEmpty()
        ->and($fcm->data['url'])->toBe(route('jobs.index', absolute: false));
});

test('users route fcm notifications to their registered tokens', function () {
    $user = User::factory()->create();
    DeviceToken::create(['user_id' => $user->id, 'token' => 'tok-a']);
    DeviceToken::create(['user_id' => $user->id, 'token' => 'tok-b']);

    expect($user->routeNotificationForFcm())->toBe(['tok-a', 'tok-b']);
});

test('api job listings include return trips for active jobs', function () {
    $profile = TransporterProfile::factory()->verified()->create();

    $outbound = Shipment::factory()->quoted()->create([
        'origin' => new Point(14.0723, -87.1921),
        'destination' => new Point(15.5042, -88.0250),
        'pickup_date' => now()->addDay()->toDateString(),
    ]);
    $quote = Quote::factory()->for($outbound)->create(['transporter_profile_id' => $profile->id]);
    app(AcceptQuoteAction::class)->execute($outbound->customer, $quote);

    $returnLoad = Shipment::factory()->published()->create([
        'origin' => new Point(15.5042, -88.0250),
        'destination' => new Point(14.0723, -87.1921),
        'pickup_date' => now()->addDays(2)->toDateString(),
    ]);

    $this->actingAs($profile->user)->getJson('/api/v1/jobs')
        ->assertOk()
        ->assertJsonPath('data.0.return_trips.0.id', $returnLoad->id);
});

test('shipment resources expose ability flags for the current user', function () {
    $shipment = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($shipment)->create();

    $this->actingAs($shipment->customer)->getJson("/api/v1/shipments/{$shipment->id}")
        ->assertOk()
        ->assertJsonPath('data.can.accept_quote', true)
        ->assertJsonPath('data.can.publish', false);

    app(AcceptQuoteAction::class)->execute($shipment->customer, $quote);

    $this->actingAs($quote->transporterProfile->user)->getJson('/api/v1/jobs')
        ->assertOk()
        ->assertJsonPath('data.0.can.advance', true)
        ->assertJsonPath('data.0.can.accept_quote', false);
});

test('the profile endpoint updates identity and transporter phone', function () {
    $profile = TransporterProfile::factory()->verified()->create(['phone' => '+504 1111-1111']);

    $this->actingAs($profile->user)->patchJson('/api/v1/profile', [
        'name' => 'Nuevo Nombre',
        'email' => 'nuevo@example.com',
        'phone' => '+504 2222-2222',
    ])->assertOk()->assertJsonPath('user.phone', '+504 2222-2222');

    expect($profile->user->refresh()->name)->toBe('Nuevo Nombre')
        ->and($profile->refresh()->phone)->toBe('+504 2222-2222');
});

test('the password endpoint requires the current password', function () {
    $user = User::factory()->create(['password' => 'old-password-1']);

    $this->actingAs($user)->putJson('/api/v1/password', [
        'current_password' => 'wrong',
        'password' => 'new-password-1',
        'password_confirmation' => 'new-password-1',
    ])->assertUnprocessable();

    $this->actingAs($user)->putJson('/api/v1/password', [
        'current_password' => 'old-password-1',
        'password' => 'new-password-1',
        'password_confirmation' => 'new-password-1',
    ])->assertOk();

    expect(Hash::check('new-password-1', $user->refresh()->password))->toBeTrue();
});

test('the mobile dashboard endpoint mirrors the web widgets', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    OperatingRegion::factory()->for($profile)->create([
        'center' => new Point(14.0723, -87.1921),
        'radius_m' => 50000,
    ]);
    Shipment::factory()->published()->create([
        'origin' => new Point(14.09, -87.20),
        'pickup_date' => now()->addDays(2)->toDateString(),
    ]);

    $this->actingAs($profile->user)->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.transporter.stats.loads', 1)
        ->assertJsonStructure(['data' => ['customer' => ['stats', 'attention'], 'transporter' => ['stats', 'jobs', 'loads']]]);
});
