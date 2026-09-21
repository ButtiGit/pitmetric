<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\MaintenanceSchedule;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CoreWorkflowService;
use App\Services\CreateConfigurationVersionService;
use App\Services\FinalizeSessionService;

beforeEach(function () {
    $this->operator = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($this->operator);
    $this->workflow = app(CoreWorkflowService::class);
});

test('core workflow only advances when each operational dependency is real', function () {
    expect($this->workflow->snapshot(false)['next']['stage'])->toBe('vehicle');

    $workspace = $this->operator->workspaces()->firstOrFail();
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id]);
    expect($this->workflow->snapshot(false)['next']['stage'])->toBe('components');

    $type = ComponentType::create(['name' => 'Engine']);
    $component = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Engine A',
        'status' => 'active',
    ]);
    $metric = UsageMetricType::query()->where('key', 'distance')->firstOrFail();
    $tracker = ComponentTracker::create([
        'component_id' => $component->id,
        'usage_metric_type_id' => $metric->id,
        'is_active' => true,
    ]);

    expect($this->workflow->snapshot(false)['next']['stage'])->toBe('components');

    ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $component->id,
        'created_by' => $this->operator->id,
        'installed_at' => now(),
    ]);

    expect($this->workflow->snapshot(false)['next']['stage'])->toBe('configuration');

    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Race build',
        'status' => 'active',
    ]);

    expect($this->workflow->snapshot(false)['next']['stage'])->toBe('configuration');

    $version = app(CreateConfigurationVersionService::class)
        ->create($configuration, $this->operator, [$component->id]);

    expect($this->workflow->snapshot(false)['next']['stage'])->toBe('session');

    $session = Session::create([
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
        'session_type' => 'test',
        'started_at' => now(),
        'distance_override_meters' => 1000,
        'status' => 'draft',
        'created_by' => $this->operator->id,
    ]);

    app(FinalizeSessionService::class)->finalize($session);

    $snapshot = $this->workflow->snapshot(false);
    expect($snapshot['steps']['session']['complete'])->toBeTrue()
        ->and($snapshot['steps']['usage']['complete'])->toBeTrue()
        ->and($snapshot['next']['stage'])->toBe('maintenance');

    MaintenanceSchedule::create([
        'component_tracker_id' => $tracker->id,
        'name' => 'Engine service',
        'interval_value' => 5000,
        'warning_value' => 500,
        'is_active' => true,
    ]);

    $snapshot = $this->workflow->snapshot(false);
    expect($snapshot['complete'])->toBeTrue()
        ->and($snapshot['steps']['maintenance']['complete'])->toBeTrue()
        ->and($snapshot['next']['stage'])->toBe('session');
});

test('changing the physical build makes configuration the next required step again', function () {
    $workspace = $this->operator->workspaces()->firstOrFail();
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id]);
    $type = ComponentType::create(['name' => 'Engine']);
    $engine = Component::create(['component_type_id' => $type->id, 'name' => 'Engine A', 'status' => 'active']);
    $radiator = Component::create(['component_type_id' => $type->id, 'name' => 'Radiator A', 'status' => 'active']);
    ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $engine->id,
        'created_by' => $this->operator->id,
        'installed_at' => now(),
    ]);
    $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => 'Race build', 'status' => 'active']);
    app(CreateConfigurationVersionService::class)->create($configuration, $this->operator, [$engine->id]);

    expect($this->workflow->snapshot(false)['next']['stage'])->toBe('session');

    ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $radiator->id,
        'created_by' => $this->operator->id,
        'installed_at' => now(),
    ]);

    expect($this->workflow->snapshot(false)['next']['stage'])->toBe('configuration');
});

test('race weekend is required before session when the event module is available', function () {
    $workspace = $this->operator->workspaces()->firstOrFail();
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id]);
    $type = ComponentType::create(['name' => 'Chassis']);
    $component = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Chassis A',
        'status' => 'active',
    ]);
    ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $component->id,
        'created_by' => $this->operator->id,
        'installed_at' => now(),
    ]);
    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Weekend build',
        'status' => 'active',
    ]);

    app(CreateConfigurationVersionService::class)
        ->create($configuration, $this->operator, [$component->id]);

    expect($this->workflow->snapshot(true)['next']['stage'])->toBe('event');

    RaceEvent::create([
        'name' => 'Test weekend',
        'start_date' => today(),
        'end_date' => today()->addDay(),
        'status' => 'planned',
        'created_by' => $this->operator->id,
    ]);

    expect($this->workflow->snapshot(true)['next']['stage'])->toBe('session');
});
