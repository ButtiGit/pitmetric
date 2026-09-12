<?php

test('modern form control stylesheet covers native non text controls', function () {
    $css = file_get_contents(resource_path('css/form-controls.css'));

    expect($css)
        ->toContain("input[type='date']")
        ->toContain("input[type='time']")
        ->toContain("input[type='number']")
        ->toContain('select[multiple]')
        ->toContain('::-webkit-calendar-picker-indicator')
        ->toContain('option:checked')
        ->toContain('prefers-reduced-motion');
});

test('form control stylesheet is loaded by the main vite entry', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)->toContain("import '../css/form-controls.css';");
});
