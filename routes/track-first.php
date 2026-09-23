<?php

use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\PitModeController;
use App\Http\Controllers\QuickCaptureController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'database.access'])->group(function () {
    Route::get('/inbox', [FollowUpController::class, 'index'])->name('follow-ups.index');

    Route::middleware('can:team-write')->group(function () {
        Route::post('/quick-captures/lap', [QuickCaptureController::class, 'storeLap'])->name('quick-captures.lap.store');
        Route::get('/pit', [PitModeController::class, 'index'])->name('pit-mode.index');
        Route::post('/pit/context', [PitModeController::class, 'updateContext'])->name('pit-mode.context.update');
        Route::delete('/pit/context', [PitModeController::class, 'clearContext'])->name('pit-mode.context.clear');
        Route::post('/pit/pressures', [PitModeController::class, 'storePressure'])->name('pit-mode.pressures.store');
        Route::post('/pit/issues', [PitModeController::class, 'storeIssue'])->name('pit-mode.issues.store');
        Route::post('/pit/component-changes', [PitModeController::class, 'storeComponentChange'])->name('pit-mode.component-changes.store');
        Route::post('/pit/notes', [PitModeController::class, 'storeNote'])->name('pit-mode.notes.store');
        Route::patch('/inbox/{followUpTask}/complete', [FollowUpController::class, 'complete'])->name('follow-ups.complete');
    });
});
