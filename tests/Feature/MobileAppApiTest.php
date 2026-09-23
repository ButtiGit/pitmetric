<?php

use App\Models\GalleryAsset;
use App\Models\User;

function mobileTokenFor(User $user): string
{
    $response = test()->postJson('/api/mobile/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest',
    ])->assertOk();

    return $response->json('token');
}

test('mobile login reports whether cloud database access is enabled', function () {
    $localOnly = User::factory()->create(['password' => 'password']);
    $cloud = User::factory()->withDatabaseAccess()->create(['password' => 'password']);

    $this->postJson('/api/mobile/login', ['email' => $localOnly->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('session.cloudEnabled', false);

    $this->postJson('/api/mobile/login', ['email' => $cloud->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('session.cloudEnabled', true);
});

test('mobile bearer token protects private endpoints', function () {
    $this->getJson('/api/mobile/me')->assertUnauthorized();

    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $token = mobileTokenFor($user);

    $this->withToken($token)->getJson('/api/mobile/me')
        ->assertOk()
        ->assertJsonPath('session.user.email', $user->email);
});

test('local only accounts cannot push gallery data to the cloud', function () {
    $user = User::factory()->create(['password' => 'password']);
    $token = mobileTokenFor($user);

    $this->withToken($token)->postJson('/api/mobile/gallery', [
        'client_id' => fake()->uuid(),
        'title' => 'Offline photo',
    ])->assertForbidden();
});

test('cloud enabled accounts can idempotently sync gallery metadata', function () {
    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $token = mobileTokenFor($user);
    $clientId = fake()->uuid();

    $payload = [
        'client_id' => $clientId,
        'title' => 'Pre qualifying setup',
        'description' => 'Front tyre detail',
        'captured_at' => now()->toIso8601String(),
    ];

    $this->withToken($token)->postJson('/api/mobile/gallery', $payload)->assertCreated();
    $this->withToken($token)->postJson('/api/mobile/gallery', $payload)->assertOk();

    expect(GalleryAsset::query()->where('user_id', $user->id)->where('client_id', $clientId)->count())->toBe(1);
});
