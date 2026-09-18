<?php

use App\Http\Controllers\ControlCenterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'database.access'])
    ->prefix('control-center')
    ->name('control-center.')
    ->group(function () {
        Route::get('/', [ControlCenterController::class, 'index'])->name('index');
        Route::get('/export', [ControlCenterController::class, 'export'])->name('export');
        Route::post('/import', [ControlCenterController::class, 'import'])->name('import');

        Route::put('/documents/{document}', [ControlCenterController::class, 'updateDocument'])->name('documents.update');
        Route::post('/documents', [ControlCenterController::class, 'storeDocument'])->name('documents.store');
        Route::get('/documents/{document}/download', [ControlCenterController::class, 'downloadDocument'])->name('documents.download');
        Route::delete('/documents/{document}', [ControlCenterController::class, 'destroyDocument'])->name('documents.destroy');

        Route::post('/notifications/{notification}/read', [ControlCenterController::class, 'readNotification'])->name('notifications.read');
        Route::post('/notifications/read-all', [ControlCenterController::class, 'readAllNotifications'])->name('notifications.read-all');
    });
