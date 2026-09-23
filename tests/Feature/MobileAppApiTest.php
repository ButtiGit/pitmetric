<?php

use App\Models\GalleryAsset;
use App\Models\MobileSyncOperation;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
    $token = $this->postJson('/api/mobile/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest',
    ])->assertOk()->json('token');

    $this->withToken($token)->getJson('/api/mobile/me')
        ->assertOk()
        ->assertJsonPath('session.user.email', $user->email);
});

test('local only accounts cannot push gallery data to the cloud', function () {
    $user = User::factory()->create(['password' => 'password']);
    $token = $this->postJson('/api/mobile/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest',
    ])->assertOk()->json('token');

    $this->withToken($token)->postJson('/api/mobile/gallery', [
        'client_id' => fake()->uuid(),
        'title' => 'Offline photo',
    ])->assertForbidden();
});

test('cloud enabled accounts can idempotently sync gallery metadata', function () {
    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $token = $this->postJson('/api/mobile/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest',
    ])->assertOk()->json('token');
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

test('cloud enabled accounts can upload the gallery image with its metadata', function () {
    Storage::fake('public');

    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $token = $this->postJson('/api/mobile/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest',
    ])->assertOk()->json('token');

    $response = $this->withToken($token)->post('/api/mobile/gallery', [
        'client_id' => fake()->uuid(),
        'title' => 'Paddock detail',
        'description' => 'Saved offline and uploaded later',
        'captured_at' => now()->toIso8601String(),
        'media_type' => 'image',
        'media' => UploadedFile::fake()->image('paddock.jpg', 1200, 900),
    ], ['Accept' => 'application/json']);

    $response->assertCreated()->assertJsonPath('media_type', 'image');

    $asset = GalleryAsset::query()->where('user_id', $user->id)->firstOrFail();
    expect($asset->path)->not->toBeNull();
    Storage::disk('public')->assertExists($asset->path);
});

test('cloud enabled accounts can upload gallery video', function () {
    Storage::fake('public');

    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $token = $this->postJson('/api/mobile/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest',
    ])->assertOk()->json('token');

    $response = $this->withToken($token)->post('/api/mobile/gallery', [
        'client_id' => fake()->uuid(),
        'title' => 'Onboard clip',
        'description' => 'Short trackside video',
        'captured_at' => now()->toIso8601String(),
        'media_type' => 'video',
        'media' => UploadedFile::fake()->create('onboard.mp4', 2048, 'video/mp4'),
    ], ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('media_type', 'video')
        ->assertJsonPath('mime_type', 'video/mp4');

    $asset = GalleryAsset::query()->where('user_id', $user->id)->firstOrFail();
    expect($asset->media_type)->toBe('video');
    Storage::disk('public')->assertExists($asset->path);
});

test('mobile bootstrap creates a personal workspace and exposes offline catalogs', function () {
    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $token = $this->postJson('/api/mobile/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest',
    ])->assertOk()->json('token');

    $this->withToken($token)->getJson('/api/mobile/bootstrap')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'workspace' => ['id', 'name'],
                'events',
                'vehicles',
                'sessions',
                'configurations',
                'maintenance_schedules',
                'work_orders',
                'setups',
                'setup_fields',
            ],
            'synced_at',
        ]);

    expect($user->fresh()->workspaces()->count())->toBe(1);
});

test('mobile domain sync is idempotent and writes real garage records', function () {
    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $token = $this->postJson('/api/mobile/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'pest',
    ])->assertOk()->json('token');
    $clientId = fake()->uuid();

    $payload = [
        'client_id' => $clientId,
        'operation' => 'vehicle.create',
        'payload' => [
            'name' => 'Offline kart',
            'category' => 'kart',
            'manufacturer' => 'PitMetric',
        ],
    ];

    $this->withToken($token)->postJson('/api/mobile/sync', $payload)
        ->assertCreated()
        ->assertJsonPath('duplicate', false);

    $this->withToken($token)->postJson('/api/mobile/sync', $payload)
        ->assertOk()
        ->assertJsonPath('duplicate', true);

    expect(Vehicle::query()->withoutGlobalScopes()->where('name', 'Offline kart')->count())->toBe(1)
        ->and(MobileSyncOperation::query()->where('client_id', $clientId)->count())->toBe(1);
});

test('public app page exposes the stable android apk download', function () {
    $this->get('/app')
        ->assertOk()
        ->assertSee('PitMetric.apk')
        ->assertSee('android-latest');
});
