<?php

use App\Models\User;
use Illuminate\Http\Client\Request as OutgoingRequest;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('pitmetric.update_editor_emails', ['editor@example.com']);
    config()->set('services.fastf1.url', 'http://bridge.internal:8080');
    config()->set('services.fastf1.token', 'testing-secret-not-a-real-token');
    Http::preventStrayRequests();
});

test('limits the F1 dashboard and API to verified editors', function () {
    $this->get(route('studio.f1-live.index'))->assertRedirect(route('login'));
    $member = User::factory()->create(['email' => 'member@example.com']);
    $editor = User::factory()->create(['email' => 'editor@example.com']);

    $this->actingAs($member)->get(route('studio.f1-live.index'))->assertForbidden();
    $this->get(route('studio.f1-live.feed'))->assertForbidden();
    $this->get(route('studio.f1-live.events', ['year' => 2026]))->assertForbidden();
    $this->get(route('studio.f1-live.history', [
        'year' => 2026, 'round' => 1, 'session' => 'R', 'drivers' => 'VER',
    ]))->assertForbidden();

    $this->actingAs($editor)->get(route('studio.f1-live.index'))
        ->assertOk()->assertSee('Live Lab')->assertDontSee('testing-secret-not-a-real-token');

    $editor->forceFill(['email_verified_at' => null])->save();
    $this->actingAs($editor)
        ->get(route('studio.f1-live.index'))->assertRedirect(route('verification.notice'));
});

test('proxies live data without exposing the shared secret to the browser', function () {
    Http::fake(['http://bridge.internal:8080/*' => Http::response([
        'live' => true,
        'drivers' => ['1' => ['car' => ['speed' => 312]]],
    ])]);

    $this->actingAs(User::factory()->create(['email' => 'editor@example.com']))
        ->getJson(route('studio.f1-live.feed', ['drivers' => '1']))
        ->assertOk()
        ->assertJsonPath('drivers.1.car.speed', 312)
        ->assertDontSee('testing-secret-not-a-real-token')
        ->assertDontSee('bridge.internal')
        ->assertHeader('cache-control', 'no-store, private');

    Http::assertSent(fn (OutgoingRequest $request): bool => $request->url() === 'http://bridge.internal:8080/api/live?drivers=1'
        && $request->hasHeader('X-PitMetric-Token', 'testing-secret-not-a-real-token'));
});

test('validates F1 parameters before contacting Python', function (string $endpoint, array $query, string $field) {
    Http::fake();

    $this->actingAs(User::factory()->create(['email' => 'editor@example.com']))
        ->getJson(route('studio.f1-live.'.$endpoint, $query))
        ->assertUnprocessable()->assertJsonValidationErrors($field);

    Http::assertNothingSent();
})->with([
    ['feed', ['drivers' => '1,2,3,4'], 'drivers'],
    ['feed', ['drivers' => '../admin'], 'drivers'],
    ['events', ['year' => 2017], 'year'],
    ['history', ['year' => 2026, 'round' => 1, 'session' => 'R', 'drivers' => 'VER', 'lap' => 'all'], 'lap'],
    ['history', ['year' => 2026, 'round' => 1, 'session' => 'invalid', 'drivers' => 'VER'], 'session'],
]);

test('proxies calendar and pending history responses', function () {
    Http::fake([
        '*/api/events*' => Http::response(['events' => [['round' => 1, 'name' => 'Australian Grand Prix']]]),
        '*/api/history*' => Http::response(['pending' => true]),
    ]);
    $this->actingAs(User::factory()->create(['email' => 'editor@example.com']))
        ->getJson(route('studio.f1-live.events', ['year' => 2026]))
        ->assertOk()->assertJsonPath('events.0.round', 1);

    $this->getJson(route('studio.f1-live.history', [
        'year' => 2026, 'round' => 1, 'session' => 'R', 'drivers' => '1,VER', 'lap' => 'fastest',
    ]))->assertOk()->assertJsonPath('pending', true);
});

test('limits live polling without blocking the calendar or the main site', function () {
    Http::fake(['*' => Http::response(['live' => false, 'events' => []])]);
    $this->actingAs(User::factory()->create(['email' => 'editor@example.com']));

    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->getJson(route('studio.f1-live.feed'))->assertOk();
    }

    $this->getJson(route('studio.f1-live.feed'))->assertTooManyRequests();
    $this->getJson(route('studio.f1-live.events', ['year' => 2026]))->assertOk();
    $this->get(route('studio.f1-live.index'))->assertOk();
});

test('keeps Python failures away from the rest of the site', function (string $failure) {
    Http::fake(['*' => match ($failure) {
        'connection' => Http::failedConnection(),
        'invalid' => Http::response('<html>not JSON</html>', 200),
        'redirect' => Http::response('', 302, ['Location' => 'https://example.test']),
        default => Http::response(['detail' => 'private upstream error'], 500),
    }]);

    $this->actingAs(User::factory()->create(['email' => 'editor@example.com']))
        ->getJson(route('studio.f1-live.feed'))
        ->assertServiceUnavailable()->assertJsonPath('live', false)
        ->assertHeader('cache-control', 'no-store, private')
        ->assertDontSee('private upstream error')->assertDontSee('testing-secret-not-a-real-token');

    $this->get(route('studio.outreach.index'))->assertOk();
})->with(['connection', 'invalid', 'redirect', 'server']);

test('returns a safe error when the Python bridge is unconfigured', function () {
    config()->set('services.fastf1.url', '');

    $this->actingAs(User::factory()->create(['email' => 'editor@example.com']))
        ->getJson(route('studio.f1-live.feed'))
        ->assertStatus(503)->assertJsonPath('live', false);
});
