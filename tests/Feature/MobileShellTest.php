<?php

use App\Models\User;

test('mobile app shell renders thumb navigation and a customizable dashboard cockpit', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-pm-mobile-cockpit', false)
        ->assertSee('data-pm-dashboard-customize', false)
        ->assertSee('data-pm-dashboard-settings', false)
        ->assertSee('data-pm-cockpit-card="trackside"', false)
        ->assertSee('data-pm-cockpit-card="garage"', false)
        ->assertSee('data-pm-cockpit-card="performance"', false)
        ->assertSee('data-pm-mobile-bottom-nav', false)
        ->assertSee(route('pit-mode.index'));
});

test('mobile navigation stays available away from the dashboard without duplicating the cockpit', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('garage.index'))
        ->assertOk()
        ->assertSee('data-pm-mobile-bottom-nav', false)
        ->assertSee('data-pm-mobile-more', false)
        ->assertDontSee('data-pm-mobile-cockpit', false);
});

test('mobile shell assets include persistent personalization and reduced motion support', function () {
    $app = file_get_contents(resource_path('js/app.js'));
    $javascript = file_get_contents(resource_path('js/mobile-shell.js'));
    $css = file_get_contents(resource_path('css/mobile-shell.css'));

    expect($app)
        ->toContain("import '../css/mobile-shell.css';")
        ->toContain("import './mobile-shell';");

    expect($javascript)
        ->toContain('pitmetric.mobile-dashboard.')
        ->toContain('localStorage.setItem')
        ->toContain("document.addEventListener('livewire:navigated'");

    expect($css)
        ->toContain('.pm-mobile-bottom-nav')
        ->toContain('.pm-mobile-cockpit-actions')
        ->toContain('@media (prefers-reduced-motion: reduce)');
});
