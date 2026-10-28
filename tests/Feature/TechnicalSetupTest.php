<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Expense;
use App\Models\Session;
use App\Models\SetupSnapshot;
use App\Models\TechnicalSetup;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;

function createTechnicalSetupPhysicalVersion(User $user, Vehicle $vehicle, string $configurationName): mixed
{
    $type = ComponentType::query()->firstOrCreate(['name' => 'Setup test component']);
    $component = Component::create([
        'component_type_id' => $type->id,
        'name' => $configurationName.' component',
        'status' => 'active',
    ]);
    ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $component->id,
        'created_by' => $user->id,
        'installed_at' => now(),
    ]);
    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => $configurationName,
        'status' => 'active',
    ]);

    return app(CreateConfigurationVersionService::class)->create($configuration, $user, [$component->id]);
}

it('creates technical setups and records their optional work cost', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'GT #12']);

    $this->actingAs($user)
        ->post(route('setups.store'), [
            'vehicle_id' => $vehicle->id,
            'name' => 'Dry baseline',
            'description' => 'Medium grip baseline',
            'tyre_pressure_fl' => '1.42',
            'tyre_pressure_fr' => '1.44',
            'brake_bias_pct' => '54.5',
            'operation_cost' => '35.00',
        ])
        ->assertRedirect(route('setups.index'));

    $setup = TechnicalSetup::query()->where('name', 'Dry baseline')->firstOrFail();
    $expense = Expense::query()
        ->where('related_type', 'technical_setup')
        ->where('related_id', $setup->id)
        ->firstOrFail();

    expect($setup->values)->toMatchArray([
        'tyre_pressure_fl' => '1.42',
        'tyre_pressure_fr' => '1.44',
        'brake_bias_pct' => '54.5',
    ])->and($expense->amount_cents)->toBe(3500)
        ->and($expense->category)->toBe('setup');
});

it('records kart camber and caster and preserves their values in session snapshots', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $kart = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'category' => 'kart', 'name' => 'Kart geometry']);

    $this->post(route('setups.store'), [
        'vehicle_id' => $kart->id,
        'name' => 'Front axle baseline',
        'camber_front_deg' => '-1.25',
        'caster_front_deg' => '10.50',
    ])->assertRedirect(route('setups.index'));

    $setup = TechnicalSetup::query()->where('name', 'Front axle baseline')->firstOrFail();
    expect($setup->values)->toMatchArray([
        'camber_front_deg' => '-1.25',
        'caster_front_deg' => '10.50',
    ]);

    $version = createTechnicalSetupPhysicalVersion($user, $kart, 'Geometry race build');
    $this->post(route('sessions.store'), [
        'configuration_version_id' => $version->id,
        'technical_setup_id' => $setup->id,
        'session_type' => 'practice',
        'started_at' => '2026-10-28 13:00:00',
    ])->assertRedirect();

    $snapshot = Session::query()->firstOrFail()->setupSnapshot()->firstOrFail();
    expect($snapshot->values)->toMatchArray([
        'camber_front_deg' => '-1.25',
        'caster_front_deg' => '10.50',
    ]);

    $this->put(route('setups.update', $setup), [
        'name' => 'Front axle baseline v2',
        'camber_front_deg' => '0.25',
        'caster_front_deg' => '12.00',
    ])->assertRedirect(route('setups.index'));

    expect($setup->fresh()->values)->toMatchArray(['caster_front_deg' => '12.00'])
        ->and($snapshot->fresh()->values)->toMatchArray([
            'camber_front_deg' => '-1.25',
            'caster_front_deg' => '10.50',
        ]);

    $this->get(route('setups.index'))->assertSuccessful()
        ->assertSee('name="caster_front_deg"', false)
        ->assertSee('value="12.00"', false);
});

it('validates kart caster limits before saving geometry', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $kart = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'category' => 'kart']);

    $this->actingAs($user)
        ->post(route('setups.store'), [
            'vehicle_id' => $kart->id,
            'name' => 'Invalid caster',
            'camber_front_deg' => '-1.00',
            'caster_front_deg' => '31',
        ])
        ->assertSessionHasErrors('caster_front_deg');

    expect(TechnicalSetup::query()->where('name', 'Invalid caster')->exists())->toBeFalse();
});

it('captures an immutable copy of the technical setup when a session is recorded', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Formula #7']);
    $version = createTechnicalSetupPhysicalVersion($user, $vehicle, 'Race build');
    $setup = TechnicalSetup::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Qualifying setup',
        'values' => [
            'tyre_pressure_fl' => 1.35,
            'brake_bias_pct' => 55.2,
            'aero_front' => 8,
        ],
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $this->post(route('sessions.store'), [
        'configuration_version_id' => $version->id,
        'technical_setup_id' => $setup->id,
        'session_type' => 'qualifying',
        'started_at' => '2026-09-16 10:00:00',
        'completed_laps' => 8,
        'duration_minutes' => 18,
    ])->assertRedirect();

    $session = Session::query()->firstOrFail();
    $snapshot = $session->setupSnapshot()->firstOrFail();

    expect($snapshot->technical_setup_id)->toBe($setup->id)
        ->and($snapshot->name)->toBe('Qualifying setup')
        ->and($snapshot->values)->toBe([
            'tyre_pressure_fl' => 1.35,
            'brake_bias_pct' => 55.2,
            'aero_front' => 8,
        ]);

    $this->put(route('setups.update', $setup), [
        'name' => 'Qualifying setup v2',
        'tyre_pressure_fl' => '1.55',
        'brake_bias_pct' => '53.0',
        'aero_front' => '10',
    ])->assertRedirect(route('setups.index'));

    $snapshot->refresh();

    expect($snapshot->name)->toBe('Qualifying setup')
        ->and($snapshot->values)->toBe([
            'tyre_pressure_fl' => 1.35,
            'brake_bias_pct' => 55.2,
            'aero_front' => 8,
        ]);
});

