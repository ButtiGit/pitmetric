<?php

use App\Models\Configuration;
use App\Models\Expense;
use App\Models\Session;
use App\Models\SetupSnapshot;
use App\Models\TechnicalSetup;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;

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

it('captures an immutable copy of the technical setup when a session is recorded', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Formula #7']);
    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Race build',
        'status' => 'active',
    ]);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, []);
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
    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Baseline build',
        'status' => 'active',
    ]);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, []);

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
    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Immutable build',
        'status' => 'active',
    ]);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, []);

    $this->post(route('sessions.store'), [
        'configuration_version_id' => $version->id,
        'session_type' => 'test',
        'started_at' => '2026-09-16 12:00:00',
    ])->assertRedirect();

    $snapshot = SetupSnapshot::query()->firstOrFail();

    expect(fn () => $snapshot->update(['name' => 'Changed']))
        ->toThrow(LogicException::class, 'Setup snapshots are immutable.');
});
