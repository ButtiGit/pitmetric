<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\GalleryAsset;
use App\Models\MobileSyncOperation;
use App\Models\Session;
use App\Models\SessionTyreReading;
use App\Models\TechnicalSetup;
use App\Models\TimingLap;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Services\CreateConfigurationVersionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function mobileOfflinePilotKart(User $user): array
{
    $workspace = $user->workspaces()->firstOrFail();
    $kart = Vehicle::factory()->create(['workspace_id' => $workspace->getKey(), 'category' => 'kart']);
    $type = ComponentType::create(['name' => 'Offline pilot engine']);
    $component = Component::create(['component_type_id' => $type->getKey(), 'name' => 'Engine', 'status' => 'active']);
    ComponentInstallation::create([
        'vehicle_id' => $kart->getKey(), 'component_id' => $component->getKey(),
        'created_by' => $user->getKey(), 'installed_at' => now(),
    ]);
    $configuration = Configuration::create(['vehicle_id' => $kart->getKey(), 'name' => 'Offline configuration', 'status' => 'active']);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, [$component->getKey()]);

    return compact('kart', 'version', 'workspace');
}

test('capacitor origin can preflight the mobile login endpoint', function () {
    $this->withHeaders([
        'Origin' => 'https://localhost',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'content-type',
    ])->options('/api/mobile/login')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'https://localhost');
});

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
    $this->get(route('localized.app', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('PitMetric.apk')
        ->assertSee('android-latest');
});

test('PWA login and authenticated scope consistently select the requested active workspace', function () {
    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $workspace = $user->workspaces()->firstOrFail();
    $second = Workspace::factory()->create();
    $user->workspaces()->attach($second, ['role' => 'mechanic_engineer', 'status' => 'active', 'joined_at' => now()]);
    $token = $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()->assertJsonPath('session.workspace.id', $workspace->getKey())->json('token');

    $this->withToken($token)->withHeader('X-Pitmetric-Workspace', (string) $second->getKey())
        ->getJson('/api/mobile/me')->assertOk()->assertJsonPath('session.workspace.id', $second->getKey());
    $this->getJson('/api/mobile/bootstrap')->assertOk()->assertJsonPath('data.workspace.id', $second->getKey());

    $outsider = Workspace::factory()->create();
    $this->withHeader('X-Pitmetric-Workspace', (string) $outsider->getKey())->getJson('/api/mobile/bootstrap')->assertForbidden();
    $this->withHeader('X-Pitmetric-Workspace', 'invalid')->getJson('/api/mobile/me')->assertForbidden();
});

test('PWA session retries and dependent offline laps and tyre readings create each domain record once', function () {
    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $this->actingAs($user);
    ['version' => $version, 'workspace' => $workspace] = mobileOfflinePilotKart($user);
    $token = $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password'])->assertOk()->json('token');
    $this->withToken($token)->withHeader('X-Pitmetric-Workspace', (string) $workspace->getKey());
    $clientId = fake()->uuid();
    $sessionOperation = [
        'client_id' => $clientId, 'operation' => 'session.create',
        'payload' => ['configuration_version_id' => $version->getKey(), 'session_type' => 'practice',
            'started_at' => now()->toIso8601String(), 'completed_laps' => 3, 'best_lap_ms' => 52123],
    ];
    $sessionId = $this->postJson('/api/mobile/sync', $sessionOperation)->assertCreated()->json('result.id');
    $this->postJson('/api/mobile/sync', $sessionOperation)->assertOk()->assertJsonPath('duplicate', true);
    $lapOperation = ['client_id' => fake()->uuid(), 'operation' => 'timing.lap.create',
        'payload' => ['session_client_id' => $clientId, 'lap_time_ms' => 51987]];
    $this->postJson('/api/mobile/sync', $lapOperation)->assertCreated();
    $this->postJson('/api/mobile/sync', $lapOperation)->assertOk()->assertJsonPath('duplicate', true);

    foreach (['cold', 'hot'] as $phase) {
        $reading = ['client_id' => fake()->uuid(), 'operation' => 'session.tyres.create', 'payload' => [
            'session_client_id' => $clientId, 'phase' => $phase, 'pressure_fl' => $phase === 'cold' ? 0.8 : 1.1,
            'measured_at' => now()->toIso8601String(),
        ]];
        $this->postJson('/api/mobile/sync', $reading)->assertCreated();
        $this->postJson('/api/mobile/sync', $reading)->assertOk()->assertJsonPath('duplicate', true);
    }
    expect(Session::query()->count())->toBe(1)
        ->and(TimingLap::query()->where('session_id', $sessionId)->orderBy('lap_number')->pluck('lap_time_ms')->all())->toBe([52123, 51987])
        ->and(SessionTyreReading::query()->where('session_id', $sessionId)->count())->toBe(2)
        ->and(Session::query()->findOrFail($sessionId)->setupSnapshot->values)->toBe([]);
    $this->getJson('/api/mobile/bootstrap')->assertOk()->assertJsonCount(2, 'data.timing_laps');
});

