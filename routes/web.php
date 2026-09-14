<?php

use App\Http\Controllers\CircuitController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\NewsletterPreferencesController;
use App\Http\Controllers\PublicUpdateController;
use App\Http\Controllers\RaceEventController;
use App\Http\Controllers\RaceEventOperationsController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UpdateStudioController;
use App\Http\Controllers\UserStudioController;
use App\Http\Controllers\VehicleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.home')->name('home');
Route::view('/about', 'public.about')->name('about');
Route::view('/cookies', 'public.cookies')->name('cookies');
Route::get('/updates', [PublicUpdateController::class, 'index'])->name('updates.index');
Route::get('/updates/{update:slug}/media', [PublicUpdateController::class, 'media'])->name('updates.media');
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

    Route::get('/events', [RaceEventController::class, 'index'])->name('events.index');
    Route::get('/garage', [VehicleController::class, 'index'])->name('demo.garage');
    Route::get('/components', [ComponentController::class, 'index'])->name('demo.components');
    Route::get('/configurations', [ConfigurationController::class, 'index'])->name('demo.configurations');
    Route::get('/circuits', [CircuitController::class, 'index'])->name('demo.circuits');
    Route::get('/sessions', [SessionController::class, 'index'])->name('demo.sessions');
    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('demo.maintenance');
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('demo.expenses');

    Route::middleware('database.access')->group(function () {
        Route::get('/events/{raceEvent}', [RaceEventController::class, 'show'])->name('events.show');
        Route::post('/events', [RaceEventController::class, 'store'])->name('events.store');
        Route::patch('/events/{raceEvent}/status', [RaceEventController::class, 'updateStatus'])->name('events.status');
        Route::post('/drivers', [RaceEventOperationsController::class, 'storeDriver'])->name('drivers.store');
        Route::post('/events/{raceEvent}/entries', [RaceEventOperationsController::class, 'storeEntry'])->name('events.entries.store');
        Route::post('/events/{raceEvent}/tasks', [RaceEventOperationsController::class, 'storeTask'])->name('events.tasks.store');
        Route::patch('/event-tasks/{eventTask}', [RaceEventOperationsController::class, 'updateTask'])->name('events.tasks.update');
        Route::post('/events/{raceEvent}/notes', [RaceEventOperationsController::class, 'storeNote'])->name('events.notes.store');
        Route::post('/events/{raceEvent}/expenses', [RaceEventOperationsController::class, 'storeExpense'])->name('events.expenses.store');

        Route::post('/garage', [VehicleController::class, 'store'])->name('garage.store');
        Route::put('/garage/{vehicle}', [VehicleController::class, 'update'])->name('garage.update');
        Route::delete('/garage/{vehicle}', [VehicleController::class, 'destroy'])->name('garage.destroy');

        Route::post('/components', [ComponentController::class, 'store'])->name('components.store');
        Route::delete('/components/{component}', [ComponentController::class, 'destroy'])->name('components.destroy');

        Route::post('/configurations', [ConfigurationController::class, 'store'])->name('configurations.store');
        Route::post('/configurations/{configuration}/versions', [ConfigurationController::class, 'storeVersion'])
            ->name('configurations.versions.store');
        Route::delete('/configurations/{configuration}', [ConfigurationController::class, 'destroy'])
            ->name('configurations.destroy');

        Route::post('/circuits', [CircuitController::class, 'store'])->name('circuits.store');
        Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');

        Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
        Route::post('/maintenance/{maintenanceSchedule}/complete', [MaintenanceController::class, 'complete'])
            ->name('maintenance.complete');

        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });

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
