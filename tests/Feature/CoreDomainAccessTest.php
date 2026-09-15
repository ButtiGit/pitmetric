<?php

use App\Models\Component;
use App\Models\User;

it('keeps non activated accounts on the local demo and blocks database writes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('demo.components'))
        ->assertOk()
        ->assertSee('data-section="components"', false);

    $this->post(route('components.store'), [
        'name' => 'Engine #01',
        'type_name' => 'Engine',
        'metric_key' => 'runtime',
    ])->assertForbidden();

    expect(Component::withoutGlobalScopes()->count())->toBe(0);
});

it('persists components for manually activated accounts', function () {
    $user = User::factory()->withDatabaseAccess()->create();

    $this->actingAs($user)
        ->post(route('components.store'), [
            'name' => 'Engine #01',
            'type_name' => 'Engine',
            'metric_key' => 'runtime',
            'serial_number' => 'ENG-001',
        ])
        ->assertRedirect(route('components.index'));

    $component = Component::query()->with('trackers.metric')->firstOrFail();

    expect($component->name)->toBe('Engine #01')
        ->and($component->serial_number)->toBe('ENG-001')
        ->and($component->trackers)->toHaveCount(1)
        ->and($component->trackers->first()?->metric->key)->toBe('runtime');
});
