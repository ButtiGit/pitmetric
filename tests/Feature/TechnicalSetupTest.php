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

it('offers a read-only setup comparison with parameters restricted to the current workspace', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'My comparison kart']);

    $this->actingAs($user);

    TechnicalSetup::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Baseline dry',
        'values' => ['tyre_pressure_fl' => 0.8, 'caster_front_deg' => 9],
        'status' => 'active',
        'created_by' => $user->id,
    ]);
    TechnicalSetup::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Baseline wet',
        'values' => ['tyre_pressure_fl' => 0.85, 'caster_front_deg' => 10],
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $other = User::factory()->withDatabaseAccess()->create();
    $foreignWorkspace = $other->workspaces()->firstOrFail();
    $foreignVehicle = Vehicle::factory()->create(['workspace_id' => $foreignWorkspace->id]);
    TechnicalSetup::query()->forceCreate([
        'workspace_id' => $foreignWorkspace->id,
        'vehicle_id' => $foreignVehicle->id,
        'name' => 'Private foreign setup',
        'values' => ['caster_front_deg' => 4],
        'status' => 'active',
        'created_by' => $other->id,
    ]);

    $this->get(route('setups.index'))
        ->assertSuccessful()
        ->assertSee('compare-technical-setups')
        ->assertSee('data-pm-setup-comparison', false)
        ->assertSee('Baseline dry')
        ->assertSee('Baseline wet')
        ->assertDontSee('Private foreign setup');
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

it('stores independent mechanical units and both rear axle measurements in the existing setup snapshot', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $kart = Vehicle::factory()->create(['workspace_id' => $user->workspaces()->firstOrFail()->id, 'category' => 'kart']);

    $this->post(route('setups.store'), [
        'vehicle_id' => $kart->id,
        'name' => 'PN Rent mechanical markers',
        'camber_mode' => 'notches',
        'caster_mode' => 'holes',
        'geometry_side_mode' => 'combined',
        'rear_width_mode' => 'both',
        'camber_front_position' => 'tacca 2',
        'caster_front_position' => 'foro C',
        'track_width_rear_mm' => 1380,
        'rear_axle_length_mm' => 1030,
        'camber_front_deg' => -2,
    ])->assertSessionHasNoErrors();

    $setup = TechnicalSetup::query()->where('name', 'PN Rent mechanical markers')->sole();
    expect($setup->values)->toMatchArray([
        'camber_mode' => 'notches',
        'caster_mode' => 'holes',
        'geometry_side_mode' => 'combined',
        'rear_width_mode' => 'both',
        'camber_front_position' => 'tacca 2',
        'caster_front_position' => 'foro C',
        'track_width_rear_mm' => 1380,
        'rear_axle_length_mm' => 1030,
    ])->not->toHaveKey('camber_front_deg');

    $version = createTechnicalSetupPhysicalVersion($user, $kart, 'Mechanical setup snapshot');
    $this->post(route('sessions.store'), [
        'configuration_version_id' => $version->id,
        'technical_setup_id' => $setup->id,
        'session_type' => 'practice',
        'started_at' => '2026-10-29 13:00:00',
    ])->assertSessionHasNoErrors();

    expect(Session::query()->sole()->setupSnapshot->values)->toMatchArray($setup->values)
        ->and(TechnicalSetup::kartGeometrySummary($setup->values))->toMatchArray([
            'camber' => 'tacca 2',
            'caster' => 'foro C',
            'rear_width' => 'Carreggiata 1380 mm · Assale 1030 mm',
        ]);
});

