<?php

use function Pest\Laravel\get;

it('opens the cloned public manager without authentication', function () {
    get(route('demo.public'))
        ->assertOk()
        ->assertSee('Race Team Demo')
        ->assertSee('Busca Race Weekend')
        ->assertSee(route('demo.manager', ['section' => 'garage']), false)
        ->assertSee(route('demo.manager', ['section' => 'components']), false);
});

it('lets guests navigate the demo manager sections with populated sample data', function () {
    get(route('demo.manager', ['section' => 'garage']))
        ->assertOk()
        ->assertSee('KR2 Race Kart')
        ->assertSee('BMW M2 Track');

    get(route('demo.manager', ['section' => 'components']))
        ->assertOk()
        ->assertSee('Rotax MAX EVO #02')
        ->assertSee('Chain DID #04');

    get(route('demo.manager', ['section' => 'sessions']))
        ->assertOk()
        ->assertSee('Circuito di Busca')
        ->assertSee('52.184');
});
