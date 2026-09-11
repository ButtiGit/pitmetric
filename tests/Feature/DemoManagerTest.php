<?php

use App\Models\User;

it('requires authentication for demo manager pages', function () {
    $this->get(route('demo.garage'))->assertRedirect(route('login'));
});

it('lets a verified user open every demo manager section', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user);

    foreach (['garage', 'components', 'configurations', 'circuits', 'sessions', 'maintenance', 'expenses'] as $section) {
        $this->get(route('demo.'.$section))->assertOk()->assertSee('localStorage');
    }
});
