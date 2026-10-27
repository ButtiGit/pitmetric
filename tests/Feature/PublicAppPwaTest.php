<?php

it('shows the public PWA option next to the Android APK in both languages', function () {
    $this->get(route('localized.app', ['locale' => 'it']))
        ->assertOk()
        ->assertSee('Scarica APK Android')
        ->assertSee('data-pm-pwa-download', false)
        ->assertSee('iPhone / Android')
        ->assertSee('Aggiungi alla schermata Home');

    $this->get(route('localized.app', ['locale' => 'en']))
        ->assertOk()
        ->assertSee('Download Android APK')
        ->assertSee('data-pm-pwa-download', false)
        ->assertSee('Add to Home Screen');
});

it('does not offer a broken PWA link before the static app is deployed', function () {
    if (is_file(public_path('mobile/index.html')) && is_file(public_path('mobile/sw.js'))) {
        $this->markTestSkipped('The PWA static build is already present on this runner.');
    }

    $this->get(route('localized.app', ['locale' => 'it']))
        ->assertOk()
        ->assertSee('PWA · iPhone / Android · in attivazione')
        ->assertSee('aria-disabled="true"', false);
});
