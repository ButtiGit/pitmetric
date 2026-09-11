<?php

use App\Models\Update;

it('shows the public home page in English by default', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Know every lap.')
        ->assertSee('PitMetric is motorsport software');
});

it('switches the public site to Italian using the locale cookie', function () {
    $this->withCookie('pitmetric_locale', 'it')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('PitMetric è un software motorsport')
        ->assertSee('Scopri PitMetric');
});

it('stores a valid language preference', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'it'])
        ->assertRedirect(route('home'))
        ->assertCookie('pitmetric_locale', 'it');
});

it('rejects an unsupported locale', function () {
    $this->from(route('home'))
        ->post(route('locale.update'), ['locale' => 'fr'])
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors('locale');
});

it('shows the cookie information page', function () {
    $this->get(route('cookies'))
        ->assertOk()
        ->assertSee('Cookies on PitMetric');
});

it('shows the public about page', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('Simone Butticè')
        ->assertSee('Junior Full-Stack Web Developer');
});

it('lists published updates', function () {
    $published = Update::factory()->create([
        'title' => 'Published update',
    ]);

    Update::factory()->draft()->create([
        'title' => 'Draft update',
    ]);

    $this->get(route('updates.index'))
        ->assertOk()
        ->assertSee($published->title)
        ->assertDontSee('Draft update');
});

it('shows a published update', function () {
    $update = Update::factory()->create();

    $this->get(route('updates.show', $update))
        ->assertOk()
        ->assertSee($update->title)
        ->assertSee($update->excerpt);
});

it('returns 404 for a draft update', function () {
    $update = Update::factory()->draft()->create();

    $this->get(route('updates.show', $update))->assertNotFound();
});
