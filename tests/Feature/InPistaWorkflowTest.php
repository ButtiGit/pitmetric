<?php

use App\Models\AuditLog;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\TimingLap;
use App\Models\TrackCapture;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use App\Services\InPistaSessionService;
use Illuminate\Support\Facades\Http;

function setUpInPistaKart(User $operator): array
{
    $kart = Vehicle::factory()->create([
        'workspace_id' => $operator->workspaces()->firstOrFail()->getKey(),
        'name' => 'PN Rent Kart 05',
        'category' => 'kart',
    ]);
    $type = ComponentType::create(['name' => 'PN Rent engine']);
    $component = Component::create([
        'component_type_id' => $type->getKey(),
        'name' => 'PN Rent engine 05',
        'status' => 'active',
    ]);
    ComponentInstallation::create([
        'vehicle_id' => $kart->getKey(),
        'component_id' => $component->getKey(),
        'created_by' => $operator->getKey(),
        'installed_at' => now(),
    ]);
    $configuration = Configuration::create(['vehicle_id' => $kart->getKey(), 'name' => 'Race PN', 'status' => 'active']);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $operator, [$component->getKey()]);
    $setup = TechnicalSetup::create([
        'vehicle_id' => $kart->getKey(),
        'name' => 'Dry baseline',
        'status' => 'active',
        'values' => ['camber_front_deg' => -1.0, 'pinion_teeth' => 11, 'crown_teeth' => 74],
        'created_by' => $operator->getKey(),
    ]);
    $driver = Driver::create(['display_name' => 'PN Driver', 'status' => 'active']);
    $event = RaceEvent::create([
        'name' => 'PN Rent Sunday', 'start_date' => today(), 'end_date' => today(),
        'status' => 'planned', 'created_by' => $operator->getKey(),
    ]);
    $entry = EventEntry::create([
        'event_id' => $event->getKey(), 'driver_id' => $driver->getKey(),
        'vehicle_id' => $kart->getKey(), 'configuration_version_id' => $version->getKey(),
    ]);

    return compact('kart', 'version', 'setup', 'driver', 'event', 'entry');
}

test('the one-screen pit view auto-selects the kart, driver and active event', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $kart = setUpInPistaKart($user)['kart'];

    $this->get(route('pit-mode.index'))
        ->assertOk()
        ->assertSee('In pista')
        ->assertSee('PN Rent Kart 05')
        ->assertSee('PN Driver')
        ->assertSee('PN Rent Sunday')
        ->assertSee('Pronti per la prossima uscita?');

    $this->post(route('pit-mode.sessions.start'), ['kart_id' => $kart->getKey(), 'session_type' => 'practice'])
        ->assertSessionHasNoErrors();

    $this->get(route('pit-mode.index', ['kart' => $kart->getKey()]))
        ->assertOk()
        ->assertSee('Prima della sessione')
        ->assertSee('Dopo la sessione');
});

