<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('unverified users are redirected to the email verification notice', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('verification.notice'));
});

test('verified users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('base users see read only platform navigation without database only tools', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSee(route('dashboard'), false)
        ->assertSee(route('events.index'), false)
        ->assertSee(route('garage.index'), false)
        ->assertSee(route('components.index'), false)
        ->assertSee(route('configurations.index'), false)
        ->assertSee(route('setups.index'), false)
        ->assertSee(route('circuits.index'), false)
        ->assertSee(route('sessions.index'), false)
        ->assertSee(route('maintenance.index'), false)
        ->assertSee(route('expenses.index'), false)
        ->assertSee('data-pitmetric-context-help', false)
        ->assertDontSee(route('team.index'), false)
        ->assertDontSee(route('insights.index'), false)
        ->assertDontSee(route('control-center.index'), false);
});

test('database enabled workspace owners see the Control Center navigation', function () {
    $user = User::factory()->withDatabaseAccess()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('control-center.index'), false);
});
