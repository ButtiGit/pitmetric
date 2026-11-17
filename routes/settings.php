<?php

use App\Http\Controllers\PrivacyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Route::get('settings/privacy', [PrivacyController::class, 'index'])->name('privacy.index');
    Route::post('settings/privacy', [PrivacyController::class, 'store'])->middleware(['password.confirm', 'throttle:5,60'])->name('privacy.store');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});

require __DIR__.'/public-app.php';
require __DIR__.'/productization.php';
require __DIR__.'/track-first.php';
