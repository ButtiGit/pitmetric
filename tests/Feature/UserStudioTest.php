<?php

use App\Models\User;

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