it('allows different left and right units and keeps unrelated geometry fields out of the saved setup', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $kart = Vehicle::factory()->create(['workspace_id' => $user->workspaces()->firstOrFail()->id, 'category' => 'kart']);

    $this->post(route('setups.store'), [
        'vehicle_id' => $kart->id,
        'name' => 'Asymmetric kart',
        'camber_mode' => 'degrees',
        'caster_mode' => 'position',
        'geometry_side_mode' => 'separate',
        'rear_width_mode' => 'axle',
        'camber_front_left_deg' => -1.25,
        'camber_front_right_deg' => -1.50,
        'caster_front_left_position' => 'A2',
        'caster_front_right_position' => 'B1',
        'rear_axle_length_mm' => 1040,
        'track_width_rear_mm' => 1400,
        'caster_front_deg' => 12,
    ])->assertSessionHasNoErrors();

    $setup = TechnicalSetup::query()->where('name', 'Asymmetric kart')->sole();
    expect($setup->values)->toMatchArray([
        'camber_front_left_deg' => -1.25,
        'camber_front_right_deg' => -1.50,
        'caster_front_left_position' => 'A2',
        'caster_front_right_position' => 'B1',
        'rear_axle_length_mm' => 1040,
    ])->not->toHaveKey('track_width_rear_mm')
        ->not->toHaveKey('caster_front_deg')
        ->and(TechnicalSetup::kartGeometrySummary($setup->values))->toMatchArray([
            'camber' => 'SX -1.25 ° · DX -1.5 °',
            'caster' => 'SX A2 · DX B1',
            'rear_width' => 'Assale 1040 mm',
        ]);

    $this->get(route('setups.index'))->assertOk()
        ->assertSee('name="camber_mode"', false)
        ->assertSee('name="rear_axle_length_mm"', false)
        ->assertSee('name="caster_front_left_position"', false);
});

it('rejects unknown geometry units and out of range axle measurements', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $kart = Vehicle::factory()->create(['workspace_id' => $user->workspaces()->firstOrFail()->id, 'category' => 'kart']);

    $this->post(route('setups.store'), [
        'vehicle_id' => $kart->id,
        'name' => 'Invalid geometry selector',
        'camber_mode' => 'inches',
        'rear_width_mode' => 'axle',
        'rear_axle_length_mm' => 2600,
    ])->assertSessionHasErrors(['camber_mode', 'rear_axle_length_mm']);

    expect(TechnicalSetup::query()->where('name', 'Invalid geometry selector')->exists())->toBeFalse();
});

it('records kart track widths and retains their original measurements in session snapshots', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $kart = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'category' => 'kart', 'name' => 'Kart width test']);

    $this->post(route('setups.store'), [
        'vehicle_id' => $kart->id,
        'name' => 'Standard width',
        'track_width_front_mm' => '1180',
        'track_width_rear_mm' => '1395',
    ])->assertRedirect(route('setups.index'));

    $setup = TechnicalSetup::query()->where('name', 'Standard width')->firstOrFail();
    expect($setup->values)->toMatchArray([
        'track_width_front_mm' => '1180',
        'track_width_rear_mm' => '1395',
    ]);

    $version = createTechnicalSetupPhysicalVersion($user, $kart, 'Width race build');
    $this->post(route('sessions.store'), [
        'configuration_version_id' => $version->id,
        'technical_setup_id' => $setup->id,
        'session_type' => 'practice',
        'started_at' => '2026-10-28 14:00:00',
    ])->assertRedirect();

    $snapshot = Session::query()->firstOrFail()->setupSnapshot()->firstOrFail();
    expect($snapshot->values)->toMatchArray([
        'track_width_front_mm' => '1180',
        'track_width_rear_mm' => '1395',
    ]);

    $this->put(route('setups.update', $setup), [
        'name' => 'Narrow rear setup',
        'track_width_front_mm' => '1180',
        'track_width_rear_mm' => '1360',
    ])->assertRedirect(route('setups.index'));

    expect($setup->fresh()->values)->toMatchArray(['track_width_rear_mm' => '1360'])
        ->and($snapshot->fresh()->values)->toMatchArray([
            'track_width_front_mm' => '1180',
            'track_width_rear_mm' => '1395',
        ]);

    $this->get(route('setups.index'))->assertSuccessful()
        ->assertSee('name="track_width_front_mm"', false)
        ->assertSee('name="track_width_rear_mm"', false)
        ->assertSee('value="1360"', false);
});

