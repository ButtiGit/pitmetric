<?php

use App\Models\User;

it('opens the public populated demo without authentication', function () {
    $this->get(route('demo.public'))
        ->assertOk()
        ->assertSee('Race Team Demo')
        ->assertSee('Rotax MAX EVO #02')
        ->assertSee('Circuito di Busca')
        ->assertSee('€ 4,018.70');
});

it('links the public demo next to the guest auth actions', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('register'), false)
        ->assertSee(route('login'), false)
        ->assertSee(route('demo.public'), false);
});

it('requires authentication for manager pages', function () {
    $this->get(route('demo.garage'))->assertRedirect(route('login'));
});

it('opens the manager sections without helper or public preview labels', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);

    $this->get(route('demo.garage'))
        ->assertOk()
        ->assertSee('id="pitmetric-demo"', false)
        ->assertDontSee(__('demo.local_badge'))
        ->assertDontSee(__('demo.local_copy'))
        ->assertDontSee(__('demo.seed'))
        ->assertDontSee(__('demo.reset'))
        ->assertDontSee('SERVER WORKSPACE')
        ->assertDontSee('DEMO LOCALE')
        ->assertDontSee('browser demo');

    foreach (['components', 'configurations', 'circuits', 'sessions', 'maintenance', 'expenses'] as $section) {
        $this->get(route('demo.'.$section))
            ->assertOk()
            ->assertSee('id="pitmetric-demo"', false)
            ->assertDontSee(__('demo.local_badge'))
            ->assertDontSee(__('demo.local_copy'))
            ->assertDontSee(__('demo.seed'))
            ->assertDontSee(__('demo.reset'))
            ->assertDontSee('DEMO LOCALE')
            ->assertDontSee('LOCAL DEMO')
            ->assertDontSee('stored locally in this browser');
    }
});
