<?php

test('formula lab is available by direct URL without indexing', function () {
    $this->get(route('formula-lab'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive">', false)
        ->assertSee('Formula Lab · PitMetric')
        ->assertSee('/game_assets/formula-lab/app.mjs?v=1', false)
        ->assertSee('/game_assets/formula-lab/style.css?v=1', false);
});

test('formula lab stays outside the public navigation and sitemap', function () {
    $this->get(route('localized.home', ['locale' => 'it']))
        ->assertOk()
        ->assertDontSee('/formula-lab', false);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertDontSee('/formula-lab', false);
});
