<?php

use function Pest\Laravel\get;

it('opens the explorable public demo without authentication', function () {
    get(route('demo.public'))
        ->assertOk()
        ->assertSee('Race Team Demo')
        ->assertSee('Rotax MAX EVO #02')
        ->assertSee('Circuito di Busca')
        ->assertSee('pitmetric-public-demo', false)
        ->assertSee('data-public-demo-nav="garage"', false)
        ->assertSee('data-public-demo-nav="sessions"', false);
});