test('the mechanic records a session without leaving the pit screen or changing its original setup snapshot', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    ['kart' => $kart, 'entry' => $entry, 'version' => $version] = setUpInPistaKart($user);

    $this->post(route('pit-mode.sessions.start'), ['kart_id' => $kart->getKey(), 'session_type' => 'practice'])
        ->assertSessionHasNoErrors()->assertRedirect(route('pit-mode.index', ['kart' => $kart->getKey()]));
    $this->post(route('pit-mode.sessions.start'), ['kart_id' => $kart->getKey(), 'session_type' => 'practice'])
        ->assertSessionHasNoErrors();

    $session = Session::query()->firstOrFail();
    expect(Session::query()->count())->toBe(1)
        ->and($session->status)->toBe('draft')
        ->and($session->event_entry_id)->toBe($entry->getKey())
        ->and($session->configuration_version_id)->toBe($version->getKey())
        ->and($session->setupSnapshot?->name)->toBe('Dry baseline');

    $this->postJson(route('pit-mode.sessions.laps.store', $session), ['lap_time' => '0:52.345'])
        ->assertSuccessful()->assertJsonPath('entry.summary', '0:52.345');
    $this->postJson(route('pit-mode.sessions.laps.store', $session), ['lap_time' => '52,123'])
        ->assertSuccessful();

    $this->postJson(route('sessions.tyre-readings.store', $session), [
        'phase' => 'cold', 'pressure_fl' => 0.79, 'pressure_fr' => 0.80,
    ])->assertSuccessful()->assertJsonPath('entry.kind', 'Pressioni fredde');

    $this->postJson(route('pit-mode.sessions.adjustments.store', $session), [
        'camber_front_deg' => -1.7, 'caster_front_deg' => 8.5,
        'pinion_teeth' => 12, 'crown_teeth' => 73,
    ])->assertSuccessful();
    $this->postJson(route('pit-mode.sessions.notes.store', $session), [
        'kind' => 'issue', 'note' => 'Rear axle vibration', 'severity' => 'warning',
    ])->assertSuccessful();

    expect(TimingLap::query()->where('session_id', $session->getKey())->pluck('lap_number')->all())->toBe([1, 2])
        ->and(TrackCapture::query()->where('session_id', $session->getKey())->count())->toBe(2)
        ->and(TrackCapture::query()->where('kind', 'setup_adjustment')->firstOrFail()->payload['values']['final_drive'])->toBe('12/73')
        ->and($session->setupSnapshot->fresh()->values['camber_front_deg'])->toEqual(-1.0);

    $this->get(route('pit-mode.index', ['kart' => $kart->getKey()]))
        ->assertOk()->assertSee('Sessione in corso')->assertSee('Rear axle vibration');

    $this->post(route('pit-mode.sessions.finish', $session))->assertSessionHasNoErrors()->assertRedirect();
    expect($session->fresh()->status)->toBe('finalized')
        ->and($session->fresh()->completed_laps)->toBe(2);

    $this->postJson(route('pit-mode.sessions.laps.store', $session), ['lap_time' => '51.123'])
        ->assertUnprocessable();
    expect(TimingLap::query()->where('session_id', $session->getKey())->count())->toBe(2);
});

test('manual lap corrections preserve both original values and operator history even after session closure', function () {
    $operator = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($operator);
    $kart = setUpInPistaKart($operator)['kart'];

    $this->post(route('pit-mode.sessions.start'), ['kart_id' => $kart->getKey(), 'session_type' => 'practice'])
        ->assertSessionHasNoErrors();
    $session = Session::query()->firstOrFail();
    $this->postJson(route('pit-mode.sessions.laps.store', $session), ['lap_time' => '0:49.000'])
        ->assertSuccessful()->assertJsonPath('lap.time', '0:49.000');
    $this->postJson(route('pit-mode.sessions.laps.store', $session), ['lap_time' => '0:52.000'])
        ->assertSuccessful();

    $firstLap = TimingLap::query()->where('session_id', $session->getKey())->orderBy('lap_number')->firstOrFail();
    $route = route('pit-mode.sessions.laps.update', [$session, $firstLap]);

    $this->patchJson($route, [
        'lap_time' => '0:53.100', 'expected_lap_time_ms' => 49000, 'reason' => 'Errore cronometro',
    ])->assertSuccessful()
        ->assertJsonPath('correction.previous_time', '0:49.000')
        ->assertJsonPath('correction.corrected_time', '0:53.100')
        ->assertJsonPath('correction.best_time', '0:52.000');

    expect($firstLap->fresh()->lap_time_ms)->toBe(53100);

    $history = AuditLog::query()->where('workspace_id', $session->workspace_id)
        ->where('action', 'lap_time_corrected')->where('subject_type', TimingLap::class)
        ->where('subject_id', $firstLap->getKey())->sole();
    expect($history->user_id)->toBe($operator->getKey())
        ->and($history->before_values['lap_time_ms'])->toBe(49000)
        ->and($history->after_values['lap_time_ms'])->toBe(53100)
        ->and($history->after_values['reason'])->toBe('Errore cronometro');

    $this->get(route('pit-mode.index', ['kart' => $kart->getKey()]))
        ->assertOk()->assertSee('Storico correzioni (1)')->assertSee('Errore cronometro')
        ->assertSee('0:49.000')->assertSee('0:53.100');

    $this->post(route('pit-mode.sessions.finish', $session))->assertSessionHasNoErrors();
    $this->patchJson($route, ['lap_time' => '0:51.200', 'expected_lap_time_ms' => 53100])
        ->assertSuccessful()->assertJsonPath('correction.best_time', '0:51.200');

    expect($session->fresh()->status)->toBe('finalized')
        ->and($firstLap->fresh()->lap_time_ms)->toBe(51200)
        ->and(AuditLog::query()->where('action', 'lap_time_corrected')->where('subject_id', $firstLap->getKey())->count())->toBe(2);

    $this->get(route('pit-mode.index', ['kart' => $kart->getKey()]))
        ->assertOk()->assertSee('Storico correzioni (2)')->assertSee('0:51.200');
});

