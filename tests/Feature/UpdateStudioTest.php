<?php

use App\Models\Update;
use App\Models\User;

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

    $this->withCookie('pitmetric_locale', 'it')
        ->get(route('updates.show', $update))
        ->assertOk()
        ->assertSee('Un nuovo flusso telemetria')
        ->assertSee('Abbiamo rifatto il flusso sessione.');
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

    $this->get(route('updates.show', $update))
        ->assertOk()
        ->assertSee('https://example.com/garage-preview.webp', false);
});
