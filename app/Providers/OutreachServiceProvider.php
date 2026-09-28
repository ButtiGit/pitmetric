<?php

namespace App\Providers;

use App\Http\Controllers\MarketingUnsubscribeController;
use App\Http\Controllers\OutreachStudioController;
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
                });
        });
    }
}
