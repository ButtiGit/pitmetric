<?php

use App\Models\User;

it('opens the public populated demo without authentication', function () {
    $this->get(route('demo.public'))
        ->assertOk()
        ->assertSee('Race Team Demo')
        ->assertSee('Busca Race Weekend');

    $this->get(route('demo.manager', ['section' => 'components']))
        ->assertOk()
        ->assertSee('Rotax MAX EVO #02');

    $this->get(route('demo.manager', ['section' => 'sessions']))
        ->assertOk()
        ->assertSee('Circuito di Busca');
});

it('links the public demo next to the guest auth actions', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('register'), false)
        ->assertSee(route('login'), false)
        ->assertSee(route('demo.public'), false);
});

it('requires authentication for legacy manager demo aliases', function () {
    $this->get(route('demo.garage'))->assertRedirect(route('login'));
});

it('keeps the authenticated local manager separate from the public demo', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);

    $this->get(route('demo.garage'))
        ->assertOk()
        ->assertSee('id="pitmetric-demo"', false)
        ->assertDontSee('Race Team Demo');

    foreach (['components', 'configurations', 'circuits', 'sessions', 'maintenance', 'expenses'] as $section) {
        $this->get(route('demo.'.$section))
            ->assertOk()
            ->assertSee('id="pitmetric-demo"', false)
            ->assertDontSee('Race Team Demo');
    }
});

test('public demo keeps navigation inside the demo and omits the workflow banner', function () {
    $this->get(route('demo.manager', ['section' => 'garage']))
        ->assertOk()
        ->assertSee('data-pm-app-shell', false)
        ->assertSee('data-pm-workspace-bar', false)
        ->assertSee('data-pm-demo-navigation', false)
        ->assertSee(route('demo.manager', ['section' => 'sessions']), false)
        ->assertDontSee('data-pm-workflow-nav', false);
});
