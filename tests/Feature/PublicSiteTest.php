<?php

use App\Models\Update;
use Illuminate\Database\Eloquent\Model;

it('shows the public home page in English with the editorial layout', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('History must stay true.')
        ->assertSee('PitMetric is motorsport software')
        ->assertSee('pm-home-editorial-title', false)
        ->assertDontSee('Know every lap.');
});

it('switches the public site to Italian using the locale cookie', function () {
    $this->withCookie('pitmetric_locale', 'it')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Lo storico deve restare vero.')
        ->assertSee('PitMetric è un software motorsport')
        ->assertSee('Scopri PitMetric');
});

it('stores a valid language preference', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'it'])
        ->assertRedirect(route('home'))
        ->assertCookie('pitmetric_locale', 'it');
});

it('rejects an unsupported locale', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'fr'])
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors('locale');
});

it('shows the cookie information page', function () {
    $this->get(route('cookies'))
        ->assertOk()
        ->assertSee('Cookies on PitMetric');
});

it('shows the public about page with Simone profile and safe contacts', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Simone Butticè')
        ->assertSee('Junior Full-Stack Web Developer')
        ->assertSee('simonebuttice05@gmail.com')
        ->assertSee('Cuneo, Piemonte, Italia')
        ->assertSee('mailto:simonebuttice05@gmail.com', false)
        ->assertDontSee('tel:', false)
        ->assertSee('media/simone-buttice-profile-original.jpeg', false)
        ->assertSee('Active development partners use PitMetric 100% free');
});

it('restores the high-resolution public brand media', function () {
    $profile = public_path('media/simone-buttice-profile-original.jpeg');
    $devlog = public_path('media/devlog-001-hq.webp');

    expect(is_file($profile))->toBeTrue()
        ->and(is_file($devlog))->toBeTrue()
        ->and(filesize($profile))->toBeGreaterThan(80_000)
        ->and(filesize($devlog))->toBeGreaterThan(300_000)
        ->and(getimagesize($profile))->toMatchArray([938, 1061])
        ->and(getimagesize($devlog))->toMatchArray([1672, 941]);
});

it('lists published updates', function () {
    $published = Update::factory()->create([
        'title' => 'Published update',
    ]);

    Update::factory()->draft()->create([
        'title' => 'Draft update',
    ]);

    $this->get(route('updates.index'))
        ->assertOk()
        ->assertSee($published->title)
        ->assertDontSee('Draft update');
});

it('keeps legacy update rows readable before the media migration is applied', function () {
    Model::preventAccessingMissingAttributes();

    try {
        $update = new Update;
        $update->setRawAttributes([
            'title' => 'Legacy update',
            'excerpt' => 'Legacy excerpt',
            'content' => 'Legacy content',
        ], true);
        $update->exists = true;

        expect($update->titleForLocale('it'))->toBe('Legacy update')
            ->and($update->excerptForLocale('it'))->toBe('Legacy excerpt')
            ->and($update->contentForLocale('it'))->toBe('Legacy content')
            ->and($update->mediaSource())->toBeNull();
    } finally {
        Model::preventAccessingMissingAttributes(false);
    }
});

it('always uses the high-quality artwork for the first development log', function () {
    $update = Update::factory()->create([
        'slug' => 'pitmetric-sta-prendendo-forma',
        'media_type' => 'image',
        'media_path' => 'updates/legacy-low-resolution.webp',
    ]);

    expect($update->mediaSource())->toBe('/media/devlog-001-hq.webp?v=20260915');
});

it('shows localized update copy when available', function () {
    $update = Update::factory()->create([
        'title' => 'English update',
        'title_it' => 'Aggiornamento italiano',
        'excerpt_it' => 'Riassunto italiano',
        'content_it' => 'Contenuto italiano',
    ]);

    $this->withCookie('pitmetric_locale', 'it')
        ->get(route('updates.show', $update))
        ->assertOk()
        ->assertSee('Aggiornamento italiano')
        ->assertSee('Riassunto italiano')
        ->assertSee('Contenuto italiano');
});

it('shows a published update', function () {
    $update = Update::factory()->create();

    $this->get(route('updates.show', $update))
        ->assertOk()
        ->assertSee($update->title)
        ->assertSee($update->excerpt);
});

it('returns 404 for a draft update', function () {
    $update = Update::factory()->draft()->create();

    $this->get(route('updates.show', $update))->assertNotFound();
});
