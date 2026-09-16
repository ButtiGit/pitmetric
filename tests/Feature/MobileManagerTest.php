<?php

use App\Models\User;
use App\Services\WorkspaceContext;

const IPHONE_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_6 like Mac OS X) AppleWebKit/605.1.15 Version/18.6 Mobile/15E148 Safari/604.1';

it('lets an iPhone browser sign in and open the manager in demo-only mode', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->withHeader('User-Agent', IPHONE_USER_AGENT)
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);

    $this->withHeader('User-Agent', IPHONE_USER_AGENT)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-pm-mobile-header', false)
        ->assertSee('data-pm-mobile-dashboard', false)
        ->assertDontSee('Database update required');

    $this->withHeader('User-Agent', IPHONE_USER_AGENT)
        ->get(route('demo.garage'))
        ->assertOk()
        ->assertSee('id="pitmetric-demo"', false)
        ->assertDontSee(__('demo.local_badge'))
        ->assertDontSee(__('demo.local_copy'))
        ->assertDontSee(__('demo.seed'))
        ->assertDontSee(__('demo.reset'));

    expect($user->hasDatabaseAccess())->toBeFalse()
        ->and($user->workspaces()->count())->toBe(0);
});

it('keeps database-enabled dashboard and garage reachable while the workspace schema is being deployed', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $workspaceContext = Mockery::mock(WorkspaceContext::class);
    $workspaceContext->shouldReceive('isReady')->twice()->andReturnFalse();
    $workspaceContext->shouldReceive('isCoreReady')->once()->andReturnFalse();
    $workspaceContext->shouldReceive('canManage')->andReturnTrue()->byDefault();
    $this->app->instance(WorkspaceContext::class, $workspaceContext);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Database update required');

    $this->get(route('demo.garage'))
        ->assertOk()
        ->assertSee('Garage temporarily unavailable');
});

it('ships touch friendly mobile bottom sheets for custom form controls', function () {
    $css = file_get_contents(resource_path('css/mobile.css'));
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($css)
        ->toContain('@media (max-width: 42rem)')
        ->toContain('position: fixed')
        ->toContain('env(safe-area-inset-bottom')
        ->toContain('font-size: 16px !important')
        ->toContain('min-height: 3rem');

    expect($javascript)->toContain("import '../css/mobile.css';");
});
