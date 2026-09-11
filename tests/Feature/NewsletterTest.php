<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

it('stores newsletter consent during registration', function () {
    $this->post(route('register.store'), [
        'name' => 'Newsletter User',
        'email' => 'newsletter@example.com',
        'password' => 'Password!12345',
        'password_confirmation' => 'Password!12345',
        'newsletter_opt_in' => '1',
        'newsletter_locale' => 'it',
    ])->assertRedirect();

    $user = User::query()->where('email', 'newsletter@example.com')->firstOrFail();

    expect($user->newsletter_subscribed_at)->not->toBeNull()
        ->and($user->newsletter_locale)->toBe('it');
});

it('lets a verified user change newsletter preference', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->post(route('newsletter.update'), ['subscribed' => '1'])
        ->assertRedirect();

    expect($user->fresh()->newsletter_subscribed_at)->not->toBeNull();
});

it('supports signed unsubscribe links', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'newsletter_subscribed_at' => now(),
    ]);

    $url = URL::signedRoute('newsletter.unsubscribe', ['user' => $user]);

    $this->get($url)->assertOk();
    expect($user->fresh()->newsletter_subscribed_at)->toBeNull();
});
