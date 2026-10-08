<?php

use App\Models\Update;
use Illuminate\Database\Eloquent\Model;

it('shows the localized public home page in English with SEO metadata', function () {
    $englishHome = route('localized.home', ['locale' => 'en'], false);
    $italianHome = route('localized.home', ['locale' => 'it'], false);

    $response = $this->get($englishHome)
        ->assertOk()
        ->assertSee('History must stay true.')
        ->assertSee('PitMetric is motorsport software')
        ->assertSee('pm-home-editorial-title', false)
        ->assertDontSee('Know every lap.')
        ->assertSee('<html lang="en"', false);

    $html = $response->getContent();

    preg_match('/<link rel="canonical" href="([^"]+)">/', $html, $canonical);
    preg_match('/<link rel="alternate" hreflang="en" href="([^"]+)">/', $html, $englishAlternate);
    preg_match('/<link rel="alternate" hreflang="it" href="([^"]+)">/', $html, $italianAlternate);

    expect(parse_url($canonical[1] ?? '', PHP_URL_PATH))->toBe($englishHome)
        ->and(parse_url($englishAlternate[1] ?? '', PHP_URL_PATH))->toBe($englishHome)
        ->and(parse_url($italianAlternate[1] ?? '', PHP_URL_PATH))->toBe($italianHome);
});

it('shows the localized public site in Italian', function () {
    $this->get(route('localized.home', ['locale' => 'it']))
        ->assertOk()
        ->assertSee('Lo storico deve restare vero.')
        ->assertSee('PitMetric è un software motorsport')
        ->assertSee('Scopri PitMetric')
        ->assertSee('<html lang="it"', false);
});

it('permanently redirects legacy public urls to the preferred localized version', function () {
    $this->get(route('home'))
        ->assertStatus(301)
        ->assertRedirect(route('localized.home', ['locale' => 'en']));

    $this->withCookie('pitmetric_locale', 'it')
        ->get(route('about'))
        ->assertStatus(301)
        ->assertRedirect(route('localized.about', ['locale' => 'it']));
});

it('stores a valid language preference', function () {
    $source = route('localized.home', ['locale' => 'en']);

    $this->from($source)
        ->post(route('locale.update'), ['locale' => 'it'])
        ->assertRedirect($source)
        ->assertCookie('pitmetric_locale', 'it');
});

it('rejects an unsupported locale', function () {
    $source = route('localized.home', ['locale' => 'en']);

    $this->from($source)
        ->post(route('locale.update'), ['locale' => 'fr'])
        ->assertRedirect($source)
        ->assertSessionHasErrors('locale');
});

it('does not follow unsafe locale redirect targets', function () {
    $source = route('localized.home', ['locale' => 'en']);

    $this->from($source)
        ->post(route('locale.update'), [
            'locale' => 'it',
            'redirect' => '/%2F%2Fevil.example',
        ])
        ->assertRedirect($source)
        ->assertCookie('pitmetric_locale', 'it');
});

it('shows the localized cookie information page', function () {
    $this->get(route('localized.cookies', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Cookies on PitMetric');
});

it('shows the localized public about page with Simone profile and safe contacts', function () {
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

it('lists published updates on the localized updates page', function () {
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
            ->and($update->mediaSource())->toBeNull();
    } finally {
        Model::preventAccessingMissingAttributes(false);
    }
});

it('shows localized update copy and alternates when the Italian translation is complete', function () {
    $update = Update::factory()->create([
        'title' => 'English update',
        'excerpt' => 'English excerpt',
        'content' => 'English content',
        'title_it' => 'Aggiornamento italiano',
        'excerpt_it' => 'Riassunto italiano',
        'content_it' => 'Contenuto italiano',
    ]);

    $englishUrl = route('localized.updates.show', ['locale' => 'en', 'update' => $update]);
    $italianUrl = route('localized.updates.show', ['locale' => 'it', 'update' => $update]);

    $this->get($italianUrl)
        ->assertOk()
        ->assertSee('Aggiornamento italiano')
        ->assertSee('Riassunto italiano')
        ->assertSee('Contenuto italiano')
        ->assertSee('<html lang="it"', false)
        ->assertSee('<link rel="canonical" href="'.$italianUrl.'">', false)
        ->assertSee('hreflang="en" href="'.$englishUrl.'"', false)
        ->assertSee('hreflang="it" href="'.$italianUrl.'"', false);
});

it('canonicalizes an untranslated Italian update to English without an Italian alternate', function () {
    $update = Update::factory()->create([
        'title' => 'English only update',
        'excerpt' => 'English only excerpt',
        'content' => 'English only content',
        'title_it' => null,
        'excerpt_it' => null,
        'content_it' => null,
    ]);

    $englishUrl = route('localized.updates.show', ['locale' => 'en', 'update' => $update]);
    $italianUrl = route('localized.updates.show', ['locale' => 'it', 'update' => $update]);

    $this->get($italianUrl)
        ->assertOk()
        ->assertSee('English only update')
        ->assertSee('English only excerpt')
        ->assertSee('English only content')
        ->assertSee('<html lang="en"', false)
        ->assertSee('<link rel="canonical" href="'.$englishUrl.'">', false)
        ->assertSee('hreflang="en" href="'.$englishUrl.'"', false)
        ->assertDontSee('hreflang="it"', false);
});

it('shows a published update on its localized English url', function () {
    $update = Update::factory()->create();

    $this->get(route('localized.updates.show', ['locale' => 'en', 'update' => $update]))
        ->assertOk()
        ->assertSee($update->title)
        ->assertSee($update->excerpt);
});

it('returns 404 for a draft update on a localized url', function () {
    $update = Update::factory()->draft()->create();

    $this->get(route('localized.updates.show', ['locale' => 'en', 'update' => $update]))
        ->assertNotFound();
});

it('publishes only indexable localized pages in the sitemap', function () {
    $translated = Update::factory()->create([
        'title' => 'Translated update',
        'excerpt' => 'English excerpt',
        'content' => 'English content',
        'title_it' => 'Aggiornamento tradotto',
        'excerpt_it' => 'Riassunto italiano',
        'content_it' => 'Contenuto italiano',
    ]);

    $englishOnly = Update::factory()->create([
        'title' => 'English sitemap update',
        'excerpt' => 'English excerpt',
        'content' => 'English content',
        'title_it' => null,
        'excerpt_it' => null,
        'content_it' => null,
    ]);

    $translatedItalianUrl = route('localized.updates.show', ['locale' => 'it', 'update' => $translated]);
    $englishOnlyEnglishUrl = route('localized.updates.show', ['locale' => 'en', 'update' => $englishOnly]);
    $englishOnlyItalianUrl = route('localized.updates.show', ['locale' => 'it', 'update' => $englishOnly]);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee($translatedItalianUrl, false)
        ->assertSee($englishOnlyEnglishUrl, false)
        ->assertDontSee($englishOnlyItalianUrl, false)
        ->assertDontSee(route('demo.public'), false)
        ->assertDontSee(route('game-dev'), false);
});
