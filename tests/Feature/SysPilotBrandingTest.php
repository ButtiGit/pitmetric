<?php

test('SysPilot uses the PitMetric brand and static asset paths', function (): void {
    $html = file_get_contents(public_path('syspilot/index.html'));
    $css = file_get_contents(public_path('syspilot/app.css'));
    $sidebar = file_get_contents(resource_path('views/layouts/app/sidebar.blade.php'));

    expect($html)->toBeString()
        ->toContain('PitMetric · SysPilot')
        ->toContain('/brand/pitmetric-primary-dark.svg')
        ->toContain('href="/dashboard"')
        ->toContain('app.css?v=06')
        ->toContain('app.js?v=06')
        ->and($css)->toBeString()
        ->toContain('--pm-page: #0b0d10')
        ->toContain('--pm-accent: #E10600')
        ->toContain('Instrument Sans')
        ->and($sidebar)->toBeString()
        ->toContain('href="/syspilot/"')
        ->toContain("hasDatabaseAccess()")
        ->toContain("can('manage-updates')");
});
