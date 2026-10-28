<?php

use App\Http\Controllers\ButtiDeskBridgeController;
use App\Http\Controllers\MobileAppController;
use App\Http\Controllers\MobileWeatherController;
use App\Http\Middleware\AuthenticateButtiDeskBridge;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function (): void {
    Route::post('/login', [MobileAppController::class, 'login'])->middleware('throttle:20,1');
    Route::post('/register', [MobileAppController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/weather', [MobileWeatherController::class, 'show'])->middleware('throttle:12,1')->name('mobile.weather');

    Route::middleware('mobile.auth')->group(function (): void {
        Route::get('/me', [MobileAppController::class, 'me']);
        Route::post('/logout', [MobileAppController::class, 'logout']);
        Route::get('/bootstrap', [MobileAppController::class, 'bootstrap']);
        Route::post('/sync', [MobileAppController::class, 'syncOperation']);
        Route::get('/gallery', [MobileAppController::class, 'galleryIndex']);
        Route::post('/gallery', [MobileAppController::class, 'galleryStore']);
    });
});

Route::prefix('buttidesk/v1')->middleware([AuthenticateButtiDeskBridge::class, 'throttle:240,1'])->group(function (): void {
    Route::post('token', [ButtiDeskBridgeController::class, 'token']);
    Route::post('context', [ButtiDeskBridgeController::class, 'context']);
    Route::post('revoke', [ButtiDeskBridgeController::class, 'revoke']);
});
