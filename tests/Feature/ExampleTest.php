<?php

test('returns a successful response', function () {
    $response = $this->get(route('localized.home', ['locale' => 'en']));

    $response->assertOk();
});
