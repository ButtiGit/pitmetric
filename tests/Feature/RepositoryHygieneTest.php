<?php

test('repository keeps project documentation entrypoints', function () {
    expect(file_exists(base_path('README.md')))->toBeTrue()
        ->and(file_exists(base_path('docs/README.md')))->toBeTrue();
});

test('starter and packaged artifacts are not committed', function () {
    expect(file_exists(resource_path('views/welcome.blade.php')))->toBeFalse()
        ->and(file_exists(base_path('tests/Feature/ExampleTest.php')))->toBeFalse()
        ->and(file_exists(base_path('tests/Unit/ExampleTest.php')))->toBeFalse()
        ->and(glob(base_path('brand/*.zip')) ?: [])->toBe([]);
});

test('standalone game keeps one canonical runtime bundle', function () {
    expect(file_exists(public_path('game_assets/game_sim.js')))->toBeTrue()
        ->and(file_exists(public_path('game_assets/game_sim.css')))->toBeTrue()
        ->and(file_exists(public_path('game_assets/game_data_2026.js')))->toBeTrue()
        ->and(glob(public_path('game_assets/game_sim_[0-9]*.js')) ?: [])->toBe([]);
});
