<?php

use App\Models\GalleryPhoto;
use App\Models\TrackCapture;
use App\Models\User;
use App\Services\MobileTokenService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function mobileTokenFor(User $user): string
{
    return app(MobileTokenService::class)->issue($user, 'Pest phone');
}

test('mobile login reports local only mode when database access is disabled', function () {
    $user = User::factory()->create([
        'email' => 'local-only@pitmetric.test',
        'email_verified_at' => now(),
    ]);

    $this->postJson('/api/mobile/v1/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('user.cloud_enabled', false)
        ->assertJsonPath('user.cloud_reason', 'database_access_required');
});

test('mobile sync keeps local data queued when cloud database access is disabled', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $token = mobileTokenFor($user);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/mobile/v1/sync', [
            'operations' => [[
                'id' => fake()->uuid(),
                'type' => 'capture',
                'payload' => [
                    'kind' => 'note',
                    'notes' => 'Keep this note on the phone.',
                    'occurred_at' => now()->toIso8601String(),
                ],
            ]],
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'database_access_required');
});

test('mobile sync is idempotent for cloud enabled users', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $token = mobileTokenFor($user);
    $uuid = fake()->uuid();
    $payload = [
        'operations' => [[
            'id' => $uuid,
            'type' => 'capture',
            'payload' => [
                'kind' => 'note',
                'notes' => 'Front grip improved after pressure change.',
                'occurred_at' => now()->toIso8601String(),
                'context' => ['driver_name' => 'Test Driver'],
            ],
        ]],
    ];

    $headers = ['Authorization' => 'Bearer '.$token];
    $this->postJson('/api/mobile/v1/sync', $payload, $headers)
        ->assertOk()
        ->assertJsonPath('synced.0.duplicate', false);

    $this->postJson('/api/mobile/v1/sync', $payload, $headers)
        ->assertOk()
        ->assertJsonPath('synced.0.duplicate', true);

    expect(TrackCapture::query()->where('notes', 'Front grip improved after pressure change.')->count())->toBe(1);
});

test('mobile gallery accepts a titled photo for cloud enabled users', function () {
    Storage::fake('local');
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $token = mobileTokenFor($user);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->post('/api/mobile/v1/gallery', [
            'client_uuid' => fake()->uuid(),
            'title' => 'Front tyre wear',
            'description' => 'After the second stint.',
            'photo' => UploadedFile::fake()->image('tyre.jpg', 1200, 900),
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('photo.title', 'Front tyre wear');

    expect(GalleryPhoto::query()->count())->toBe(1);
});

test('mobile gallery isolates photos between workspaces', function () {
    Storage::fake('local');
    $owner = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $other = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $ownerToken = mobileTokenFor($owner);
    $otherToken = mobileTokenFor($other);

    $upload = $this->withHeader('Authorization', 'Bearer '.$ownerToken)
        ->post('/api/mobile/v1/gallery', [
            'client_uuid' => fake()->uuid(),
            'title' => 'Private tyre photo',
            'photo' => UploadedFile::fake()->image('private.jpg', 600, 600),
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    $photoId = (int) $upload->json('photo.id');

    $this->withHeader('Authorization', 'Bearer '.$otherToken)
        ->getJson('/api/mobile/v1/gallery/'.$photoId)
        ->assertNotFound();
});
