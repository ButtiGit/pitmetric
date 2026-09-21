<?php

test('app form controls are native first and keep the PitMetric visual system', function () {
    $css = file_get_contents(resource_path('css/forms.css'));

    expect($css)
        ->toContain('select.pm-input:not([multiple]):not([size])')
        ->toContain("input.pm-input[type='date']")
        ->toContain("input.pm-input[type='time']")
        ->toContain("input.pm-input[type='datetime-local']")
        ->toContain("input.pm-input[type='month']")
        ->toContain("input.pm-input[type='week']")
        ->toContain("input[type='number'].pm-input")
        ->toContain("input[type='checkbox']")
        ->toContain("input[type='radio']")
        ->toContain("input[type='range']")
        ->toContain("input[type='file'].pm-input::file-selector-button")
        ->toContain("input[type='color'].pm-input")
        ->toContain('::-webkit-calendar-picker-indicator')
        ->toContain('appearance: none')
        ->toContain('prefers-reduced-motion');
});

test('the custom picker framework has been removed from the app runtime', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain("import '../css/forms.css';")
        ->toContain("import './workspace-forms';")
        ->not->toContain('form-controls')
        ->not->toContain('form-control-guard')
        ->not->toContain('public-site');

    expect(file_exists(resource_path('js/form-controls.js')))->toBeFalse()
        ->and(file_exists(resource_path('js/form-controls-composite.js')))->toBeFalse()
        ->and(file_exists(resource_path('js/form-control-guard.js')))->toBeFalse()
        ->and(file_exists(resource_path('css/form-controls.css')))->toBeFalse()
        ->and(file_exists(resource_path('css/form-controls-composite.css')))->toBeFalse()
        ->and(file_exists(resource_path('css/form-control-popovers.css')))->toBeFalse()
        ->and(file_exists(resource_path('css/form-control-guard.css')))->toBeFalse();
});

test('workspace form javascript is limited to application behavior rather than replacing controls', function () {
    $javascript = file_get_contents(resource_path('js/workspace-forms.js'));

    expect($javascript)
        ->toContain('function syncVehicleOptions')
        ->toContain('data-pm-responsive-table')
        ->toContain('data-pm-form-state')
        ->toContain('data-pm-submitting')
        ->toContain("document.addEventListener('livewire:navigated'")
        ->not->toContain('MutationObserver')
        ->not->toContain('pm-custom-control')
        ->not->toContain('data-pm-enhanced');
});
