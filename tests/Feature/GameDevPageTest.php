<?php

test('game dev career prototype is publicly available', function () {
    $response = $this->get(route('game-dev'));

    $response->assertOk()
        ->assertSee('Open Wheel Career 26')
        ->assertSee('Crea il tuo personaggio')
        ->assertSee('Regole 2026 implementate')
        ->assertSee('F1')
        ->assertSee('F2');
});
