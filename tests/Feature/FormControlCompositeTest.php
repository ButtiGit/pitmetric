<?php

test('month week and datetime local controls are completed with PitMetric composites', function () {
    $javascript = file_get_contents(resource_path('js/form-controls-composite.js'));
    $css = file_get_contents(resource_path('css/form-controls-composite.css'));

    expect($javascript)
        ->toContain('function enhanceMonth')
        ->toContain('function enhanceWeek')
        ->toContain('function enhanceDateTime')
        ->toContain('input[type="month"]')
        ->toContain('input[type="week"]')
        ->toContain('input[type="datetime-local"]')
        ->toContain('MutationObserver')
        ->toContain("source.dataset.pmEnhanced = 'true'");

    expect($css)
        ->toContain('.pm-composite-control')
        ->toContain('.pm-composite-week')
        ->toContain('.pm-composite-datetime');
});

test('composite controls load before the native picker guard', function () {
    $javascript = file_get_contents(resource_path('js/app.js'));

    expect($javascript)
        ->toContain("import '../css/form-controls-composite.css';")
        ->toContain("import './form-controls-composite';")
        ->toContain("import './form-control-guard';");

    expect(strpos($javascript, "import './form-controls-composite';"))
        ->toBeLessThan(strpos($javascript, "import './form-control-guard';"));
});
