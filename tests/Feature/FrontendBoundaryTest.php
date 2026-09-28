<?php

test('vite exposes one app entrypoint and one public entrypoint', function () {
    $vite = file_get_contents(base_path('vite.config.js'));

    expect($vite)
        ->toContain("'resources/js/app.js'")
        ->toContain("'resources/js/public.js'")
        ->not->toContain("'resources/css/app.css'")
        ->not->toContain("'resources/css/public.css'");
});

test('app and public layouts load only their own frontend entrypoint', function () {
    $appHead = file_get_contents(resource_path('views/partials/head.blade.php'));
    $publicLayout = file_get_contents(resource_path('views/layouts/public.blade.php'));

    expect($appHead)
        ->toContain("@vite('resources/js/app.js')")
        ->not->toContain('resources/js/public.js');

    expect($publicLayout)
        ->toContain("@vite('resources/js/public.js')")
        ->not->toContain('resources/js/app.js')
        ->not->toContain('<script>');
});

test('public assets are consolidated without polish and simplify patch layers', function () {
    $javascript = file_get_contents(resource_path('js/public.js'));
    $css = file_get_contents(resource_path('css/public.css'));

    expect($javascript)
        ->toContain("import '../css/public.css';")
        ->toContain("import './public-site';")
        ->not->toContain('workspace-forms')
        ->not->toContain('onboarding')
        ->not->toContain('pitmetric-demo');

    expect($css)
        ->toContain("@import './public-site.css';")
        ->toContain("@import './home-editorial.css';")
        ->toContain('.pm-public-header-action')
        ->toContain('.pm-partner-notice');

    expect(file_exists(resource_path('css/public-polish.css')))->toBeFalse()
        ->and(file_exists(resource_path('css/public-simplify.css')))->toBeFalse();
});
