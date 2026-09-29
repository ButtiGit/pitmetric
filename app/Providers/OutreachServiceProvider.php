<?php

namespace App\Providers;

use App\Http\Controllers\MarketingUnsubscribeController;
use App\Http\Controllers\OutreachStudioController;
use App\Http\Controllers\StudioMailboxController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class OutreachServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->group(function (): void {
            Route::get('/marketing/unsubscribe', MarketingUnsubscribeController::class)
                ->middleware('signed')
                ->name('marketing.unsubscribe');

            Route::middleware(['auth', 'verified', 'can:manage-updates'])
                ->prefix('studio')
                ->name('studio.')
                ->group(function (): void {
                    Route::get('/outreach', [OutreachStudioController::class, 'index'])->name('outreach.index');
                    Route::post('/outreach', [OutreachStudioController::class, 'send'])
                        ->middleware('throttle:3,1')
                        ->name('outreach.send');

                    Route::get('/mail', [StudioMailboxController::class, 'index'])->name('mail.index');
                    Route::get('/mail/compose', [StudioMailboxController::class, 'compose'])->name('mail.compose');
                    Route::post('/mail/send', [StudioMailboxController::class, 'send'])
                        ->middleware('throttle:10,1')
                        ->name('mail.send');
                    Route::get('/mail/{emailId}', [StudioMailboxController::class, 'show'])
                        ->where('emailId', '[A-Za-z0-9_-]+')
                        ->name('mail.show');
                    Route::post('/mail/{emailId}/reply', [StudioMailboxController::class, 'reply'])
                        ->where('emailId', '[A-Za-z0-9_-]+')
                        ->middleware('throttle:10,1')
                        ->name('mail.reply');
                });
        });
    }
}
