<?php

use App\Models\Circuit;
use App\Models\Configuration;
use App\Models\ConfigurationVersion;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function weatherTestSession(User $user): Session
{
    $workspace = $user->workspaces()->firstOrFail();
    $vehicle = Vehicle::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'PN Rent weather kart',
        'category' => 'kart',
    ]);
    $configuration = Configuration::query()->create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Weather kart build',
        'status' => 'active',
    ]);
    $version = ConfigurationVersion::query()->create([
        'configuration_id' => $configuration->id,
        'version_number' => 1,
        'created_by' => $user->id,
    ]);

    return Session::query()->create([
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
        'session_type' => 'practice',
        'started_at' => now(),
        'completed_laps' => 12,
        'status' => 'finalized',
        'finalized_at' => now(),
        'notes' => 'Training on track',
        'created_by' => $user->id,
    ]);
}

function fakeNorwayWeather(int $status = 200): void
{
    Http::preventStrayRequests();
    Http::fake([
        'api.met.no/*' => Http::response([
            'type' => 'Feature',
            'properties' => [
                'timeseries' => [[
                    'time' => '2026-10-28T12:00:00Z',
                    'data' => [
                        'instant' => [
                            'details' => [
                                'air_temperature' => 18.7,
                                'relative_humidity' => 63.4,
                                'wind_speed' => 3.5,
                                'wind_from_direction' => 225,
                                'air_pressure_at_sea_level' => 1014.6,
                                'cloud_area_fraction' => 30,
                            ],
                        ],
                        'next_1_hours' => [
                            'summary' => ['symbol_code' => 'partlycloudy_day'],
                            'details' => ['precipitation_amount' => 0.2],
                        ],
                    ],
                ]],
            ],
        ], $status, [
            'Expires' => now()->addMinutes(30)->toRfc7231String(),
            'Last-Modified' => now()->subMinutes(10)->toRfc7231String(),
        ]),
    ]);
}

it('imports a MET Norway GPS forecast into the existing session without changing recorded usage', function () {
    app()->setLocale('it');
    Cache::flush();
    fakeNorwayWeather();
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $session = weatherTestSession($user);

    $this->post(route('sessions.weather.store', $session), [
        'latitude' => '44.123456',
        'longitude' => '7.987654',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('sessions.index', [
            'weather_session' => $session->id,
            'recorded' => $session->id,
            'page' => 1,
        ]));

    $snapshot = $session->fresh()->weather_snapshot;

    expect($snapshot['provider'])->toBe('met_norway')
        ->and($snapshot['location_source'])->toBe('manual')
        ->and($snapshot['latitude'])->toBe(44.1235)
        ->and($snapshot['longitude'])->toBe(7.9877)
        ->and($snapshot['air_temperature_c'])->toBe(18.7)
        ->and($snapshot['humidity_pct'])->toBe(63)
        ->and($snapshot['wind_speed_kmh'])->toBe(12.6)
        ->and($snapshot['pressure_hpa'])->toBe(1014.6)
        ->and($snapshot['precipitation_next_hour_mm'])->toBe(0.2)
        ->and($snapshot['symbol_code'])->toBe('partlycloudy_day')
        ->and($snapshot['hourly'])->toHaveCount(1)
        ->and($session->fresh()->completed_laps)->toBe(12)
        ->and($session->fresh()->notes)->toBe('Training on track');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'api.met.no/weatherapi/locationforecast/2.0/compact')
        && str_contains($request->url(), 'lat=44.1235')
        && str_contains($request->url(), 'lon=7.9877')
        && $request->hasHeader('User-Agent', 'PitMetric/1.0 (+https://pitmetric.it)'));

    $this->get(route('sessions.index', ['weather_session' => $session->id]))
        ->assertSuccessful()
        ->assertSee('Condizioni in pista')
        ->assertSee('19')
        ->assertSee('12,6 km/h')
        ->assertSee('Prossime ore')
        ->assertSee('MET Norway')
        ->assertSee('Aggiorna da GPS');
});

it('prefers saved circuit coordinates without requesting device GPS and records their origin', function () {
    app()->setLocale('it');
    Cache::flush();
    fakeNorwayWeather();
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $session = weatherTestSession($user);
    $circuit = Circuit::create([
        'name' => 'PN Rent Test Circuit',
        'latitude' => 44.39123456,
        'longitude' => 7.54789123,
    ]);
    $layout = $circuit->layouts()->create(['name' => 'Full', 'length_meters' => 1200]);
    $session->update(['circuit_layout_id' => $layout->id]);

    $this->get(route('sessions.index', ['weather_session' => $session->id]))
        ->assertSuccessful()
        ->assertSee('PN Rent Test Circuit')
        ->assertSee('Rileva meteo del circuito')
        ->assertSee('Usa invece il GPS del dispositivo')
        ->assertSee('name="source" value="circuit"', false);
    Http::assertNothingSent();

    $this->post(route('sessions.weather.store', $session), [
        'source' => 'circuit',
        'latitude' => 10,
        'longitude' => 20,
    ])->assertSessionHasNoErrors();

    $snapshot = $session->fresh()->weather_snapshot;
    expect($snapshot['location_source'])->toBe('circuit')
        ->and($snapshot['circuit_id'])->toBe($circuit->id)
        ->and($snapshot['circuit_name'])->toBe('PN Rent Test Circuit')
        ->and($snapshot['latitude'])->toBe(44.3912)
        ->and($snapshot['longitude'])->toBe(7.5479);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'lat=44.3912')
        && str_contains($request->url(), 'lon=7.5479'));

    $this->get(route('sessions.index', ['weather_session' => $session->id]))
        ->assertSee('Origine posizione:')
        ->assertSee('Circuito · PN Rent Test Circuit');
});

