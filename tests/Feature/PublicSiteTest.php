<?php

use App\Models\Update;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

it('shows the localized public home page in English with the editorial layout', function () {
    $this->get(route('localized.home', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('History must stay true.')
        ->assertSee('PitMetric is motorsport software')
        ->assertSee('pm-home-editorial-title', false)
        ->assertDontSee('Know every lap.');
});

it('uses the locale in the URL instead of the locale cookie', function () {
    $this->withCookie('pitmetric_locale', 'it')
        ->get(route('localized.home', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('History must stay true.')
        ->assertDontSee('Lo storico deve restare vero.');

    $this->withCookie('pitmetric_locale', 'en')
        ->get(route('localized.home', ['locale' => 'it']))
        ->assertOk()
        ->assertSee('Lo storico deve restare vero.')
        ->assertSee('PitMetric è un software motorsport')
        ->assertSee('Scopri PitMetric');
});

it('redirects legacy public URLs to their localized versions', function () {
    $this->get(route('home'))
        ->assertStatus(301)
        ->assertRedirect(route('localized.home', ['locale' => 'en']));

    $this->withCookie('pitmetric_locale', 'it')
        ->get(route('about'))
        ->assertStatus(301)
        ->assertRedirect(route('localized.about', ['locale' => 'it']));
});

it('stores a valid language preference and can redirect to the matching localized page', function () {
    $this->from(route('localized.home', ['locale' => 'en']))
        ->post(route('locale.update'), [
            'locale' => 'it',
            'redirect' => '/it',
        ])
        ->assertRedirect('/it')
        ->assertCookie('pitmetric_locale', 'it');
});

it('rejects an unsupported locale', function () {
    $this->from(route('localized.home', ['locale' => 'en']))
        ->post(route('locale.update'), ['locale' => 'fr'])
        ->assertRedirect(route('localized.home', ['locale' => 'en']))
        ->assertSessionHasErrors('locale');
});

it('renders canonical and hreflang metadata for localized pages', function () {
    $response = $this->get(route('localized.home', ['locale' => 'en']))
        ->assertOk();

    $html = $response->getContent();

    expect(preg_match('/<link rel="canonical" href="https?:\/\/[^"]+\/en">/', $html))->toBe(1)
        ->and(preg_match('/hreflang="en" href="https?:\/\/[^"]+\/en"/', $html))->toBe(1)
        ->and(preg_match('/hreflang="it" href="https?:\/\/[^"]+\/it"/', $html))->toBe(1)
        ->and(preg_match('/hreflang="x-default" href="https?:\/\/[^"]+\/en"/', $html))->toBe(1);
});

it('shows the localized cookie information page', function () {
    $this->get(route('localized.cookies', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Cookies on PitMetric');
});

it('shows the localized about page with Simone profile and safe contacts', function () {
    $this->get(route('localized.about', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Simone Butticè')
        ->assertSee('Junior Full-Stack Web Developer')
        ->assertSee('simonebuttice05@gmail.com')
        ->assertSee('Cuneo, Piemonte, Italia')
        ->assertSee('mailto:simonebuttice05@gmail.com', false)
        ->assertDontSee('tel:', false)
        ->assertSee('media/simone-buttice-profile.webp', false)
        ->assertSee('Active development partners use PitMetric 100% free');
});

it('lists published updates on the localized updates index', function () {
    $published = Update::factory()->create([
        'title' => 'Published update',
    ]);

    Update::factory()->draft()->create([
        'title' => 'Draft update',
    ]);

    $this->get(route('localized.updates.index', ['locale' => 'en']))
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
            ->and($update->mediaSource())->toBeNull()
            ->and($update->hasLocaleVersion('it'))->toBeFalse();
    } finally {
        Model::preventAccessingMissingAttributes(false);
    }
});

it('shows localized update copy when a complete Italian version is available', function () {
    $update = Update::factory()->create([
        'title' => 'English update',
        'title_it' => 'Aggiornamento italiano',
        'excerpt_it' => 'Riassunto italiano',
        'content_it' => 'Contenuto italiano',
    ]);

    $this->get(route('localized.updates.show', ['locale' => 'it', 'update' => $update]))
        ->assertOk()
        ->assertSee('Aggiornamento italiano')
        ->assertSee('Riassunto italiano')
        ->assertSee('Contenuto italiano');
});

it('redirects an untranslated Italian update to the English canonical version', function () {
    $update = Update::factory()->create([
        'title_it' => null,
        'excerpt_it' => null,
        'content_it' => null,
    ]);

    $this->get(route('localized.updates.show', ['locale' => 'it', 'update' => $update]))
        ->assertStatus(301)
        ->assertRedirect(route('localized.updates.show', ['locale' => 'en', 'update' => $update]));
});

it('shows a published update with article structured data', function () {
    $update = Update::factory()->create();

    $this->get(route('localized.updates.show', ['locale' => 'en', 'update' => $update]))
        ->assertOk()
        ->assertSee($update->title)
        ->assertSee($update->excerpt)
        ->assertSee('"@type":"Article"', false);
});

it('uses an absolute social image URL for bundled update artwork', function () {
    Storage::fake('public');

    $update = Update::factory()->create([
        'slug' => 'pitmetric-sta-prendendo-forma',
        'media_type' => 'image',
        'media_path' => 'updates/missing.webp',
        'media_url' => null,
    ]);

    $this->get(route('localized.updates.show', ['locale' => 'en', 'update' => $update]))
        ->assertOk()
        ->assertSee('property="og:image" content="'.url('/media/devlog-001.webp').'"', false);
});

it('returns 404 for a draft localized update', function () {
    $update = Update::factory()->draft()->create();

    $this->get(route('localized.updates.show', ['locale' => 'en', 'update' => $update]))
        ->assertNotFound();
});

it('publishes a localized sitemap and omits nonexistent update translations', function () {
    $translated = Update::factory()->create([
        'title_it' => 'Aggiornamento tradotto',
        'excerpt_it' => 'Riassunto tradotto',
        'content_it' => 'Contenuto tradotto',
    ]);

    $englishOnly = Update::factory()->create([
        'title_it' => null,
        'excerpt_it' => null,
        'content_it' => null,
    ]);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee(route('localized.home', ['locale' => 'en']), false)
        ->assertSee(route('localized.home', ['locale' => 'it']), false)
        ->assertSee(route('localized.updates.show', ['locale' => 'it', 'update' => $translated]), false)
        ->assertSee(route('localized.updates.show', ['locale' => 'en', 'update' => $englishOnly]), false)
        ->assertDontSee(route('localized.updates.show', ['locale' => 'it', 'update' => $englishOnly]), false);
});
