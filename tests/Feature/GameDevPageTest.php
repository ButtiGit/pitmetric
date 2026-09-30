<?php

test('game dev manager is publicly available with its track and controls', function () {
    $this->get(route('game-dev'))
        ->assertOk()
        ->assertSee('PitMetric Grand Prix Manager')
        ->assertSee('id="weekendSelect"', false)
        ->assertSee('id="teamSelect"', false)
        ->assertSee('id="trackSvg"', false)
        ->assertSee('/game_dev/v2-runtime.js', false);
});
