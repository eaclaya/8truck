<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('the google button redirects to the provider', function () {
    Socialite::fake('google');

    $this->get(route('google.redirect'))->assertRedirect();
});

test('an existing account signs straight in via google', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);

    Socialite::fake('google', SocialiteUser::fake([
        'name' => 'Existing User',
        'email' => 'existing@example.com',
    ]));

    $this->get(route('google.callback'))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('a new google user is sent to the role completion step', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'name' => 'Nuevo Usuario',
        'email' => 'nuevo-sso@example.com',
    ]));

    $this->get(route('google.callback'))
        ->assertRedirect(route('google.complete'));

    $this->assertGuest();

    $this->get(route('google.complete'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/CompleteRegistration')
            ->where('email', 'nuevo-sso@example.com')
        );
});

test('completing as customer creates a verified account and logs in', function () {
    $this->withSession(['google_registration' => [
        'name' => 'Cliente SSO',
        'email' => 'cliente-sso@example.com',
    ]]);

    $this->post(route('google.store'), ['role' => 'customer'])
        ->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'cliente-sso@example.com')->firstOrFail();

    expect($user->hasRole('customer'))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->transporterProfile)->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('completing as transporter requires a phone and creates the profile', function () {
    $this->withSession(['google_registration' => [
        'name' => 'Transportista SSO',
        'email' => 'trans-sso@example.com',
    ]]);

    $this->post(route('google.store'), ['role' => 'transporter'])
        ->assertSessionHasErrors('phone');

    $this->withSession(['google_registration' => [
        'name' => 'Transportista SSO',
        'email' => 'trans-sso@example.com',
    ]]);

    $this->post(route('google.store'), [
        'role' => 'transporter',
        'phone' => '+504 9999-0000',
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'trans-sso@example.com')->firstOrFail();

    expect($user->transporterProfile?->phone)->toBe('+504 9999-0000');
});

test('the completion step without a pending google session goes to register', function () {
    $this->get(route('google.complete'))->assertRedirect(route('register'));
    $this->post(route('google.store'), ['role' => 'customer'])->assertRedirect(route('register'));
});

test('a denied or invalid grant lands back on login with an error', function () {
    $this->get(route('google.callback'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
