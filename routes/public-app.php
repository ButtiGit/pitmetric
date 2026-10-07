<?php

use Illuminate\Support\Facades\Route;

Route::get('/app', fn () => redirect()->route('localized.app', [
    'locale' => app()->getLocale(),
], 301))->name('app');
