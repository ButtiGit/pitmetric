<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use Illuminate\Validation\ValidationException;

it('captures physical installation history without mutating it', function () {
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

    $firstInstallation = ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $engineA->id,
        'created_by' => $user->id,
        'installed_at' => now()->subHour(),
    ]);

    $service = app(CreateConfigurationVersionService::class);
    $versionOne = $service->create($configuration, $user, [$engineA->id]);

    $firstInstallation->update(['removed_at' => now()]);
    $secondInstallation = ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $engineB->id,
        'created_by' => $user->id,
        'installed_at' => now(),
    ]);

    $versionTwo = $service->create($configuration, $user, [$engineB->id]);

    expect($versionOne->version_number)->toBe(1)
        ->and($versionOne->components->modelKeys())->toBe([$engineA->id])
        ->and($versionTwo->version_number)->toBe(2)
        ->and($versionTwo->components->modelKeys())->toBe([$engineB->id])
        ->and($firstInstallation->fresh()->removed_at)->not->toBeNull()
        ->and($secondInstallation->fresh()->removed_at)->toBeNull()
        ->and(ComponentInstallation::query()->where('vehicle_id', $vehicle->id)->count())->toBe(2);
});

it('refuses a configuration snapshot that diverges from the physical vehicle', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id]);
    $type = ComponentType::create(['name' => 'Engine']);
    $installed = Component::create(['component_type_id' => $type->id, 'name' => 'Installed engine', 'status' => 'active']);
    $different = Component::create(['component_type_id' => $type->id, 'name' => 'Different engine', 'status' => 'active']);
    ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $installed->id,
        'created_by' => $user->id,
        'installed_at' => now(),
    ]);
    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Race Build',
        'status' => 'active',
    ]);

    expect(fn () => app(CreateConfigurationVersionService::class)->create($configuration, $user, [$different->id]))
        ->toThrow(ValidationException::class);

    expect($configuration->versions()->count())->toBe(0)
        ->and($installed->activeInstallation)->not->toBeNull()
        ->and($different->activeInstallation)->toBeNull();
});

it('requires a real installed component before creating a version', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id]);
    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Empty build',
        'status' => 'active',
    ]);

    expect(fn () => app(CreateConfigurationVersionService::class)->create($configuration, $user, []))
        ->toThrow(ValidationException::class);

    expect($configuration->versions()->count())->toBe(0);
});
