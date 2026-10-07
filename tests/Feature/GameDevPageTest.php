<?php

test('game dev grand prix manager prototype is publicly available', function () {
    $response = $this->get(route('game-dev'));

    $response->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('PitMetric Grand Prix Manager')
        ->assertSee('PITMETRIC GP')
        ->assertSee('Pit Wall')
        ->assertSee('Race Control & Feed', false);
});
