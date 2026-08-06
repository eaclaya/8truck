<?php

use App\Models\TransporterProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('a customer can register through the api and receives a token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'API Customer',
        'email' => 'api@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'customer',
        'device_name' => 'pixel-7',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'roles', 'is_transporter']]);

    expect(User::query()->where('email', 'api@example.com')->first()->hasRole('customer'))->toBeTrue();
});

test('a transporter registration through the api creates the profile', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'API Trucker',
        'email' => 'trucker-api@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'transporter',
        'phone' => '+504 8888-8888',
    ]);

    $response->assertCreated()->assertJsonPath('user.is_transporter', true);
});

test('login returns a token for valid credentials and fails for bad ones', function () {
    $user = User::factory()->create(['password' => 'secret-password']);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret-password',
    ])->assertOk()->assertJsonStructure(['token']);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertUnprocessable();
});

test('the token authenticates api requests and logout revokes it', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('user.email', $user->email);

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

    expect($user->tokens()->count())->toBe(0);

    $this->app['auth']->forgetGuards();

    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

test('google sign-in verifies the id token and logs in an existing user', function () {
    config()->set('services.google.client_id', 'test-client-id');

    $user = User::factory()->create(['email' => 'google-user@example.com']);

    Http::fake([
        'oauth2.googleapis.com/*' => Http::response([
            'aud' => 'test-client-id',
            'email' => 'google-user@example.com',
            'email_verified' => 'true',
            'name' => 'Google User',
        ]),
    ]);

    $this->postJson('/api/v1/auth/google', ['id_token' => 'fake-token'])
        ->assertOk()
        ->assertJsonPath('user.email', 'google-user@example.com');
});

test('google sign-in for a new user requires a role, then creates the account', function () {
    config()->set('services.google.client_id', 'test-client-id');

    Http::fake([
        'oauth2.googleapis.com/*' => Http::response([
            'aud' => 'test-client-id',
            'email' => 'new-google@example.com',
            'email_verified' => 'true',
            'name' => 'Nuevo Usuario',
        ]),
    ]);

    $this->postJson('/api/v1/auth/google', ['id_token' => 'fake-token'])
        ->assertUnprocessable()
        ->assertJsonPath('needs_registration', true);

    $this->postJson('/api/v1/auth/google', [
        'id_token' => 'fake-token',
        'role' => 'transporter',
        'phone' => '+504 7777-7777',
    ])->assertOk()->assertJsonPath('user.is_transporter', true);

    expect(TransporterProfile::query()->whereRelation('user', 'email', 'new-google@example.com')->exists())->toBeTrue();
});

test('a google token with the wrong audience is rejected', function () {
    config()->set('services.google.client_id', 'test-client-id');

    Http::fake([
        'oauth2.googleapis.com/*' => Http::response([
            'aud' => 'someone-elses-client-id',
            'email' => 'attacker@example.com',
            'email_verified' => 'true',
        ]),
    ]);

    $this->postJson('/api/v1/auth/google', ['id_token' => 'fake-token'])
        ->assertUnprocessable();
});
