<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NewsletterPreferencesController;
use App\Http\Controllers\PublicUpdateController;
use App\Http\Controllers\UpdateStudioController;
use App\Http\Controllers\UserStudioController;
use App\Http\Controllers\VehicleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.home')->name('home');
Route::view('/about', 'public.about')->name('about');
Route::view('/cookies', 'public.cookies')->name('cookies');
Route::get('/updates', [PublicUpdateController::class, 'index'])->name('updates.index');
Route::get('/updates/{update:slug}', [PublicUpdateController::class, 'show'])->name('updates.show');
Route::get('/newsletter/unsubscribe/{user}', [NewsletterPreferencesController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('newsletter.unsubscribe');

Route::post('/locale', function (Request $request) {
    $validated = $request->validate(['locale' => ['required', 'in:en,it']]);

    return back()->withCookie(cookie(
        'pitmetric_locale',
        $validated['locale'],
        60 * 24 * 365,
        '/',
        null,
        $request->isSecure(),
        false,
        false,
        'lax',
    ));
})->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('/garage', [VehicleController::class, 'index'])->name('demo.garage');
    Route::middleware('database.access')->group(function () {
        Route::post('/garage', [VehicleController::class, 'store'])->name('garage.store');
        Route::put('/garage/{vehicle}', [VehicleController::class, 'update'])->name('garage.update');
        Route::delete('/garage/{vehicle}', [VehicleController::class, 'destroy'])->name('garage.destroy');
    });

    foreach (['components', 'configurations', 'circuits', 'sessions', 'maintenance', 'expenses'] as $section) {
        Route::view('/'.$section, 'demo.workspace', ['initialSection' => $section])->name('demo.'.$section);
    }

    Route::get('/newsletter', [NewsletterPreferencesController::class, 'edit'])->name('newsletter.edit');
    Route::post('/newsletter', [NewsletterPreferencesController::class, 'update'])->name('newsletter.update');

    Route::middleware('can:manage-updates')
        ->prefix('studio')
        ->name('studio.')
        ->group(function () {
            Route::resource('updates', UpdateStudioController::class)->except('show');
            Route::get('users', [UserStudioController::class, 'index'])->name('users.index');
            Route::patch('users/{user}/access', [UserStudioController::class, 'updateAccess'])->name('users.access');
        });
});

require __DIR__.'/settings.php';
