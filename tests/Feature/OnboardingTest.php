<?php

use App\Models\City;
use App\Models\OperatingRegion;
use App\Models\TransporterProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use MatanYadaev\EloquentSpatial\Objects\Point;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('registering as a transporter lands on the onboarding wizard', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Trucker',
        'email' => 'trucker-onboarding@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'transporter',
        'phone' => '+504 9999-9999',
    ]);

    $response->assertRedirect(route('onboarding'));
});

test('registering as a customer still lands on the dashboard', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Cliente',
        'email' => 'cliente-onboarding@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'customer',
    ]);

    $response->assertRedirect(route('dashboard'));
});

test('google signup as transporter continues into onboarding', function () {
    $this->withSession(['google_registration' => [
        'name' => 'SSO Trucker',
        'email' => 'sso-trucker@example.com',
    ]]);

    $this->post(route('google.store'), [
        'role' => 'transporter',
        'phone' => '+504 8888-0000',
    ])->assertRedirect(route('onboarding'));
});

test('logging in with an unconfigured transporter profile resumes onboarding', function () {
    $profile = TransporterProfile::factory()->create();
    $profile->user->update(['password' => 'password-123']);

    $this->post(route('login.store'), [
        'email' => $profile->user->email,
        'password' => 'password-123',
    ])->assertRedirect(route('onboarding'));
});

test('logging in with regions configured goes to the dashboard', function () {
    $profile = TransporterProfile::factory()->create();
    $profile->user->update(['password' => 'password-123']);
    OperatingRegion::factory()->for($profile)->create();

    $this->post(route('login.store'), [
        'email' => $profile->user->email,
        'password' => 'password-123',
    ])->assertRedirect(route('dashboard'));
});

test('the wizard shows current progress and catalogs', function () {
    $profile = TransporterProfile::factory()->verified()->create();
    OperatingRegion::factory()->for($profile)->create(['center' => new Point(14.07, -87.19)]);
    City::factory()->count(2)->create();

    $this->actingAs($profile->user)->get(route('onboarding'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('onboarding/Index')
            ->has('regions', 1)
            ->has('documents', 0)
            ->has('cities')
            ->missing('trucks')
        );
});

test('customers cannot open the transporter wizard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('onboarding'))
        ->assertForbidden();
});