test('lap correction rejects imported times, conflicting values, cross-session ids and other workspaces', function () {
    $operator = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($operator);
    $kart = setUpInPistaKart($operator)['kart'];
    $this->post(route('pit-mode.sessions.start'), ['kart_id' => $kart->getKey(), 'session_type' => 'test']);
    $session = Session::query()->firstOrFail();
    $this->postJson(route('pit-mode.sessions.laps.store', $session), ['lap_time' => '52.123'])->assertSuccessful();
    $manual = TimingLap::query()->where('session_id', $session->getKey())->sole();
    $route = route('pit-mode.sessions.laps.update', [$session, $manual]);

    $this->patchJson($route, ['lap_time' => '52.200', 'expected_lap_time_ms' => 52000])->assertUnprocessable();
    $this->patchJson($route, ['lap_time' => '52.123', 'expected_lap_time_ms' => 52123])->assertUnprocessable();
    $this->patchJson($route, ['lap_time' => 'broken', 'expected_lap_time_ms' => 52123])->assertUnprocessable();

    $imported = TimingLap::query()->create([
        'session_id' => $session->getKey(), 'vehicle_id' => $kart->getKey(),
        'lap_number' => 2, 'lap_time_ms' => 51000, 'source' => 'import', 'is_valid' => true,
    ]);
    $this->patchJson(route('pit-mode.sessions.laps.update', [$session, $imported]), [
        'lap_time' => '50.000', 'expected_lap_time_ms' => 51000,
    ])->assertUnprocessable();

    $otherSession = Session::query()->create([
        'vehicle_id' => $kart->getKey(), 'configuration_version_id' => $session->configuration_version_id,
        'session_type' => 'test', 'started_at' => now(), 'status' => 'draft', 'created_by' => $operator->getKey(),
    ]);
    $this->patchJson(route('pit-mode.sessions.laps.update', [$otherSession, $manual]), [
        'lap_time' => '50.000', 'expected_lap_time_ms' => 52123,
    ])->assertNotFound();

    $outsider = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($outsider);
    $this->patchJson($route, ['lap_time' => '50.000', 'expected_lap_time_ms' => 52123])->assertNotFound();

    expect($manual->fresh()->lap_time_ms)->toBe(52123)
        ->and($imported->fresh()->lap_time_ms)->toBe(51000)
        ->and(AuditLog::query()->where('action', 'lap_time_corrected')->count())->toBe(0);
});

test('the pit screen suggests the finalized kart setup including its last recorded adjustments', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    ['kart' => $kart, 'setup' => $setup] = setUpInPistaKart($user);

    expect(app(InPistaSessionService::class)->latestUsedSettings($kart))->toBeNull();

    $this->post(route('pit-mode.sessions.start'), ['kart_id' => $kart->getKey(), 'session_type' => 'practice'])
        ->assertSessionHasNoErrors();
    $session = Session::query()->firstOrFail();

    $this->postJson(route('pit-mode.sessions.adjustments.store', $session), [
        'camber_front_deg' => -1.7, 'caster_front_deg' => 9.5,
        'pinion_teeth' => 12, 'crown_teeth' => 73,
    ])->assertSuccessful();
    $this->postJson(route('pit-mode.sessions.adjustments.store', $session), [
        'camber_front_deg' => -1.3,
    ])->assertSuccessful();

    expect(app(InPistaSessionService::class)->latestUsedSettings($kart))->toBeNull();

    $this->post(route('pit-mode.sessions.finish', $session))->assertSessionHasNoErrors();

    $recommended = app(InPistaSessionService::class)->latestUsedSettings($kart);
    expect($recommended['session_id'])->toBe($session->getKey())
        ->and($recommended['setup_name'])->toBe($setup->name)
        ->and($recommended['values'])->toMatchArray([
            'camber_front_deg' => -1.3,
            'caster_front_deg' => 9.5,
            'pinion_teeth' => 12,
            'crown_teeth' => 73,
            'final_drive' => '12/73',
        ]);

    $this->get(route('pit-mode.index', ['kart' => $kart->getKey()]))
        ->assertOk()
        ->assertSee('Ultime regolazioni utilizzate')
        ->assertSee('12/73');

    $this->post(route('pit-mode.sessions.start'), ['kart_id' => $kart->getKey(), 'session_type' => 'practice'])
        ->assertSessionHasNoErrors();

    $this->get(route('pit-mode.index', ['kart' => $kart->getKey()]))
        ->assertOk()
        ->assertSee('Riprendi valori')
        ->assertSee('data-in-pista-recommended-field="camber_front_deg" value="-1.3"', false)
        ->assertSee('data-in-pista-recommended-field="crown_teeth" value="73"', false);

    expect($session->setupSnapshot->fresh()->values['camber_front_deg'])->toEqual(-1.0);
});

