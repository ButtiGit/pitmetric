<?php

use App\Models\Update;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('pitmetric.update_editor_emails', ['editor@example.com']);
});

it('blocks non editors from the update studio', function () {
    $user = User::factory()->create([
        'email' => 'member@example.com',
    ]);

    $this->actingAs($user)
        ->get(route('studio.updates.index'))
        ->assertForbidden();
});

it('lets an editor publish a text only update', function () {
    $editor = User::factory()->create([
        'email' => 'editor@example.com',
    ]);

    $this->actingAs($editor)
        ->post(route('studio.updates.store'), [
            'title' => 'A new telemetry workflow',
            'title_it' => 'Un nuovo flusso telemetria',
            'content' => "We rebuilt the session flow.\nIt now keeps the technical history intact.",
            'content_it' => "Abbiamo rifatto il flusso sessione.\nOra mantiene intatto lo storico tecnico.",
            'status' => 'published',
        ])
        ->assertRedirect(route('studio.updates.index'));

    $update = Update::query()->firstOrFail();

    expect($update->status)->toBe('published')
        ->and($update->media_type)->toBeNull()
        ->and($update->slug)->toBe('a-new-telemetry-workflow');

    $this->get(route('localized.updates.show', ['locale' => 'it', 'update' => $update]))
        ->assertOk()
        ->assertSee('Un nuovo flusso telemetria')
        ->assertSee('Abbiamo rifatto il flusso sessione.');
});

it('lets editors change a published post date without changing its content or status', function () {
    $editor = User::factory()->create(['email' => 'editor@example.com']);
    $update = Update::factory()->create([
        'title' => 'Trackside improvements',
        'content' => 'The new offline workflow is ready.',
        'status' => 'published',
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($editor)->get(route('studio.updates.edit', $update))
        ->assertOk()
        ->assertSee('name="published_at"', false);

    $this->put(route('studio.updates.update', $update), [
        'primary_locale' => 'en',
        'primary_title' => 'Trackside improvements',
        'primary_content' => 'The new offline workflow is ready.',
        'status' => 'published',
        'published_at' => '2026-09-15T10:30',
    ])->assertRedirect(route('studio.updates.index'));

    $update->refresh();
    expect($update->published_at?->copy()->timezone('Europe/Rome')->format('Y-m-d H:i'))->toBe('2026-09-15 10:30')
        ->and($update->status)->toBe('published')
        ->and($update->content)->toBe('The new offline workflow is ready.');

    $this->get(route('studio.updates.index'))->assertOk()->assertSee('15/09/2026');
    $this->get(route('studio.updates.edit', $update))->assertOk()->assertSee('value="2026-09-15T10:30"', false);
    $this->get(route('localized.updates.show', ['locale' => 'it', 'update' => $update]))
        ->assertOk()->assertSee('datetime="2026-09-15"', false);

    $this->put(route('studio.updates.update', $update), [
        'primary_locale' => 'en',
        'primary_title' => 'Trackside improvements',
        'primary_content' => 'The new offline workflow is ready.',
        'status' => 'published',
    ])->assertRedirect(route('studio.updates.index'));

    expect($update->fresh()?->published_at?->copy()->timezone('Europe/Rome')->format('Y-m-d H:i'))->toBe('2026-09-15 10:30');
});

it('rejects invalid or future publication dates without changing the post', function () {
    $editor = User::factory()->create(['email' => 'editor@example.com']);
    $update = Update::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
    $originalDate = $update->published_at?->toDateTimeString();

    foreach (['not-a-date', now('Europe/Rome')->addYear()->format('Y-m-d\TH:i')] as $invalidDate) {
        $this->actingAs($editor)->put(route('studio.updates.update', $update), [
            'primary_locale' => 'en',
            'primary_title' => $update->title,
            'primary_content' => $update->content,
            'status' => 'published',
            'published_at' => $invalidDate,
        ])->assertSessionHasErrors('published_at');
    }

    expect($update->fresh()?->published_at?->toDateTimeString())->toBe($originalDate);
});

it('lets an editor publish a media only update from an external url', function () {
    $editor = User::factory()->create([
        'email' => 'editor@example.com',
    ]);

    $this->actingAs($editor)
        ->post(route('studio.updates.store'), [
            'title' => 'First garage preview',
            'media_url' => 'https://example.com/garage-preview.webp',
            'media_type' => 'image',
            'media_alt' => 'PitMetric garage preview',
            'status' => 'published',
        ])
        ->assertRedirect(route('studio.updates.index'));

    $update = Update::query()->firstOrFail();

    expect($update->media_type)->toBe('image')
        ->and($update->content)->toBe('')
        ->and($update->excerpt)->toBe('First garage preview');

    $this->get(route('localized.updates.show', ['locale' => 'en', 'update' => $update]))
        ->assertOk()
        ->assertSee('https://example.com/garage-preview.webp', false);
});

it('serves uploaded update media without relying on the public storage symlink', function () {
    Storage::fake('public');
    Storage::disk('public')->put('updates/devlog.webp', 'pitmetric-media');

    $update = Update::factory()->create([
        'slug' => 'stored-media-test',
        'media_type' => 'image',
        'media_path' => 'updates/devlog.webp',
        'media_url' => null,
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ]);

    expect($update->mediaSource())
        ->toContain(route('updates.media', $update));

    $this->get(route('updates.media', $update))
        ->assertOk()
        ->assertHeader('cache-control', 'immutable, max-age=31536000, public');
});

it('falls back to valid bundled artwork for the first devlog when its uploaded file is missing', function () {
    Storage::fake('public');

    $update = Update::factory()->create([
        'slug' => 'pitmetric-sta-prendendo-forma',
        'media_type' => 'image',
        'media_path' => 'updates/missing.webp',
        'media_url' => null,
    ]);

    expect($update->mediaSource())->toBe('/media/devlog-001.webp');

    $artwork = file_get_contents(public_path('media/devlog-001.webp'));

    expect($artwork)->not->toBeFalse()
        ->and(substr((string) $artwork, 0, 4))->toBe('RIFF')
        ->and(substr((string) $artwork, 8, 4))->toBe('WEBP');
});
