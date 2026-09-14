<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;

it('keeps component installation history when a configuration version changes', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id]);
    $type = ComponentType::create(['name' => 'Engine']);
    $engineA = Component::create(['component_type_id' => $type->id, 'name' => 'Engine A', 'status' => 'active']);
    $engineB = Component::create(['component_type_id' => $type->id, 'name' => 'Engine B', 'status' => 'active']);
    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Race Build',
        'status' => 'active',
    ]);

    $service = app(CreateConfigurationVersionService::class);
    $versionOne = $service->create($configuration, $user, [$engineA->id]);
    $versionTwo = $service->create($configuration, $user, [$engineB->id]);

    $firstInstallation = ComponentInstallation::query()->where('component_id', $engineA->id)->firstOrFail();
    $secondInstallation = ComponentInstallation::query()->where('component_id', $engineB->id)->firstOrFail();

    expect($versionOne->version_number)->toBe(1)
        ->and($versionTwo->version_number)->toBe(2)
        ->and($firstInstallation->removed_at)->not->toBeNull()
        ->and($secondInstallation->removed_at)->toBeNull()
        ->and(ComponentInstallation::query()->where('vehicle_id', $vehicle->id)->count())->toBe(2);
});
