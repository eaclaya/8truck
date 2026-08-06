<?php

use App\Actions\Quotes\AcceptQuoteAction;
use App\Models\OperatingRegion;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use App\Models\User;
use MatanYadaev\EloquentSpatial\Objects\Point;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('customers see shipment stats and attention items', function () {
    $customer = User::factory()->create();
    Shipment::factory()->create(['customer_id' => $customer->id]);
    $quoted = Shipment::factory()->quoted()->create(['customer_id' => $customer->id]);
    Quote::factory()->for($quoted)->create();

    $response = $this->actingAs($customer)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('customer.stats.draft', 1)
            ->where('customer.stats.awaiting', 1)
            ->has('customer.attention', 1)
            ->where('customer.attention.0.action', 'review_quotes')
            ->where('customer.attention.0.pending_quotes', 1)
            ->where('transporter', null)
        );
});

test('transporters additionally see loads and job widgets', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    OperatingRegion::factory()->for($profile)->create([
        'center' => new Point(14.0723, -87.1921),
        'radius_m' => 50000,
    ]);

    Shipment::factory()->published()->create([
        'origin' => new Point(14.09, -87.20),
        'pickup_date' => now()->addDays(2)->toDateString(),
    ]);

    $job = Shipment::factory()->quoted()->create();
    $quote = Quote::factory()->for($job)->create(['transporter_profile_id' => $profile->id]);
    app(AcceptQuoteAction::class)->execute($job->customer, $quote);

    $response = $this->actingAs($profile->user)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('transporter.stats.loads', 1)
            ->where('transporter.stats.activeJobs', 1)
            ->has('transporter.jobs', 1)
            ->has('transporter.loads', 1)
        );
});
