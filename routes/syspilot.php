<?php

use App\Http\Controllers\SysPilotController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'database.access'])
    ->prefix('api/syspilot')
    ->name('syspilot.')
    ->group(function (): void {
        Route::get('/bootstrap', [SysPilotController::class, 'bootstrap'])->name('bootstrap');
        Route::get('/interventions/{intervention}', [SysPilotController::class, 'show'])->name('show');
        Route::post('/interventions', [SysPilotController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('store');
        Route::patch('/interventions/{intervention}/steps/{step}', [SysPilotController::class, 'updateStep'])->name('steps.update');
        Route::post('/interventions/{intervention}/ai/propose', [SysPilotController::class, 'proposeRevision'])
            ->middleware('throttle:3,1')->name('ai.propose');
        Route::post('/interventions/{intervention}/ai/apply', [SysPilotController::class, 'applyRevision'])
            ->middleware('throttle:10,1')->name('ai.apply');
        Route::post('/interventions/{intervention}/close', [SysPilotController::class, 'close'])->name('close');
    });