test('latest kart settings exclude drafts, other karts and other workspaces', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($owner);
    $kart = setUpInPistaKart($owner)['kart'];
    $otherKart = Vehicle::factory()->create([
        'workspace_id' => $owner->workspaces()->firstOrFail()->getKey(),
        'category' => 'kart',
        'name' => 'Another kart in the same team',
    ]);

    $this->post(route('pit-mode.sessions.start'), ['kart_id' => $kart->getKey(), 'session_type' => 'test'])
        ->assertSessionHasNoErrors();
    $draft = Session::query()->firstOrFail();
    $this->postJson(route('pit-mode.sessions.adjustments.store', $draft), ['caster_front_deg' => 6.5])
        ->assertSuccessful();

    expect(app(InPistaSessionService::class)->latestUsedSettings($kart))->toBeNull()
        ->and(app(InPistaSessionService::class)->latestUsedSettings($otherKart))->toBeNull();

    $this->post(route('pit-mode.sessions.finish', $draft))->assertSessionHasNoErrors();
    expect(app(InPistaSessionService::class)->latestUsedSettings($otherKart))->toBeNull();

    $outsider = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($outsider);
    $this->get(route('pit-mode.index', ['kart' => $kart->getKey()]))
        ->assertOk()
        ->assertDontSee('Ultime regolazioni utilizzate')
        ->assertDontSee('Another kart in the same team');
});

test('GPS forecast can be saved from the pit screen through the existing session endpoint', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $kart = setUpInPistaKart($user)['kart'];
    $this->post(route('pit-mode.sessions.start'), ['kart_id' => $kart->getKey(), 'session_type' => 'practice']);
    $session = Session::query()->firstOrFail();

    Http::fake(['api.met.no/*' => Http::response([
        'properties' => ['timeseries' => [[
            'time' => now()->toIso8601String(),
            'data' => ['instant' => ['details' => ['air_temperature' => 23.2, 'wind_speed' => 1.5]],
                'next_1_hours' => ['summary' => ['symbol_code' => 'clearsky_day']]],
        ]]],
    ])]);

    $this->postJson(route('sessions.weather.store', $session), [
        'latitude' => 44.12, 'longitude' => 7.88,
    ])->assertSuccessful()->assertJsonPath('weather.air_temperature_c', 23.2);

    expect($session->fresh()->weather_snapshot['provider'])->toBe('met_norway');
});

test('pit operations cannot read or write another teams kart or session', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($owner);
    $kart = setUpInPistaKart($owner)['kart'];
    $this->post(route('pit-mode.sessions.start'), ['kart_id' => $kart->getKey(), 'session_type' => 'practice']);
    $session = Session::query()->firstOrFail();

    $outsider = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($outsider);

    $this->post(route('pit-mode.sessions.start'), ['kart_id' => $kart->getKey(), 'session_type' => 'practice'])->assertNotFound();
    $this->postJson(route('pit-mode.sessions.laps.store', $session), ['lap_time' => '52.123'])->assertNotFound();
    $this->postJson(route('pit-mode.sessions.adjustments.store', $session), ['caster_front_deg' => 7])->assertNotFound();
    $this->get(route('pit-mode.index', ['kart' => $kart->getKey()]))->assertDontSee('PN Rent Kart 05');

    expect(TimingLap::query()->count())->toBe(0);
});