test('PWA rejects laps from foreign sessions and unresolved local session references without consuming client IDs', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($owner);
    ['kart' => $kart, 'version' => $version] = mobileOfflinePilotKart($owner);
    $foreign = Session::create(['vehicle_id' => $kart->getKey(), 'configuration_version_id' => $version->getKey(),
        'session_type' => 'practice', 'started_at' => now(), 'status' => 'draft', 'created_by' => $owner->getKey()]);
    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $token = $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password'])->assertOk()->json('token');
    $this->withToken($token)->withHeader('X-Pitmetric-Workspace', (string) $user->workspaces()->firstOrFail()->getKey());
    $clientId = fake()->uuid();
    $this->postJson('/api/mobile/sync', ['client_id' => $clientId, 'operation' => 'timing.lap.create',
        'payload' => ['session_id' => $foreign->getKey(), 'lap_time_ms' => 50123]])->assertNotFound();
    $this->postJson('/api/mobile/sync', ['client_id' => $clientId, 'operation' => 'timing.lap.create',
        'payload' => ['session_client_id' => fake()->uuid(), 'lap_time_ms' => 50123]])->assertUnprocessable();
    $this->postJson('/api/mobile/sync', ['client_id' => $clientId, 'operation' => 'timing.lap.create',
        'payload' => ['session_id' => $foreign->getKey(), 'lap_time_ms' => 0]])->assertUnprocessable();
    expect(MobileSyncOperation::query()->where('client_id', $clientId)->exists())->toBeFalse()
        ->and(TimingLap::query()->withoutGlobalScopes()->count())->toBe(0);
});

test('PWA respects read only and revoked team memberships and never reuses a UUID in another workspace', function () {
    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $workspace = $user->workspaces()->firstOrFail();
    $token = $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password'])->assertOk()->json('token');
    $this->withToken($token)->withHeader('X-Pitmetric-Workspace', (string) $workspace->getKey());
    $payload = ['client_id' => fake()->uuid(), 'operation' => 'vehicle.create', 'payload' => ['name' => 'Kart', 'category' => 'kart']];
    $this->postJson('/api/mobile/sync', $payload)->assertCreated();
    $second = Workspace::factory()->create();
    $user->workspaces()->attach($second, ['role' => 'owner', 'status' => 'active', 'joined_at' => now()]);
    $this->withHeader('X-Pitmetric-Workspace', (string) $second->getKey())->postJson('/api/mobile/sync', $payload)->assertConflict();
    $user->workspaces()->updateExistingPivot($workspace, ['role' => 'viewer']);
    $this->withHeader('X-Pitmetric-Workspace', (string) $workspace->getKey())->getJson('/api/mobile/me')
        ->assertOk()->assertJsonPath('session.canWrite', false);
    $this->postJson('/api/mobile/sync', ['client_id' => fake()->uuid(), ...array_diff_key($payload, ['client_id' => true])])->assertForbidden();
    $user->workspaces()->updateExistingPivot($workspace, ['status' => 'suspended']);
    $this->getJson('/api/mobile/bootstrap')->assertForbidden();
});

test('PWA validates setup measurements and computes the canonical transmission ratio', function () {
    $user = User::factory()->withDatabaseAccess()->create(['password' => 'password']);
    $this->actingAs($user);
    $kart = mobileOfflinePilotKart($user)['kart'];
    $token = $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password'])->assertOk()->json('token');
    $this->withToken($token);
    $payload = ['client_id' => fake()->uuid(), 'operation' => 'setup.create', 'payload' => [
        'vehicle_id' => $kart->getKey(), 'name' => 'Weekend', 'values' => [
            'camber_front_deg' => -1.5, 'caster_front_deg' => 8.2, 'rear_width_mode' => 'both',
            'track_width_rear_mm' => 1390, 'rear_axle_length_mm' => 1040,
            'pinion_teeth' => 11, 'crown_teeth' => 74, 'gear_ratio' => 99,
        ],
    ]];
    $id = $this->postJson('/api/mobile/sync', $payload)->assertCreated()->json('result.id');
    $setup = TechnicalSetup::query()->findOrFail($id);
    expect($setup->values['gear_ratio'])->toBe(6.727)
        ->and($setup->values['rear_axle_length_mm'])->toBe(1040)
        ->and($setup->values['caster_front_deg'])->toBe(8.2);
    $payload['client_id'] = fake()->uuid();
    $payload['payload']['values']['tyre_pressure_fl'] = 100;
    $this->postJson('/api/mobile/sync', $payload)->assertUnprocessable();
    expect(TechnicalSetup::query()->count())->toBe(1);
});
