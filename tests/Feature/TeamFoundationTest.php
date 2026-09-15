<?php

use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

it('gives activated accounts an owner membership in their personal team', function () {
    $user = User::factory()->withDatabaseAccess()->create();

    $membership = DB::table('workspace_user')
        ->where('user_id', $user->id)
        ->first();

    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe('owner')
        ->and($membership->status)->toBe('active')
        ->and($membership->joined_at)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('team.index'))
        ->assertOk()
        ->assertSee($user->name.' Team')
        ->assertSee('Owner');
});

it('switches the current team and isolates operational data', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $firstTeam = $user->workspaces()->firstOrFail();

    $this->actingAs($user);

    Vehicle::factory()->create([
        'workspace_id' => $firstTeam->id,
        'name' => 'Kart Team A',
    ]);

    $this->post(route('team.store'), ['name' => 'Team B'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('team.index'));

    $secondTeam = Workspace::query()->where('name', 'Team B')->firstOrFail();

    $this->post(route('garage.store'), [
        'name' => 'Kart Team B',
        'category' => 'kart',
        'status' => 'active',
    ])->assertSessionHasNoErrors();

    $this->get(route('garage.index'))
        ->assertOk()
        ->assertSee('Kart Team B')
        ->assertDontSee('Kart Team A');

    $this->post(route('team.switch', $firstTeam))
        ->assertRedirect(route('dashboard'));

    $this->get(route('garage.index'))
        ->assertOk()
        ->assertSee('Kart Team A')
        ->assertDontSee('Kart Team B');

    expect($secondTeam->users()->whereKey($user->id)->wherePivot('role', 'owner')->exists())->toBeTrue();
});

it('accepts a signed invitation and grants the selected team role', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $invitee = User::factory()->create(['email' => 'mechanic@example.test']);
    $team = $owner->workspaces()->firstOrFail();

    $this->actingAs($owner)
        ->post(route('team.invitations.store'), [
            'email' => $invitee->email,
            'role' => 'mechanic_engineer',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('team.index'));

    $invitation = TeamInvitation::query()->firstOrFail();
    $signedUrl = URL::temporarySignedRoute(
        'team.invitations.accept',
        $invitation->expires_at,
        ['teamInvitation' => $invitation],
    );

    $this->actingAs($invitee)
        ->get($signedUrl)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('team.index'));

    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $team->id,
        'user_id' => $invitee->id,
        'role' => 'mechanic_engineer',
        'status' => 'active',
    ]);

    expect($invitee->fresh()->hasDatabaseAccess())->toBeTrue()
        ->and($invitation->fresh()->status)->toBe('accepted');

    $this->actingAs($invitee)
        ->post(route('garage.store'), [
            'name' => 'Mechanic Kart',
            'category' => 'kart',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('vehicles', [
        'workspace_id' => $team->id,
        'name' => 'Mechanic Kart',
    ]);
});

it('keeps driver and viewer roles read only on operational routes', function (string $role) {
    $owner = User::factory()->withDatabaseAccess()->create();
    $invitee = User::factory()->create(['email' => $role.'@example.test']);
    $team = $owner->workspaces()->firstOrFail();

    $invitation = TeamInvitation::create([
        'workspace_id' => $team->id,
        'invited_by' => $owner->id,
        'email' => $invitee->email,
        'role' => $role,
        'status' => 'pending',
        'expires_at' => now()->addDays(7),
    ]);

    $signedUrl = URL::temporarySignedRoute(
        'team.invitations.accept',
        $invitation->expires_at,
        ['teamInvitation' => $invitation],
    );

    $this->actingAs($invitee)->get($signedUrl)->assertRedirect(route('team.index'));

    $this->actingAs($invitee)
        ->get(route('garage.index'))
        ->assertOk();

    $this->post(route('garage.store'), [
        'name' => 'Blocked Kart',
        'category' => 'kart',
        'status' => 'active',
    ])->assertForbidden();

    $this->assertDatabaseMissing('vehicles', [
        'workspace_id' => $team->id,
        'name' => 'Blocked Kart',
    ]);
})->with(['driver', 'viewer']);

it('lets owners update non owner memberships and blocks owner demotion', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $member = User::factory()->create();
    $team = $owner->workspaces()->firstOrFail();

    DB::table('workspace_user')->insert([
        'workspace_id' => $team->id,
        'user_id' => $member->id,
        'role' => 'driver',
        'status' => 'active',
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($owner)
        ->patch(route('team.members.update', $member), [
            'role' => 'manager',
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $team->id,
        'user_id' => $member->id,
        'role' => 'manager',
        'status' => 'active',
    ]);

    $this->patch(route('team.members.update', $owner), [
        'role' => 'viewer',
        'status' => 'suspended',
    ])->assertSessionHasErrors('member');

    $this->assertDatabaseHas('workspace_user', [
        'workspace_id' => $team->id,
        'user_id' => $owner->id,
        'role' => 'owner',
        'status' => 'active',
    ]);
});
