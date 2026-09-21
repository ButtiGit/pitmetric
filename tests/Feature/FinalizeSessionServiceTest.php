<?php

use App\Models\Circuit;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\UsageBatch;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use App\Services\FinalizeSessionService;
use Illuminate\Validation\ValidationException;

it('finalizes a session once and propagates calculated usage idempotently', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Kart #27']);
    $type = ComponentType::create(['name' => 'Chain']);
    $component = Component::create(['component_type_id' => $type->id, 'name' => 'Chain #03', 'status' => 'active']);
    $distanceMetric = UsageMetricType::query()->where('key', 'distance')->firstOrFail();
    $tracker = ComponentTracker::create([
        'component_id' => $component->id,
        'usage_metric_type_id' => $distanceMetric->id,
        'is_active' => true,
    ]);

    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Race Build',
        'status' => 'active',
    ]);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, [$component->id]);

    $circuit = Circuit::create(['name' => 'Test Circuit']);
    $layout = $circuit->layouts()->create(['name' => 'Full', 'length_meters' => 1250, 'is_active' => true]);

    $session = Session::create([
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
        'circuit_layout_id' => $layout->id,
        'session_type' => 'practice',
        'started_at' => now(),
        'completed_laps' => 40,
        'duration_seconds' => 3000,
        'status' => 'draft',
        'created_by' => $user->id,
    ]);

    $service = app(FinalizeSessionService::class);
    $service->finalize($session);
    $service->finalize($session->fresh());

    expect(UsageBatch::query()->where('source_type', 'session')->where('source_id', $session->id)->count())->toBe(4)
        ->and($tracker->usageEntries()->count())->toBe(1)
        ->and((int) $tracker->usageEntries()->sum('value'))->toBe(50000)
        ->and($session->fresh()->status)->toBe('finalized')
        ->and($version->fresh()->locked_at)->not->toBeNull();
});

it('refuses to finalize a session when the physical vehicle no longer matches the selected configuration', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Kart #18']);
    $type = ComponentType::create(['name' => 'Chain']);
    $configuredComponent = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Chain configured',
        'status' => 'active',
    ]);
    $replacementComponent = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Chain replacement',
        'status' => 'active',
    ]);

    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Race Build',
        'status' => 'active',
    ]);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, [$configuredComponent->id]);

    $configuredComponent->activeInstallation->update(['removed_at' => now()]);
    ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $replacementComponent->id,
        'created_by' => $user->id,
        'installed_at' => now(),
    ]);

    $session = Session::create([
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
        'session_type' => 'practice',
        'started_at' => now(),
        'duration_seconds' => 900,
        'status' => 'draft',
        'created_by' => $user->id,
    ]);

    expect(fn () => app(FinalizeSessionService::class)->finalize($session))
        ->toThrow(ValidationException::class);

    expect($session->fresh()->status)->toBe('draft')
        ->and($version->fresh()->locked_at)->toBeNull()
        ->and(UsageBatch::query()->where('source_type', 'session')->where('source_id', $session->id)->count())->toBe(0);
});

it('keeps an event entry aligned with the configuration actually used by its latest session', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Kart #7']);
    $type = ComponentType::create(['name' => 'Engine']);
    $engine = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Engine A',
        'status' => 'active',
    ]);
    $radiator = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Radiator B',
        'status' => 'active',
    ]);

    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Weekend build',
        'status' => 'active',
    ]);
    $versionOne = app(CreateConfigurationVersionService::class)->create($configuration, $user, [$engine->id]);
    $versionTwo = app(CreateConfigurationVersionService::class)->create($configuration, $user, [$engine->id, $radiator->id]);

    $event = RaceEvent::create([
        'name' => 'Test weekend',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
        'status' => 'active',
        'created_by' => $user->id,
    ]);
    $driver = Driver::create(['display_name' => 'Test Driver']);
    $entry = EventEntry::create([
        'event_id' => $event->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $versionOne->id,
    ]);

    $session = Session::create([
        'event_id' => $event->id,
        'event_entry_id' => $entry->id,
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $versionTwo->id,
        'session_type' => 'practice',
        'started_at' => now(),
        'duration_seconds' => 1200,
        'status' => 'draft',
        'created_by' => $user->id,
    ]);

    app(FinalizeSessionService::class)->finalize($session);

    expect($entry->fresh()->configuration_version_id)->toBe($versionTwo->id)
        ->and($session->fresh()->status)->toBe('finalized');
});
