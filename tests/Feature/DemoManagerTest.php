<?php

use App\Models\User;

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
