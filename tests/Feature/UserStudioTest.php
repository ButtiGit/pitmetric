<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vehicle;

beforeEach(function () {
    config()->set('pitmetric.update_editor_emails', ['editor@example.com']);
});

it('keeps the user studio editor only', function () {
    $member = User::factory()->create(['email' => 'member@example.com']);

    $this->actingAs($member)
        ->get(route('studio.users.index'))
        ->assertForbidden();
});

it('lets an editor see registered users and their database access state', function () {
    $editor = User::factory()->create(['email' => 'editor@example.com', 'name' => 'PitMetric Editor']);
    $member = User::factory()->create(['email' => 'driver@example.com', 'name' => 'Track Driver']);

    expect($member->hasDatabaseAccess())->toBeFalse();

    $this->actingAs($editor)
        ->get(route('studio.users.index'))
        ->assertOk()
        ->assertSee($editor->email)
        ->assertSee($member->email)
        ->assertSee('Track Driver')
        ->assertSee(__('users.access'))
        ->assertSee(__('users.paused'));
});

it('keeps demo access available until an editor enables database access', function () {
    $member = User::factory()->create(['email' => 'driver@example.com']);

    expect($member->hasDatabaseAccess())->toBeFalse()
        ->and($member->workspaces()->count())->toBe(0);

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk();

    $this->get(route('demo.garage'))
        ->assertOk()
        ->assertSee('id="pitmetric-demo"', false)
        ->assertDontSee(__('demo.local_badge'));

    $this->post(route('garage.store'), [
        'name' => 'Blocked Kart',
        'category' => 'kart',
        'status' => 'active',
    ])->assertForbidden();
});

it('lets an editor enable and disable database access', function () {
    $editor = User::factory()->create(['email' => 'editor@example.com']);
    $member = User::factory()->create(['email' => 'driver@example.com']);

    expect($member->workspaces()->count())->toBe(0);

    $this->actingAs($editor)
        ->patch(route('studio.users.access', $member), ['database_access_enabled' => true])
        ->assertRedirect();

    $member->refresh();
    expect($member->hasDatabaseAccess())->toBeTrue()
        ->and($member->workspaces()->count())->toBe(1);

    $this->actingAs($member)
        ->get(route('demo.garage'))
        ->assertOk()
        ->assertSee(__('garage.workspace.badge'));

    $this->actingAs($editor)
        ->patch(route('studio.users.access', $member), ['database_access_enabled' => false])
        ->assertRedirect();

    $member->refresh();
    expect($member->hasDatabaseAccess())->toBeFalse()
        ->and($member->workspaces()->count())->toBe(1);

    $this->actingAs($member)
        ->get(route('demo.garage'))
        ->assertOk()
        ->assertSee('id="pitmetric-demo"', false)
        ->assertDontSee(__('demo.local_badge'));
});

it('does not allow editor database access to be disabled from the user studio', function () {
    $editor = User::factory()->create(['email' => 'editor@example.com']);

    $this->actingAs($editor)
        ->patch(route('studio.users.access', $editor), ['database_access_enabled' => false])
        ->assertForbidden();

    expect($editor->fresh()->hasDatabaseAccess())->toBeFalse();

    $this->actingAs($editor)
        ->get(route('demo.garage'))
        ->assertOk()
        ->assertSee(__('garage.workspace.badge'));
});

test('usage counts creations by their author across teams without counting edits or archives', function () {
    $editor = User::factory()->create(['email' => 'editor@example.com']);
    $author = User::factory()->withDatabaseAccess()->create();
    $colleague = User::factory()->withDatabaseAccess()->create();
    $workspace = $author->workspaces()->firstOrFail();
    $this->actingAs($author);
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id]);
    $vehicle->update(['name' => 'Updated kart']);
    $vehicle->delete();
    $colleague->workspaces()->attach($workspace, ['role' => 'mechanic_engineer', 'status' => 'active']);
    $this->actingAs($colleague);
    Vehicle::factory()->create(['workspace_id' => $workspace->id]);
    Vehicle::factory()->create(['workspace_id' => $colleague->workspaces()->where('workspaces.id', '!=', $workspace->id)->firstOrFail()->id]);
    AuditLog::create(['workspace_id' => $workspace->id, 'user_id' => $author->id, 'action' => 'created']);
    AuditLog::create(['workspace_id' => $workspace->id, 'user_id' => null, 'action' => 'created', 'subject_type' => Vehicle::class, 'subject_id' => 999999]);

    $this->actingAs($editor)->get(route('studio.users.index'))->assertOk()
        ->assertViewHas('users', function ($users) use ($author, $colleague, $editor): bool {
            return $users->firstWhere('id', $author->id)->records_created_count === 1
                && $users->firstWhere('id', $colleague->id)->records_created_count === 2
                && $users->firstWhere('id', $editor->id)->records_created_count === 0;
        });
});

test('the user studio shows login time in Italy including daylight saving and an honest unknown state', function () {
    $editor = User::factory()->create(['email' => 'editor@example.com']);
    $user = User::factory()->create(['last_login_at' => '2026-11-02 10:25:00']);
    $summerUser = User::factory()->create(['last_login_at' => '2026-07-01 10:25:00']);
    $this->actingAs($editor)->get(route('studio.users.index'))->assertOk()
        ->assertSee(__('users.records_created'))->assertSee(__('users.last_login'))
        ->assertSee('02/11/2026 11:25')->assertSee('01/07/2026 12:25')
        ->assertSee(__('users.login_unknown'));
    expect($user->fresh()->last_login_at->format('H:i'))->toBe('10:25')
        ->and($summerUser->fresh()->last_login_at->format('H:i'))->toBe('10:25');
});
