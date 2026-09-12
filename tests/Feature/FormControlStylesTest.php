<?php

test('modern form control stylesheet covers native non text controls', function () {
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

test('form control stylesheet is loaded by the main vite entry', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)->toContain("import '../css/form-controls.css';");
});
