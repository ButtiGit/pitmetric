<?php

use Illuminate\Support\Facades\Route;

Route::get('/app', fn () => redirect()->route('localized.app', [
    'locale' => app()->getLocale(),
]))->name('app');
