<?php

use Illuminate\Support\Facades\Blade;

test('page header renders its title, description, breadcrumbs, actions, and optional help', function () {
    $html = Blade::render(<<<'BLADE'
        <x-pitmetric.page-header
            title="Garage"
            description="Manage your karts and their operational history."
            help="Vehicles are the physical assets managed by this workspace."
        >
            <x-slot:breadcrumbs>
                <span>Operations</span>
            </x-slot:breadcrumbs>

            <x-slot:actions>
                <button type="button">Add kart</button>
            </x-slot:actions>
        </x-pitmetric.page-header>
        BLADE);

    expect($html)
        ->toContain('data-pitmetric-component="page-header"')
        ->toContain('Garage')
        ->toContain('Manage your karts and their operational history.')
        ->toContain('Operations')
        ->toContain('Add kart')
        ->toContain('data-pitmetric-help-tooltip');
});

test('page header renders the requested semantic heading level', function () {
    $html = Blade::render('<x-pitmetric.page-header title="Garage" heading-level="2" />');

    expect($html)->toContain('<h2')->toContain('>Garage</h2>');
});

test('help tooltip renders a question mark trigger with accessible copy', function () {
    $html = Blade::render('<x-pitmetric.help-tooltip text="Helpful context" label="Explain this field" />');

    expect($html)
        ->toContain('data-pitmetric-help-tooltip')
        ->toContain('aria-label="Explain this field"')
        ->toContain('Helpful context')
        ->toContain('>?</button>');
});

test('metric card renders its label, value, and supporting text', function () {
    $html = Blade::render('<x-pitmetric.metric-card label="Recorded driving time" :value="24" supporting-text="Current season" />');

    expect($html)
        ->toContain('data-pitmetric-component="metric-card"')
        ->toContain('Recorded driving time')
        ->toContain('24')
        ->toContain('Current season');
});

test('metric card omits supporting text when it is not supplied', function () {
    $html = Blade::render('<x-pitmetric.metric-card label="Recorded driving time" value="24 h" />');

    expect($html)->not->toContain('data-pitmetric-metric-support');
});

test('metric card renders supported variants', function (string $variant) {
    $html = Blade::render('<x-pitmetric.metric-card label="Maintenance" value="Ready" :variant="$variant" />', [
        'variant' => $variant,
    ]);

    expect($html)->toContain("data-pitmetric-variant=\"{$variant}\"");
})->with(['neutral', 'success', 'warning', 'danger', 'info']);

test('metric card safely falls back to the neutral variant', function () {
    $html = Blade::render('<x-pitmetric.metric-card label="Maintenance" value="Ready" variant="unexpected" />');

    expect($html)->toContain('data-pitmetric-variant="neutral"');
});

test('status badge renders its label and a non-colour indicator', function () {
    $html = Blade::render('<x-pitmetric.status-badge label="Upcoming" variant="warning" />');

    expect($html)
        ->toContain('Upcoming')
        ->toContain('data-pitmetric-status-indicator');
});

test('status badge renders supported variants', function (string $variant) {
    $html = Blade::render('<x-pitmetric.status-badge label="State" :variant="$variant" />', [
        'variant' => $variant,
    ]);

    expect($html)->toContain("data-pitmetric-variant=\"{$variant}\"");
})->with(['neutral', 'success', 'warning', 'danger', 'info']);

test('status badge safely falls back to the neutral variant', function () {
    $html = Blade::render('<x-pitmetric.status-badge label="State" variant="unexpected" />');

    expect($html)->toContain('data-pitmetric-variant="neutral"');
});

test('empty state renders its title, description, and optional actions', function () {
    $html = Blade::render(<<<'BLADE'
        <x-pitmetric.empty-state
            title="No karts registered"
            description="Create a kart to begin tracking sessions and maintenance."
        >
            <x-slot:actions>
                <button type="button">Create kart</button>
            </x-slot:actions>
        </x-pitmetric.empty-state>
        BLADE);

    expect($html)
        ->toContain('data-pitmetric-component="empty-state"')
        ->toContain('No karts registered')
        ->toContain('Create a kart to begin tracking sessions and maintenance.')
        ->toContain('Create kart');
});

test('empty state works without actions', function () {
    $html = Blade::render('<x-pitmetric.empty-state title="No sessions recorded" description="Register a session when you return from the track." />');

    expect($html)
        ->toContain('No sessions recorded')
        ->not->toContain('data-pitmetric-empty-state-actions');
});
