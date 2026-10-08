<?php

test('game dev grand prix manager prototype is publicly available but not indexable', function () {
    $response = $this->get(route('game-dev'));

    $response->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('PitMetric Grand Prix Manager')
        ->assertSee('PITMETRIC GP')
        ->assertSee('Grand Prix Management Lab')
        ->assertSee('Pit wall pronto. Avvia la sessione.')
        ->assertSee('Prove libere')
        ->assertSee('Qualifica')
        ->assertSee('Gara');
});
