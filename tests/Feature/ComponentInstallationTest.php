<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\User;
use App\Models\Vehicle;

it('tracks physical component installation removal and reinstalllation history', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspaceId = (int) $user->workspaces()->value('workspaces.id');
    $this->actingAs($user);

    $type = ComponentType::create(['name' => 'Engine']);
    $component = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Engine #4',
        'status' => 'active',
    ]);
    $kart27 = Vehicle::factory()->create(['workspace_id' => $workspaceId, 'name' => 'Kart 27']);
    $kart12 = Vehicle::factory()->create(['workspace_id' => $workspaceId, 'name' => 'Kart 12']);

    $this->post(route('component-installations.store'), [
        'component_id' => $component->id,
        'vehicle_id' => $kart27->id,
        'position_or_role' => 'Engine',
        'installed_at' => '2026-09-15 09:00:00',
        'notes' => 'Race weekend install',
    ])->assertRedirect(route('components.index'));

    $installation = ComponentInstallation::query()->firstOrFail();

    expect($installation->vehicle_id)->toBe($kart27->id)
        ->and($installation->component_id)->toBe($component->id)
        ->and($installation->removed_at)->toBeNull();

    $this->post(route('component-installations.store'), [
        'component_id' => $component->id,
        'vehicle_id' => $kart12->id,
        'installed_at' => '2026-09-15 10:00:00',
    ])->assertSessionHasErrors('component_id');

    expect(ComponentInstallation::query()->whereNull('removed_at')->count())->toBe(1);

    $this->patch(route('component-installations.remove', $installation), [
        'removed_at' => '2026-09-15 12:00:00',
    ])->assertRedirect(route('components.index'));

    expect($installation->fresh()->removed_at)->not->toBeNull();

    $this->post(route('component-installations.store'), [
        'component_id' => $component->id,
        'vehicle_id' => $kart12->id,
        'position_or_role' => 'Engine',
        'installed_at' => '2026-09-15 13:00:00',
    ])->assertRedirect(route('components.index'));

    expect(ComponentInstallation::query()->count())->toBe(2)
        ->and(ComponentInstallation::query()->whereNull('removed_at')->value('vehicle_id'))->toBe($kart12->id);
});

it('rejects removal timestamps earlier than installation', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspaceId = (int) $user->workspaces()->value('workspaces.id');
    $this->actingAs($user);

    $type = ComponentType::create(['name' => 'Chain']);
    $component = Component::create(['component_type_id' => $type->id, 'name' => 'Chain #1', 'status' => 'active']);
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspaceId]);
    $installation = ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $component->id,
        'created_by' => $user->id,
        'installed_at' => '2026-09-15 12:00:00',
    ]);

    $this->patch(route('component-installations.remove', $installation), [
        'removed_at' => '2026-09-15 11:59:00',
    ])->assertSessionHasErrors('removed_at');

    expect($installation->fresh()->removed_at)->toBeNull();
});

it('does not allow archiving a component while it is installed', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspaceId = (int) $user->workspaces()->value('workspaces.id');
    $this->actingAs($user);

    $type = ComponentType::create(['name' => 'Engine']);
    $component = Component::create(['component_type_id' => $type->id, 'name' => 'Engine active', 'status' => 'active']);
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspaceId]);
    ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $component->id,
        'created_by' => $user->id,
        'installed_at' => now(),
    ]);

    $this->delete(route('components.destroy', $component))
        ->assertSessionHasErrors('component');

    expect($component->fresh())->not->toBeNull();
});

it('keeps installation writes inside the authenticated workspace', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $otherUser = User::factory()->withDatabaseAccess()->create();
    $workspaceId = (int) $user->workspaces()->value('workspaces.id');
    $otherWorkspaceId = (int) $otherUser->workspaces()->value('workspaces.id');

    $this->actingAs($user);
    $type = ComponentType::create(['name' => 'Engine']);
    $component = Component::create(['component_type_id' => $type->id, 'name' => 'Own engine', 'status' => 'active']);
    $ownVehicle = Vehicle::factory()->create(['workspace_id' => $workspaceId]);
    $otherVehicle = Vehicle::factory()->create(['workspace_id' => $otherWorkspaceId]);

    $this->post(route('component-installations.store'), [
        'component_id' => $component->id,
        'vehicle_id' => $otherVehicle->id,
        'installed_at' => now()->format('Y-m-d H:i:s'),
    ])->assertNotFound();

    $this->post(route('component-installations.store'), [
        'component_id' => $component->id,
        'vehicle_id' => $ownVehicle->id,
        'installed_at' => now()->format('Y-m-d H:i:s'),
    ])->assertRedirect(route('components.index'));

    expect(ComponentInstallation::query()->count())->toBe(1);
});
