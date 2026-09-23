<?php

use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\QuickCaptureController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'database.access'])->group(function () {
    Route::get('/inbox', [FollowUpController::class, 'index'])->name('follow-ups.index');

    Route::middleware('can:team-write')->group(function () {
        Route::post('/quick-captures/lap', [QuickCaptureController::class, 'storeLap'])->name('quick-captures.lap.store');
        Route::patch('/inbox/{followUpTask}/complete', [FollowUpController::class, 'complete'])->name('follow-ups.complete');
    });
});
