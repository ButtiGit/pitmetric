<?php

use App\Models\Update;
use App\Models\User;
use Illuminate\Http\Client\Request as OutgoingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('pitmetric.update_editor_emails', ['editor@example.com']);
    config()->set('services.fastf1.url', 'http://bridge.internal:8080');
    config()->set('services.fastf1.token', 'testing-secret-not-a-real-token');
});

it('limits the F1 dashboard and API to verified editors', function () {
    $member = User::factory()->create(['email' => 'member@example.com']);
    $editor = User::factory()->create(['email' => 'editor@example.com']);

    $this->actingAs($member)->get(route('studio.f1-live.index'))->assertForbidden();
    $this->get(route('studio.f1-live.feed'))->assertForbidden();
    $this->get(route('studio.f1-live.history', [
        'year' => 2026, 'round' => 1, 'session' => 'R', 'drivers' => 'VER',
    ]))->assertForbidden();

    $this->actingAs($editor)->get(route('studio.f1-live.index'))
        ->assertOk()->assertSee('Live Lab');
});

it('proxies live data without exposing the shared secret to the browser', function () {
    Http::fake(['http://bridge.internal:8080/*' => Http::response([
        'live' => true,
        'drivers' => ['1' => ['car' => ['speed' => 312]]],
    ])]);

    $this->actingAs(User::factory()->create(['email' => 'editor@example.com']))
        ->getJson(route('studio.f1-live.feed', ['drivers' => '1']))
        ->assertOk()
        ->assertJsonPath('drivers.1.car.speed', 312)
        ->assertHeader('cache-control', 'no-store, private');

    Http::assertSent(fn (OutgoingRequest $request): bool => $request->url() === 'http://bridge.internal:8080/api/live?drivers=1'
        && $request->hasHeader('X-PitMetric-Token', 'testing-secret-not-a-real-token'));
});

it('returns a safe error when the Python bridge is unconfigured', function () {
    config()->set('services.fastf1.url', '');

    $this->actingAs(User::factory()->create(['email' => 'editor@example.com']))
        ->getJson(route('studio.f1-live.feed'))
        ->assertStatus(503)->assertJsonPath('live', false);
});

it('never deletes an existing post image when a replacement upload fails validation', function () {
    Storage::fake('public');
    Storage::disk('public')->put('updates/old-post.webp', 'original-bytes');

    $post = Update::factory()->create([
        'media_path' => 'updates/old-post.webp',
        'media_url' => null,
        'media_type' => 'image',
    ]);

    $this->actingAs(User::factory()->create(['email' => 'editor@example.com']))
        ->put(route('studio.updates.update', $post), [
            'primary_locale' => 'it',
            'primary_title' => 'Un post con foto',
            'primary_content' => 'Un testo di prova.',
            'status' => 'published',
            'media' => UploadedFile::fake()->create('not-an-image.txt', 12, 'text/plain'),
        ])->assertSessionHasErrors(['media']);

    expect(Storage::disk('public')->exists('updates/old-post.webp'))->toBeTrue();
    expect($post->fresh()->media_path)->toBe('updates/old-post.webp');
});