it('uses the event circuit when a session has no direct layout', function () {
    Cache::flush();
    fakeNorwayWeather();
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $session = weatherTestSession($user);
    $circuit = Circuit::create(['name' => 'Event Track', 'latitude' => 45.0201, 'longitude' => 8.2304]);
    $layout = $circuit->layouts()->create(['name' => 'Event layout', 'length_meters' => 1100]);
    $event = RaceEvent::create([
        'name' => 'Track day',
        'circuit_layout_id' => $layout->id,
        'start_date' => now(),
        'end_date' => now(),
        'status' => 'planned',
        'created_by' => $user->id,
    ]);
    $session->update(['event_id' => $event->id]);

    $this->post(route('sessions.weather.store', $session), ['source' => 'circuit'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->weather_snapshot['circuit_id'])->toBe($circuit->id);
    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'lat=45.0201')
        && str_contains($request->url(), 'lon=8.2304'));
});

it('keeps GPS and manual input available when the circuit has no coordinates', function () {
    app()->setLocale('it');
    Cache::flush();
    fakeNorwayWeather();
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $session = weatherTestSession($user);
    $circuit = Circuit::create(['name' => 'Circuit without location']);
    $layout = $circuit->layouts()->create(['name' => 'Short', 'length_meters' => 990]);
    $session->update(['circuit_layout_id' => $layout->id]);

    $this->get(route('sessions.index', ['weather_session' => $session->id]))
        ->assertSuccessful()
        ->assertDontSee('Rileva meteo del circuito')
        ->assertSee('Rileva meteo con GPS')
        ->assertSee('name="source" value="manual"', false);

    $this->post(route('sessions.weather.store', $session), ['source' => 'circuit'])
        ->assertSessionHasErrors('weather');

    expect($session->fresh()->weather_snapshot)->toBeNull();
    Http::assertNothingSent();

    $this->post(route('sessions.weather.store', $session), [
        'source' => 'gps',
        'latitude' => 44.1234,
        'longitude' => 7.5678,
    ])->assertSessionHasNoErrors();
    expect($session->fresh()->weather_snapshot['location_source'])->toBe('gps');
});

it('never reads circuit weather coordinates from another workspace', function () {
    Cache::flush();
    fakeNorwayWeather();
    $owner = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($owner);
    $session = weatherTestSession($owner);

    $otherUser = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($otherUser);
    $foreignCircuit = Circuit::create([
        'name' => 'Private team circuit',
        'latitude' => 48.1234,
        'longitude' => 9.4321,
    ]);
    $foreignLayout = $foreignCircuit->layouts()->create(['name' => 'Private layout', 'length_meters' => 1200]);

    $this->actingAs($owner);
    $session->update(['circuit_layout_id' => $foreignLayout->id]);

    $this->post(route('sessions.weather.store', $session), ['source' => 'circuit'])
        ->assertSessionHasErrors('weather');

    expect($session->fresh()->weather_snapshot)->toBeNull();
    Http::assertNothingSent();
});

it('reuses a fresh forecast from the shared coordinate cache instead of repeating API calls', function () {
    Cache::flush();
    fakeNorwayWeather();
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $session = weatherTestSession($user);

    $payload = ['latitude' => 44.123456, 'longitude' => 7.987654];
    $this->post(route('sessions.weather.store', $session), $payload)->assertSessionHasNoErrors();
    $this->post(route('sessions.weather.store', $session), $payload)->assertSessionHasNoErrors();

    Http::assertSentCount(1);
});

it('validates GPS coordinates before contacting the weather provider', function (array $coordinates) {
    Cache::flush();
    fakeNorwayWeather();
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $session = weatherTestSession($user);

    $this->post(route('sessions.weather.store', $session), $coordinates)
        ->assertSessionHasErrors();

    expect($session->fresh()->weather_snapshot)->toBeNull();
    Http::assertNothingSent();
})->with([
    [['latitude' => 92, 'longitude' => 7.8]],
    [['latitude' => 44.1, 'longitude' => -181]],
    [['latitude' => 'invalid', 'longitude' => 7.8]],
    [['latitude' => 44.1]],
]);

it('does not overwrite stored weather when the external service fails', function () {
    Cache::flush();
    Http::preventStrayRequests();
    Http::fake(['api.met.no/*' => Http::response(['error' => 'Unavailable'], 503)]);
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $session = weatherTestSession($user);
    $session->weather_snapshot = ['provider' => 'met_norway', 'air_temperature_c' => 17.0];
    $session->save();
    $originalWeather = $session->fresh()->weather_snapshot;

    $this->post(route('sessions.weather.store', $session), [
        'latitude' => 43.1,
        'longitude' => 8.1,
    ])->assertSessionHasErrors('weather');

    expect($session->fresh()->weather_snapshot)->toBe($originalWeather);
});

it('blocks GPS weather updates from another workspace and read-only team members', function () {
    Cache::flush();
    fakeNorwayWeather();
    $owner = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($owner);
    $session = weatherTestSession($owner);
    $workspace = $owner->workspaces()->firstOrFail();

    $otherUser = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($otherUser)->post(route('sessions.weather.store', $session), [
        'latitude' => 44.2,
        'longitude' => 7.7,
    ])->assertNotFound();

    $viewer = User::factory()->create();
    $viewer->forceFill(['database_access_enabled' => true])->save();
    DB::table('workspace_user')->insert([
        'workspace_id' => $workspace->id,
        'user_id' => $viewer->id,
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($viewer)->post(route('sessions.weather.store', $session), [
        'latitude' => 44.2,
        'longitude' => 7.7,
    ])->assertForbidden();

    Http::assertNothingSent();
    expect($session->fresh()->weather_snapshot)->toBeNull();
});
