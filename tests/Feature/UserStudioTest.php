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

it('lets an editor see registered users', function () {
    $editor = User::factory()->create(['email' => 'editor@example.com', 'name' => 'PitMetric Editor']);
    $member = User::factory()->create(['email' => 'driver@example.com', 'name' => 'Track Driver']);

    $this->actingAs($editor)
        ->get(route('studio.users.index'))
        ->assertOk()
        ->assertSee($editor->email)
        ->assertSee($member->email)
        ->assertSee('Track Driver');
});

it('lets an editor disable and restore manager access', function () {
    $editor = User::factory()->create(['email' => 'editor@example.com']);
    $member = User::factory()->create(['email' => 'driver@example.com']);

    $this->actingAs($editor)
        ->patch(route('studio.users.access', $member), ['manager_access_enabled' => false])
        ->assertRedirect();

    $member->refresh();
    expect($member->hasManagerAccess())->toBeFalse();

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertRedirect(route('access.paused'));

    $this->actingAs($editor)
        ->patch(route('studio.users.access', $member), ['manager_access_enabled' => true])
        ->assertRedirect();

    $member->refresh();
    expect($member->hasManagerAccess())->toBeTrue();

    $this->actingAs($member)
        ->get(route('dashboard'))
        ->assertOk();
});

it('does not allow editor access to be disabled from the user studio', function () {
    $editor = User::factory()->create(['email' => 'editor@example.com']);

    $this->actingAs($editor)
        ->patch(route('studio.users.access', $editor), ['manager_access_enabled' => false])
        ->assertForbidden();

    expect($editor->fresh()->hasManagerAccess())->toBeTrue();
});