it('rejects invalid kart track widths and leaves setups unchanged', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $kart = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'category' => 'kart']);

    $this->actingAs($user)
        ->post(route('setups.store'), [
            'vehicle_id' => $kart->id,
            'name' => 'Invalid width',
            'track_width_front_mm' => '400',
            'track_width_rear_mm' => '1395.5',
        ])
        ->assertSessionHasErrors(['track_width_front_mm', 'track_width_rear_mm']);

    expect(TechnicalSetup::query()->where('name', 'Invalid width')->exists())->toBeFalse();
});

it('calculates kart gearing from pinion and crown teeth and preserves the original session ratio', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $kart = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'category' => 'kart', 'name' => 'Kart gearing']);

    $this->post(route('setups.store'), [
        'vehicle_id' => $kart->id,
        'name' => 'Short gearing',
        'pinion_teeth' => '11',
        'crown_teeth' => '43',
        'final_drive' => 'manual old ratio',
        'gear_ratio' => '999',
    ])->assertRedirect(route('setups.index'));

    $setup = TechnicalSetup::query()->where('name', 'Short gearing')->firstOrFail();
    expect($setup->values)->toMatchArray([
        'pinion_teeth' => '11',
        'crown_teeth' => '43',
        'final_drive' => '11/43',
        'gear_ratio' => 3.909,
    ]);

    $version = createTechnicalSetupPhysicalVersion($user, $kart, 'Gearing race build');
    $this->post(route('sessions.store'), [
        'configuration_version_id' => $version->id,
        'technical_setup_id' => $setup->id,
        'session_type' => 'practice',
        'started_at' => '2026-10-28 15:00:00',
    ])->assertRedirect();

    $snapshot = Session::query()->firstOrFail()->setupSnapshot()->firstOrFail();
    expect($snapshot->values)->toMatchArray(['final_drive' => '11/43', 'gear_ratio' => 3.909]);

    $this->put(route('setups.update', $setup), [
        'name' => 'Longer gearing',
        'pinion_teeth' => '12',
        'crown_teeth' => '45',
        'final_drive' => '11/43',
    ])->assertRedirect(route('setups.index'));

    expect($setup->fresh()->values)->toMatchArray(['final_drive' => '12/45', 'gear_ratio' => 3.75])
        ->and($snapshot->fresh()->values)->toMatchArray(['final_drive' => '11/43', 'gear_ratio' => 3.909]);

    $this->get(route('setups.index'))->assertSuccessful()
        ->assertSee('name="pinion_teeth"', false)
        ->assertSee('name="crown_teeth"', false)
        ->assertSee('value="45"', false);
});

it('keeps manually entered legacy ratios and validates both kart gear tooth counts', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $kart = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'category' => 'kart']);
    $this->actingAs($user);

    $this->post(route('setups.store'), [
        'vehicle_id' => $kart->id,
        'name' => 'Old sprocket notes',
        'final_drive' => '11/43',
    ])->assertRedirect(route('setups.index'));

    $legacySetup = TechnicalSetup::query()->where('name', 'Old sprocket notes')->firstOrFail();
    expect($legacySetup->values)->toBe(['final_drive' => '11/43']);

    $this->post(route('setups.store'), [
        'vehicle_id' => $kart->id,
        'name' => 'Missing crown',
        'pinion_teeth' => '11',
    ])->assertSessionHasErrors('crown_teeth');

    $this->post(route('setups.store'), [
        'vehicle_id' => $kart->id,
        'name' => 'Invalid tooth counts',
        'pinion_teeth' => '0',
        'crown_teeth' => '43.5',
    ])->assertSessionHasErrors(['pinion_teeth', 'crown_teeth']);

    expect(TechnicalSetup::query()->count())->toBe(1);
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
