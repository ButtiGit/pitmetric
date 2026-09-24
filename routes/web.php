<?php

use App\Http\Controllers\CircuitController;
use App\Http\Controllers\ComponentController;
use App\Http\Controllers\ComponentInstallationController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IntelligenceController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\NewsletterPreferencesController;
use App\Http\Controllers\PublicUpdateController;
use App\Http\Controllers\RaceEventController;
use App\Http\Controllers\RaceEventOperationsController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TechnicalSetupController;
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\TimingController;
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
    Route::get('/garage', [VehicleController::class, 'index'])->name('garage.index');
    Route::get('/components', [ComponentController::class, 'index'])->name('components.index');
    Route::get('/configurations', [ConfigurationController::class, 'index'])->name('configurations.index');
    Route::get('/setups', [TechnicalSetupController::class, 'index'])->name('setups.index');
    Route::get('/circuits', [CircuitController::class, 'index'])->name('circuits.index');
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');

    // Backwards-compatible beta aliases. The manager navigation now uses the canonical
    // *.index names; older links remain functional until all beta clients have rolled forward.
    Route::get('/demo/garage', [VehicleController::class, 'index'])->name('demo.garage');
    Route::get('/demo/components', [ComponentController::class, 'index'])->name('demo.components');
    Route::get('/demo/configurations', [ConfigurationController::class, 'index'])->name('demo.configurations');
    Route::get('/demo/circuits', [CircuitController::class, 'index'])->name('demo.circuits');
    Route::get('/demo/sessions', [SessionController::class, 'index'])->name('demo.sessions');
    Route::get('/demo/maintenance', [MaintenanceController::class, 'index'])->name('demo.maintenance');
    Route::get('/demo/expenses', [ExpenseController::class, 'index'])->name('demo.expenses');

    Route::get('/team/invitations/{teamInvitation}/accept', [TeamController::class, 'accept'])
        ->middleware('signed')
        ->name('team.invitations.accept');

    Route::middleware('database.access')->group(function () {
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::post('/team', [TeamController::class, 'store'])->name('team.store');
        Route::put('/team', [TeamController::class, 'update'])->name('team.update');
        Route::post('/team/switch/{workspace}', [TeamController::class, 'switch'])->name('team.switch');
        Route::post('/team/invitations', [TeamController::class, 'invite'])->name('team.invitations.store');
        Route::delete('/team/invitations/{teamInvitation}', [TeamController::class, 'revokeInvitation'])
            ->name('team.invitations.destroy');
        Route::patch('/team/members/{member}', [TeamController::class, 'updateMember'])->name('team.members.update');

        Route::get('/timing', [TimingController::class, 'index'])->name('timing.index');
        Route::get('/telemetry', [TelemetryController::class, 'index'])->name('telemetry.index');

        Route::get('/insights', [IntelligenceController::class, 'index'])->name('insights.index');
        Route::get('/insights/events/{raceEvent}', [IntelligenceController::class, 'weekendReport'])
            ->name('insights.events.report');
        Route::get('/insights/events/{raceEvent}/csv', [IntelligenceController::class, 'weekendReportCsv'])
            ->name('insights.events.report.csv');

        Route::get('/events/{raceEvent}', [RaceEventController::class, 'show'])->name('events.show');

        Route::middleware('can:team-write')->group(function () {
            Route::delete('/circuits/{circuit}', [CircuitController::class, 'destroy'])->name('circuits.destroy');
            Route::patch('/circuits/{circuit}/restore', [CircuitController::class, 'restore'])->withTrashed()->name('circuits.restore');
            Route::delete('/circuits/{circuit}/layouts/{circuitLayout}', [CircuitController::class, 'destroyLayout'])->name('circuits.layouts.destroy');
            Route::patch('/circuits/{circuit}/layouts/{circuitLayout}/restore', [CircuitController::class, 'restoreLayout'])->withTrashed()->name('circuits.layouts.restore');
            Route::patch('/events/{raceEvent}/restore', [RaceEventController::class, 'restore'])->withTrashed()->name('events.restore');
            Route::delete('/events/{raceEvent}', [RaceEventController::class, 'destroy'])->name('events.destroy');
            Route::put('/event-entries/{eventEntry}', [RaceEventOperationsController::class, 'updateEntry'])->name('events.entries.update');
            Route::delete('/event-entries/{eventEntry}', [RaceEventOperationsController::class, 'destroyEntry'])->name('events.entries.destroy');
            Route::put('/event-tasks/{eventTask}', [RaceEventOperationsController::class, 'updateTaskDetails'])->name('events.tasks.details');
            Route::delete('/event-tasks/{eventTask}', [RaceEventOperationsController::class, 'destroyTask'])->name('events.tasks.destroy');
            Route::put('/event-notes/{eventNote}', [RaceEventOperationsController::class, 'updateNote'])->name('events.notes.update');
            Route::delete('/event-notes/{eventNote}', [RaceEventOperationsController::class, 'destroyNote'])->name('events.notes.destroy');
            Route::put('/maintenance/{maintenanceSchedule}', [MaintenanceController::class, 'update'])->name('maintenance.update');
            Route::delete('/maintenance/{maintenanceSchedule}', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');
            Route::patch('/maintenance/{maintenanceSchedule}/restore', [MaintenanceController::class, 'restore'])->name('maintenance.restore');
            Route::delete('/maintenance/work-orders/{maintenanceWorkOrder}', [MaintenanceController::class, 'destroyWorkOrder'])->name('maintenance.work-orders.destroy');
            Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
            Route::delete('/telemetry/{telemetryImport}', [TelemetryController::class, 'destroy'])->name('telemetry.destroy');

            Route::post('/events', [RaceEventController::class, 'store'])->name('events.store');
            Route::patch('/events/{raceEvent}/status', [RaceEventController::class, 'updateStatus'])->name('events.status');
            Route::put('/drivers/{driver}', [RaceEventOperationsController::class, 'updateDriver'])->name('drivers.update');
            Route::delete('/drivers/{driver}', [RaceEventOperationsController::class, 'destroyDriver'])->name('drivers.destroy');
            Route::put('/events/{raceEvent}', [RaceEventController::class, 'update'])->name('events.update');
            Route::post('/drivers', [RaceEventOperationsController::class, 'storeDriver'])->name('drivers.store');
            Route::post('/events/{raceEvent}/entries', [RaceEventOperationsController::class, 'storeEntry'])->name('events.entries.store');
            Route::post('/events/{raceEvent}/schedule', [RaceEventOperationsController::class, 'storeScheduleItem'])->name('events.schedule.store');
            Route::patch('/event-schedule/{eventScheduleItem}', [RaceEventOperationsController::class, 'updateScheduleItem'])->name('events.schedule.update');
            Route::delete('/event-schedule/{eventScheduleItem}', [RaceEventOperationsController::class, 'destroyScheduleItem'])->name('events.schedule.destroy');
            Route::post('/events/{raceEvent}/tasks', [RaceEventOperationsController::class, 'storeTask'])->name('events.tasks.store');
            Route::patch('/event-tasks/{eventTask}', [RaceEventOperationsController::class, 'updateTask'])->name('events.tasks.update');
            Route::post('/events/{raceEvent}/notes', [RaceEventOperationsController::class, 'storeNote'])->name('events.notes.store');
            Route::post('/events/{raceEvent}/expenses', [RaceEventOperationsController::class, 'storeExpense'])->name('events.expenses.store');

            Route::post('/garage', [VehicleController::class, 'store'])->name('garage.store');
            Route::put('/garage/{vehicle}', [VehicleController::class, 'update'])->name('garage.update');
            Route::delete('/garage/{vehicle}', [VehicleController::class, 'destroy'])->name('garage.destroy');

            Route::put('/components/{component}', [ComponentController::class, 'update'])->name('components.update');
            Route::post('/components', [ComponentController::class, 'store'])->name('components.store');
            Route::delete('/components/{component}', [ComponentController::class, 'destroy'])->name('components.destroy');
            Route::post('/component-installations', [ComponentInstallationController::class, 'store'])
                ->name('component-installations.store');
            Route::patch('/component-installations/{componentInstallation}/remove', [ComponentInstallationController::class, 'remove'])
                ->name('component-installations.remove');

            Route::put('/configurations/{configuration}', [ConfigurationController::class, 'update'])->name('configurations.update');
            Route::post('/configurations', [ConfigurationController::class, 'store'])->name('configurations.store');
            Route::post('/configurations/{configuration}/versions', [ConfigurationController::class, 'storeVersion'])
                ->name('configurations.versions.store');
            Route::delete('/configurations/{configuration}', [ConfigurationController::class, 'destroy'])
                ->name('configurations.destroy');

            Route::post('/setups', [TechnicalSetupController::class, 'store'])->name('setups.store');
            Route::put('/setups/{technicalSetup}', [TechnicalSetupController::class, 'update'])->name('setups.update');
            Route::delete('/setups/{technicalSetup}', [TechnicalSetupController::class, 'destroy'])->name('setups.destroy');

            Route::put('/circuits/{circuit}', [CircuitController::class, 'update'])->name('circuits.update');
            Route::post('/circuits/{circuit}/layouts', [CircuitController::class, 'storeLayout'])->name('circuits.layouts.store');
            Route::put('/circuits/{circuit}/layouts/{circuitLayout}', [CircuitController::class, 'updateLayout'])->name('circuits.layouts.update');
            Route::post('/circuits', [CircuitController::class, 'store'])->name('circuits.store');
            Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
            Route::post('/timing/quick', [TimingController::class, 'storeQuickCapture'])->name('timing.quick.store');
            Route::post('/telemetry/import', [TelemetryController::class, 'store'])->name('telemetry.store');

            Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
            Route::post('/maintenance/work-orders', [MaintenanceController::class, 'storeWorkOrder'])
                ->name('maintenance.work-orders.store');
            Route::patch('/maintenance/work-orders/{maintenanceWorkOrder}', [MaintenanceController::class, 'updateWorkOrder'])->name('maintenance.work-orders.update');
            Route::post('/maintenance/work-orders/{maintenanceWorkOrder}/complete', [MaintenanceController::class, 'completeWorkOrder'])
                ->name('maintenance.work-orders.complete');
            Route::post('/maintenance/{maintenanceSchedule}/complete', [MaintenanceController::class, 'complete'])
                ->name('maintenance.complete');

            Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
            Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
            Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        });
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
