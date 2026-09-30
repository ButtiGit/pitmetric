<?php

use App\Models\User;

it('renders the guide center and operational next action on the dashboard', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-pm-guide', false)
        ->assertSee('data-pm-start-page-tour', false)
        ->assertSee('data-pm-start-full-tour', false)
        ->assertSee('data-pm-dashboard-next', false)
        ->assertSee('data-pm-dashboard-workflow', false)
        ->assertSee('Add your first vehicle');
});

it('keeps contextual page tours available throughout the core workflow', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    foreach ([
        'garage.index' => 'garage',
        'components.index' => 'components',
        'configurations.index' => 'configurations',
        'events.index' => 'events',
        'sessions.index' => 'sessions',
        'maintenance.index' => 'maintenance',
    ] as $routeName => $context) {
        $this->get(route($routeName))
            ->assertOk()
            ->assertSee('data-pm-guide', false)
            ->assertSee('data-pm-context="'.$context.'"', false)
            ->assertSee('data-pm-workflow-nav', false);
    }
});

it('offers contextual tours on performance pages too', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $this->actingAs($user);

    $this->get(route('timing.index'))
        ->assertOk()
        ->assertSee('data-pm-context="timing"', false)
        ->assertSee('Tour this page');

    $this->get(route('telemetry.index'))
        ->assertOk()
        ->assertSee('data-pm-context="telemetry"', false)
        ->assertSee('Tour this page');
});

it('ships the tour runtime in the application bundle entrypoint', function () {
    $entrypoint = file_get_contents(resource_path('js/app.js'));
    $runtime = file_get_contents(resource_path('js/onboarding.js'));

    expect($entrypoint)
        ->toContain("import './onboarding';")
        ->and($runtime)
        ->toContain("document.addEventListener('livewire:navigated', initializeOnboarding)")
        ->toContain('pitmetric:onboarding:')
        ->toContain("url.searchParams.set('pm-tour', 'full')");
});
