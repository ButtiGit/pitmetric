<?php

use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileBootstrapController;
use App\Http\Controllers\Api\MobileGalleryController;
use App\Http\Controllers\Api\MobileSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile/v1')->name('api.mobile.')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/login', [MobileAuthController::class, 'login'])->name('login');
        Route::post('/register', [MobileAuthController::class, 'register'])->name('register');
    });

    Route::middleware(['mobile.auth', 'throttle:120,1'])->group(function () {
        Route::get('/bootstrap', MobileBootstrapController::class)->name('bootstrap');
        Route::post('/sync', MobileSyncController::class)->name('sync');
        Route::get('/gallery', [MobileGalleryController::class, 'index'])->name('gallery.index');
        Route::post('/gallery', [MobileGalleryController::class, 'store'])->name('gallery.store');
        Route::get('/gallery/{galleryPhoto}', [MobileGalleryController::class, 'show'])->name('gallery.show');
        Route::delete('/token', [MobileAuthController::class, 'logout'])->name('logout');
    });
});
