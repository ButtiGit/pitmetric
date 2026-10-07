<?php

test('returns a successful response from the localized home page', function () {
    $response = $this->get(route('localized.home', ['locale' => 'en']));

    $response->assertOk();
});
