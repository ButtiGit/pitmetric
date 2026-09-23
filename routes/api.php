<?php

use App\Http\Controllers\MobileAppController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function (): void {
    Route::post('/login', [MobileAppController::class, 'login'])->middleware('throttle:20,1');
    Route::post('/register', [MobileAppController::class, 'register'])->middleware('throttle:10,1');

    Route::middleware('mobile.auth')->group(function (): void {
        Route::get('/me', [MobileAppController::class, 'me']);
        Route::post('/logout', [MobileAppController::class, 'logout']);
        Route::get('/bootstrap', [MobileAppController::class, 'bootstrap']);
        Route::post('/sync', [MobileAppController::class, 'syncOperation']);
        Route::get('/gallery', [MobileAppController::class, 'galleryIndex']);
        Route::post('/gallery', [MobileAppController::class, 'galleryStore']);
    });
});
