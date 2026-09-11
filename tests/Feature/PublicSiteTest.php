<?php

use App\Models\Update;

it('shows the public home page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Know every lap.')
        ->assertSee('Track every component.');
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
