<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

it('starts a verified read-only demo session from the public entry point', function () {
    $this->post(route('demo.enter'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('pitmetric.demo_read_only', true);

    $this->assertAuthenticated();

    $user = User::query()->where('email', 'demo@pitmetric.app')->firstOrFail();

    expect($user->name)->toBe('PitMetric Demo')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->hasDatabaseAccess())->toBeFalse();
});

it('blocks mutations while the demo session is active', function () {
    $this->post(route('demo.enter'));

    $this->post(route('newsletter.update'), [
        'subscribed' => '1',
    ])->assertForbidden();
});

it('blocks signed get routes that mutate data in demo mode', function () {
    $this->post(route('demo.enter'));

    $user = User::query()->where('email', 'demo@pitmetric.app')->firstOrFail();
    $url = URL::signedRoute('newsletter.unsubscribe', $user);

    $this->get($url)->assertForbidden();
});

it('keeps harmless locale switching available in demo mode', function () {
    $this->post(route('demo.enter'));

    $this->post(route('locale.update'), ['locale' => 'it'])
        ->assertRedirect();
});

it('marks manager demo pages as read only', function () {
    $this->post(route('demo.enter'));

    $this->get(route('garage.index'))
        ->assertOk()
        ->assertSee('data-read-only="true"', false);
});

it('shows the demo action to guests on the public site', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('demo.enter'), false)
        ->assertSee('Demo');
});
