<?php

use App\Models\User;

it('requires authentication for manager pages', function () {
    $this->get(route('demo.garage'))->assertRedirect(route('login'));
});

it('opens the manager sections without public preview labels', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);

    $this->get(route('demo.garage'))
        ->assertOk()
        ->assertSee('WORKSPACE')
        ->assertDontSee('SERVER WORKSPACE')
        ->assertDontSee('DEMO LOCALE');

    foreach (['components', 'configurations', 'circuits', 'sessions', 'maintenance', 'expenses'] as $section) {
        $this->get(route('demo.'.$section))
            ->assertOk()
            ->assertSee('AREA GESTIONALE')
            ->assertDontSee('localStorage')
            ->assertDontSee('DEMO LOCALE');
    }
});
