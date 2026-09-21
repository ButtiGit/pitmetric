<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\User;
use App\Models\Vehicle;

beforeEach(function () {
    $this->operator = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($this->operator);
    $workspace = $this->operator->workspaces()->firstOrFail();
    $this->vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Kart #27']);
    $this->type = ComponentType::create(['name' => 'Engine']);
});

test('configuration creation requires a real physical build and rolls back cleanly', function () {
    $this->post(route('configurations.store'), [
        'vehicle_id' => $this->vehicle->id,
        'name' => 'Race build',
    ])->assertSessionHasErrors('components');

    expect(Configuration::query()->count())->toBe(0);
});

test('configuration creation snapshots installed components and rejects client controlled parts', function () {
    $installed = Component::create([
        'component_type_id' => $this->type->id,
        'name' => 'Installed engine',
        'status' => 'active',
    ]);
    $notInstalled = Component::create([
        'component_type_id' => $this->type->id,
        'name' => 'Spare engine',
        'status' => 'active',
    ]);
    $installation = ComponentInstallation::create([
        'vehicle_id' => $this->vehicle->id,
        'component_id' => $installed->id,
        'created_by' => $this->operator->id,
        'installed_at' => now(),
    ]);

    $this->post(route('configurations.store'), [
        'vehicle_id' => $this->vehicle->id,
        'name' => 'Injected build',
        'component_ids' => [$notInstalled->id],
    ])->assertSessionHasErrors('component_ids');

    expect(Configuration::query()->count())->toBe(0);

    $this->post(route('configurations.store'), [
        'vehicle_id' => $this->vehicle->id,
        'name' => 'Race build',
    ])->assertSessionHasNoErrors();

    $configuration = Configuration::query()->firstOrFail();
    $version = $configuration->versions()->with('components')->firstOrFail();

    expect($version->components->modelKeys())->toBe([$installed->id])
        ->and($installation->fresh()->removed_at)->toBeNull()
        ->and($notInstalled->activeInstallation)->toBeNull();
});

test('new configuration version captures a changed physical build', function () {
    $engineA = Component::create([
        'component_type_id' => $this->type->id,
        'name' => 'Engine A',
        'status' => 'active',
    ]);
    $engineB = Component::create([
        'component_type_id' => $this->type->id,
        'name' => 'Engine B',
        'status' => 'active',
    ]);
    $firstInstallation = ComponentInstallation::create([
        'vehicle_id' => $this->vehicle->id,
        'component_id' => $engineA->id,
        'created_by' => $this->operator->id,
        'installed_at' => now()->subHour(),
    ]);

    $this->post(route('configurations.store'), [
        'vehicle_id' => $this->vehicle->id,
        'name' => 'Race build',
    ])->assertSessionHasNoErrors();

    $configuration = Configuration::query()->firstOrFail();
    $firstInstallation->update(['removed_at' => now()]);
    ComponentInstallation::create([
        'vehicle_id' => $this->vehicle->id,
        'component_id' => $engineB->id,
        'created_by' => $this->operator->id,
        'installed_at' => now(),
    ]);

    $this->post(route('configurations.versions.store', $configuration), [
        'notes' => 'Engine changed',
    ])->assertSessionHasNoErrors();

    $versions = $configuration->versions()->with('components')->orderBy('version_number')->get();

    expect($versions)->toHaveCount(2)
        ->and($versions[0]->components->modelKeys())->toBe([$engineA->id])
        ->and($versions[1]->components->modelKeys())->toBe([$engineB->id])
        ->and(ComponentInstallation::query()->where('vehicle_id', $this->vehicle->id)->count())->toBe(2);
});

test('sessions page excludes stale configuration versions after a physical change', function () {
    $engineA = Component::create([
        'component_type_id' => $this->type->id,
        'name' => 'Engine A',
        'status' => 'active',
    ]);
    $engineB = Component::create([
        'component_type_id' => $this->type->id,
        'name' => 'Engine B',
        'status' => 'active',
    ]);
    $firstInstallation = ComponentInstallation::create([
        'vehicle_id' => $this->vehicle->id,
        'component_id' => $engineA->id,
        'created_by' => $this->operator->id,
        'installed_at' => now(),
    ]);

    $this->post(route('configurations.store'), [
        'vehicle_id' => $this->vehicle->id,
        'name' => 'Race build',
    ])->assertSessionHasNoErrors();
    $configuration = Configuration::query()->firstOrFail();
    $staleVersion = $configuration->versions()->firstOrFail();

    $firstInstallation->update(['removed_at' => now()]);
    ComponentInstallation::create([
        'vehicle_id' => $this->vehicle->id,
        'component_id' => $engineB->id,
        'created_by' => $this->operator->id,
        'installed_at' => now(),
    ]);

    $this->get(route('sessions.index'))
        ->assertSuccessful()
        ->assertViewHas('versions', fn ($versions) => $versions->isEmpty());

    $this->post(route('sessions.store'), [
        'configuration_version_id' => $staleVersion->id,
        'session_type' => 'test',
        'started_at' => now()->toDateTimeString(),
    ])->assertSessionHasErrors('configuration_version_id');
});
