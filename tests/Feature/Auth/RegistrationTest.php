<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
    $this->seed(RoleSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new customers can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'customer',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->hasRole('customer'))->toBeTrue()
        ->and($user->transporterProfile)->toBeNull();
});

test('new transporters register with a phone and get a profile', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Trucker User',
        'email' => 'trucker@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'transporter',
        'phone' => '+504 9999-9999',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('onboarding', absolute: false));

    $user = User::query()->where('email', 'trucker@example.com')->firstOrFail();

    expect($user->hasRole('transporter'))->toBeTrue()
        ->and($user->transporterProfile)->not->toBeNull()
        ->and($user->transporterProfile->phone)->toBe('+504 9999-9999');
});

test('registration requires a role', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('role');
    $this->assertGuest();
});

test('transporter registration requires a phone', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Trucker User',
        'email' => 'trucker@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'transporter',
    ]);

    $response->assertSessionHasErrors('phone');
    $this->assertGuest();
});
