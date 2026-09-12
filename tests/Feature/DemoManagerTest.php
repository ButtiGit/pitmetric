<?php

use App\Models\User;

it('requires authentication for manager pages', function () {
    $this->get(route('demo.garage'))->assertRedirect(route('login'));
});

it('opens the real garage and keeps unfinished sections in the local demo', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);

    $this->get(route('demo.garage'))
        ->assertOk()
        ->assertSee('SERVER WORKSPACE')
        ->assertDontSee('localStorage');

    foreach (['components', 'configurations', 'circuits', 'sessions', 'maintenance', 'expenses'] as $section) {
        $this->get(route('demo.'.$section))->assertOk()->assertSee('localStorage');
    }
});
