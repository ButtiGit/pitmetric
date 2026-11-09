<?php

use App\Http\Middleware\RecordStudioSiteTraffic;
use App\Models\StudioSiteCheckRun;
use App\Models\StudioSiteMetric;
use App\Models\User;
use App\Services\StudioSiteTraffic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    config()->set('pitmetric.update_editor_emails', ['editor@pitmetric.test']);
    Cache::forget('studio-site-android-release-downloads');
    Http::preventStrayRequests();
    Http::fake([
        'https://api.github.com/repos/ButtiGit/pitmetric/releases/tags/android-latest' => Http::response([
            'assets' => [['name' => 'PitMetric.apk', 'download_count' => 19]],
        ]),
        'https://api.github.com/repos/ButtiGit/pitmetric/actions/workflows/tests.yml/runs*' => Http::response(['workflow_runs' => [
            ['status' => 'completed', 'conclusion' => 'success', 'run_number' => 242],
        ]]),
    ]);
});

test('site management and manual diagnostic runs are restricted to Studio editors', function () {
    $ordinary = User::factory()->create(['database_access_enabled' => true]);
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test', 'database_access_enabled' => false]);

    $this->get(route('studio.site.index'))->assertRedirect(route('login'));
    $this->actingAs($ordinary)->get(route('studio.site.index'))->assertForbidden();
    $this->post(route('studio.site.checks'))->assertForbidden();

    $this->actingAs($editor)->get(route('studio.site.index'))->assertOk()->assertSee('Gestione sito');
    $this->post(route('studio.site.checks'))->assertRedirect(route('studio.site.index'));

    $run = StudioSiteCheckRun::query()->sole();
    expect($run->user_id)->toBe($editor->id)
        ->and(count($run->results))->toBeGreaterThan(10)
        ->and(collect($run->results)->pluck('key'))->toContain('database', 'ci', 'pwa', 'calendar');

    $this->get(route('studio.site.index'))->assertOk()->assertSee('Suite automatica CI #242')->assertSee('19');
});

test('public page views are recorded in daily aggregates without visitor identifiers', function () {
    $this->get(route('localized.app', 'it'))->assertOk();
    $this->get(route('localized.app', 'it'))->assertOk();
    $this->withHeader('User-Agent', 'Googlebot')->get(route('localized.app', 'en'))->assertOk();

    expect(StudioSiteMetric::query()->where('kind', 'page_view')->count())->toBe(1);
    $metric = StudioSiteMetric::query()->where('kind', 'page_view')->sole();
    expect($metric->subject)->toBe('/it/app')
        ->and($metric->value)->toBe(2)
        ->and($metric->getAttributes())->not->toHaveKey('ip_address');
});

test('download buttons redirect correctly and track clicks not actual installations', function () {
    $this->get(route('app.download.android'))
        ->assertRedirect('https://github.com/ButtiGit/pitmetric/releases/download/android-latest/PitMetric.apk');

    $metric = StudioSiteMetric::query()->where('kind', 'apk_download')->sole();
    expect($metric->subject)->toBe('android')->and($metric->value)->toBe(1);

    if (is_file(public_path('mobile/index.html')) && is_file(public_path('mobile/sw.js'))) {
        $this->get(route('app.open.pwa'))->assertRedirect('/mobile/');
        expect(StudioSiteMetric::query()->where('kind', 'pwa_open')->sole()->value)->toBe(1);
    } else {
        $this->get(route('app.open.pwa'))->assertNotFound();
        expect(StudioSiteMetric::query()->where('kind', 'pwa_open')->count())->toBe(0);
    }
});

test('server errors are aggregated by route without exposing technical details', function () {
    $request = Request::create('/unavailable', 'GET');
    app(RecordStudioSiteTraffic::class)->terminate($request, new Response('unavailable', 503));

    $metric = StudioSiteMetric::query()->where('kind', 'http_error')->sole();
    expect($metric->subject)->toBe('unknown')->and($metric->value)->toBe(1);
});

test('monitoring tracking keeps a broken metrics store from blocking ordinary requests', function () {
    $traffic = app(StudioSiteTraffic::class);
    $traffic->record('apk_download', 'android');
    $traffic->record('apk_download', 'android');

    expect(StudioSiteMetric::query()->where('kind', 'apk_download')->sole()->value)->toBe(2);
});
