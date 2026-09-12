<?php

use App\Models\User;
use App\Models\Vehicle;

it('shows only vehicles from the authenticated workspace', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

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

    $this->get(route('demo.garage'))
        ->assertOk()
        ->assertSee('My Kart')
        ->assertDontSee('Secret Kart');

    expect($otherVehicle->id)->not->toBe($ownVehicle->id);
});

it('creates vehicles inside the authenticated workspace automatically', function () {
    $user = User::factory()->create();
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
            'notes' => 'Race kart',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('demo.garage'));

    $this->assertDatabaseHas('vehicles', [
        'workspace_id' => $workspaceId,
        'name' => 'Kart #27',
        'category' => 'kart',
        'identifier' => 'CHASSIS-27',
    ]);
});

it('validates vehicle data before storing it', function () {
    $user = User::factory()->create();

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
    $user = User::factory()->create();
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
        ->assertRedirect(route('demo.garage'));

    $this->assertDatabaseHas('vehicles', [
        'id' => $vehicle->id,
        'workspace_id' => $workspaceId,
        'name' => 'Race Kart',
        'status' => 'inactive',
    ]);
});

it('soft deletes vehicles instead of destroying their history', function () {
    $user = User::factory()->create();
    $workspaceId = (int) $user->workspaces()->value('workspaces.id');
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspaceId]);

    $this->actingAs($user)
        ->delete(route('garage.destroy', $vehicle))
        ->assertRedirect(route('demo.garage'));

    $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
});

it('cannot bind or mutate a vehicle from another workspace', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
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
