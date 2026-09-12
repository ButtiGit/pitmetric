<?php

test('native form controls keep a styled fallback when javascript is unavailable', function () {
    $css = file_get_contents(resource_path('css/form-controls.css'));

    expect($css)
        ->toContain("input[type='date']")
        ->toContain("input[type='time']")
        ->toContain("input[type='number']")
        ->toContain("input[type='checkbox']")
        ->toContain("input[type='radio']")
        ->toContain("input[type='range']")
        ->toContain("input[type='file']")
        ->toContain("input[type='color']")
        ->toContain('select[multiple]')
        ->toContain('select:not([multiple]):not([size])')
        ->toContain('appearance: none !important')
        ->toContain('background-image: url(')
        ->toContain('::-webkit-calendar-picker-indicator')
        ->toContain('::-webkit-slider-thumb')
        ->toContain('::file-selector-button')
        ->toContain('option:checked')
        ->toContain('prefers-reduced-motion');
});

test('interactive non text controls use custom PitMetric popovers instead of native pickers', function () {
    $javascript = file_get_contents(resource_path('js/form-controls.js'));
    $css = file_get_contents(resource_path('css/form-control-popovers.css'));

    expect($javascript)
        ->toContain('function enhanceSelect')
        ->toContain('function enhanceDate')
        ->toContain('function enhanceTime')
        ->toContain('function enhanceNumber')
        ->toContain('buildCalendar')
        ->toContain('MutationObserver')
        ->not->toContain('showPicker(');

    expect($css)
        ->toContain('.pm-native-control-source')
        ->toContain('.pm-control-popover')
        ->toContain('.pm-calendar-popover')
        ->toContain('.pm-select-popover')
        ->toContain('.pm-time-popover')
        ->toContain('.pm-number-control');
});

test('custom form controls are loaded before the demo renderer', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain("import '../css/form-controls.css';")
        ->toContain("import '../css/form-control-popovers.css';")
        ->toContain("import './form-controls';")
        ->toContain("import './pitmetric-demo';");

    expect(strpos($javascript, "import './form-controls';"))
        ->toBeLessThan(strpos($javascript, "import './pitmetric-demo';"));
});