it('automatically snapshots the latest active setup when none is selected', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id]);
    $version = createTechnicalSetupPhysicalVersion($user, $vehicle, 'Baseline build');

    TechnicalSetup::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Old dry setup',
        'values' => ['brake_bias_pct' => 52],
        'status' => 'active',
        'created_by' => $user->id,
    ]);
    $latest = TechnicalSetup::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Current dry setup',
        'values' => ['brake_bias_pct' => 54],
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $this->post(route('sessions.store'), [
        'configuration_version_id' => $version->id,
        'session_type' => 'practice',
        'started_at' => '2026-09-16 11:00:00',
        'completed_laps' => 5,
    ])->assertRedirect();

    $snapshot = SetupSnapshot::query()->firstOrFail();

    expect($snapshot->technical_setup_id)->toBe($latest->id)
        ->and($snapshot->name)->toBe('Current dry setup')
        ->and($snapshot->values['brake_bias_pct'])->toBe(54);
});

it('does not allow a setup snapshot to be changed after capture', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id]);
    $version = createTechnicalSetupPhysicalVersion($user, $vehicle, 'Immutable build');

    $this->post(route('sessions.store'), [
        'configuration_version_id' => $version->id,
        'session_type' => 'test',
        'started_at' => '2026-09-16 12:00:00',
    ])->assertRedirect();

    $snapshot = SetupSnapshot::query()->firstOrFail();

    expect(fn () => $snapshot->update(['name' => 'Changed']))
        ->toThrow(LogicException::class, 'Setup snapshots are immutable.');
});

it('records hot and cold kart measurements after the session without rewriting its cold setup snapshot', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $kart = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'category' => 'kart', 'name' => 'Kart 12']);
    $version = createTechnicalSetupPhysicalVersion($user, $kart, 'Kart race build');
    $setup = TechnicalSetup::create([
        'vehicle_id' => $kart->id,
        'name' => 'Dry cold baseline',
        'values' => ['tyre_pressure_fl' => 0.8, 'tyre_pressure_rr' => 0.86],
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $this->post(route('sessions.store'), [
        'configuration_version_id' => $version->id,
        'technical_setup_id' => $setup->id,
        'session_type' => 'practice',
        'started_at' => '2026-10-28 10:00:00',
    ])->assertRedirect();

    $session = Session::query()->firstOrFail();
    $snapshot = $session->setupSnapshot()->firstOrFail();

    $this->post(route('sessions.tyre-readings.store', $session), [
        'phase' => 'hot',
        'pressure_fl' => '0.97',
        'pressure_rr' => '1.04',
        'measured_at' => '2026-10-28 10:25',
        'notes' => 'After session',
    ])->assertRedirect(route('sessions.index', ['recorded' => $session->id]));

    $this->post(route('sessions.tyre-readings.store', $session), [
        'phase' => 'cold',
        'pressure_fr' => '0.82',
    ])->assertRedirect();

    $this->post(route('sessions.tyre-readings.store', $session), [
        'phase' => 'hot',
        'pressure_fr' => '1.01',
    ])->assertRedirect();

    expect($session->tyreReadings()->count())->toBe(3)
        ->and($session->tyreReadings()->where('phase', 'hot')->where('notes', 'After session')->firstOrFail()->pressures)->toBe(['fl' => 0.97, 'rr' => 1.04])
        ->and($session->tyreReadings()->where('phase', 'cold')->firstOrFail()->pressures)->toBe(['fr' => 0.82])
        ->and($snapshot->fresh()->values)->toBe(['tyre_pressure_fl' => 0.8, 'tyre_pressure_rr' => 0.86]);

    $this->put(route('setups.update', $setup), ['name' => 'Updated setup', 'tyre_pressure_fl' => 1.1])->assertRedirect();
    expect($snapshot->fresh()->values)->toBe(['tyre_pressure_fl' => 0.8, 'tyre_pressure_rr' => 0.86]);

    $this->get(route('sessions.index'))->assertSuccessful()
        ->assertSee('After session')
        ->assertSee('0,97 bar')
        ->assertSee('1,01 bar');
});

it('validates kart pressure measurements and blocks sessions from other workspaces', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $workspace = $owner->workspaces()->firstOrFail();
    $this->actingAs($owner);

    $kart = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'category' => 'kart']);
    $version = createTechnicalSetupPhysicalVersion($owner, $kart, 'Kart pressure build');
    $this->post(route('sessions.store'), [
        'configuration_version_id' => $version->id,
        'session_type' => 'test',
        'started_at' => '2026-10-28 12:00:00',
    ])->assertRedirect();

    $session = Session::query()->firstOrFail();
    $this->post(route('sessions.tyre-readings.store', $session), ['phase' => 'hot'])
        ->assertSessionHasErrors('pressure_fl');
    $this->post(route('sessions.tyre-readings.store', $session), ['phase' => 'hot', 'pressure_fl' => '11'])
        ->assertSessionHasErrors('pressure_fl');
    expect($session->tyreReadings()->count())->toBe(0);

    $otherUser = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($otherUser)
        ->post(route('sessions.tyre-readings.store', $session), ['phase' => 'hot', 'pressure_fl' => '0.8'])
        ->assertNotFound();

    expect($session->tyreReadings()->withoutGlobalScopes()->count())->toBe(0);
});
