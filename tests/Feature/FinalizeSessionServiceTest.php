<?php

use App\Models\Circuit;
use App\Models\Component;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Session;
use App\Models\UsageBatch;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use App\Services\FinalizeSessionService;

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
