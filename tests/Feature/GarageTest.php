<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Expense;
use App\Models\User;
use App\Models\Vehicle;

it('shows only vehicles from the authenticated workspace', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $otherUser = User::factory()->withDatabaseAccess()->create();

    $workspaceId = (int) $user->workspaces()->value('workspaces.id');
    $otherWorkspaceId = (int) $otherUser->workspaces()->value('workspaces.id');

    $ownVehicle = Vehicle::factory()->create([
        'workspace_id' => $workspaceId,
        'name' => 'My Kart',
    ]);
    $otherVehicle = Vehicle::factory()->create([
        'workspace_id' => $otherWorkspaceId,
        'name' => 'Secret Kart',
    ]);

    $this->actingAs($user);

    expect(Vehicle::query()->pluck('id')->all())->toBe([$ownVehicle->id]);

    $this->get(route('garage.index'))
        ->assertOk()
        ->assertSee('My Kart')
        ->assertDontSee('Secret Kart');

    expect($otherVehicle->id)->not->toBe($ownVehicle->id);
});

it('creates vehicles inside the authenticated workspace and links acquisition cost', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspaceId = (int) $user->workspaces()->value('workspaces.id');

    $this->actingAs($user)
        ->post(route('garage.store'), [
            'name' => 'Kart #27',
            'category' => 'kart',
            'manufacturer' => 'Tony Kart',
            'model' => 'Racer 401',
            'year' => 2026,
            'identifier' => 'CHASSIS-27',
            'status' => 'active',
            'purchase_cost' => 4250.50,
            'notes' => 'Race kart',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('garage.index'));

    $vehicle = Vehicle::query()->where('name', 'Kart #27')->firstOrFail();

    $this->assertDatabaseHas('vehicles', [
        'id' => $vehicle->id,
        'workspace_id' => $workspaceId,
        'name' => 'Kart #27',
        'category' => 'kart',
        'identifier' => 'CHASSIS-27',
    ]);

    $this->assertDatabaseHas('expenses', [
        'workspace_id' => $workspaceId,
        'amount_cents' => 425050,
        'category' => 'vehicle',
        'related_type' => 'vehicle',
        'related_id' => $vehicle->id,
    ]);

    expect(Expense::query()->count())->toBe(1);
});

it('accepts specialist motorsport vehicle types', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspaceId = (int) $user->workspaces()->value('workspaces.id');

    $this->actingAs($user)
        ->post(route('garage.store'), [
            'name' => 'Formula #12',
            'category' => 'formula',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('garage.index'));

    $this->assertDatabaseHas('vehicles', [
        'workspace_id' => $workspaceId,
        'name' => 'Formula #12',
        'category' => 'formula',
    ]);
});

it('shows active installed components as direct garage shortcuts', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspaceId = (int) $user->workspaces()->value('workspaces.id');
    $this->actingAs($user);

    $type = ComponentType::create(['name' => 'Engine']);
    $activeComponent = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Engine #9',
        'status' => 'active',
    ]);
    $removedComponent = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Old engine #3',
        'status' => 'active',
    ]);
    $vehicle = Vehicle::factory()->create([
        'workspace_id' => $workspaceId,
        'name' => 'Formula #9',
        'category' => 'formula',
    ]);

    ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $activeComponent->id,
        'created_by' => $user->id,
        'position_or_role' => 'Power unit',
        'installed_at' => now()->subDay(),
    ]);
    ComponentInstallation::create([
        'vehicle_id' => $vehicle->id,
        'component_id' => $removedComponent->id,
        'created_by' => $user->id,
        'position_or_role' => 'Old power unit',
        'installed_at' => now()->subDays(10),
        'removed_at' => now()->subDays(2),
    ]);

    $this->get(route('garage.index'))
        ->assertOk()
        ->assertSee('Formula #9')
        ->assertSee('Engine #9')
        ->assertSee('Power unit')
        ->assertSee(route('components.index').'#component-'.$activeComponent->id, false)
        ->assertDontSee('Old engine #3');
});

it('validates vehicle data before storing it', function () {
    $user = User::factory()->withDatabaseAccess()->create();

    $this->actingAs($user)
        ->post(route('garage.store'), [
            'name' => '',
            'category' => 'spaceship',
            'year' => 1800,
            'status' => 'unknown',
        ])
        ->assertSessionHasErrors(['name', 'category', 'year', 'status']);
});

it('updates a vehicle from the current workspace', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspaceId = (int) $user->workspaces()->value('workspaces.id');
    $vehicle = Vehicle::factory()->create([
        'workspace_id' => $workspaceId,
        'name' => 'Old Name',
        'category' => 'kart',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->put(route('garage.update', $vehicle), [
            'name' => 'Race Kart',
            'category' => 'kart',
            'manufacturer' => 'OTK',
            'model' => 'Racer',
            'year' => 2026,
            'identifier' => 'NEW-27',
            'status' => 'inactive',
            'notes' => 'Stored after the season',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('garage.index'));

    $this->assertDatabaseHas('vehicles', [
        'id' => $vehicle->id,
        'workspace_id' => $workspaceId,
        'name' => 'Race Kart',
        'status' => 'inactive',
    ]);
});

it('soft deletes vehicles instead of destroying their history', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $workspaceId = (int) $user->workspaces()->value('workspaces.id');
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspaceId]);

    $this->actingAs($user)
        ->delete(route('garage.destroy', $vehicle))
        ->assertRedirect(route('garage.index'));

    $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
});

it('cannot bind or mutate a vehicle from another workspace', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $otherUser = User::factory()->withDatabaseAccess()->create();
    $otherWorkspaceId = (int) $otherUser->workspaces()->value('workspaces.id');
    $vehicle = Vehicle::factory()->create([
        'workspace_id' => $otherWorkspaceId,
        'name' => 'Other Workspace Kart',
    ]);

    $this->actingAs($user)
        ->put(route('garage.update', $vehicle), [
            'name' => 'Hijacked',
            'category' => 'kart',
            'status' => 'active',
        ])
        ->assertNotFound();

    $this->actingAs($user)
        ->delete(route('garage.destroy', $vehicle))
        ->assertNotFound();

    $this->assertDatabaseHas('vehicles', [
        'id' => $vehicle->id,
        'name' => 'Other Workspace Kart',
        'deleted_at' => null,
    ]);
});
