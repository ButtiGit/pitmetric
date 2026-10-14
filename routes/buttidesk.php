<?php

use App\Modules\ButtiDeskPro\Http\Controllers\CalendarController;
use App\Modules\ButtiDeskPro\Http\Controllers\ContactController;
use App\Modules\ButtiDeskPro\Http\Controllers\DeskController;
use App\Modules\ButtiDeskPro\Http\Controllers\MailController;
use App\Modules\ButtiDeskPro\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

// A secret subscription URL can be consumed by external calendars without a browser session.
Route::get('/buttidesk/calendar/feed/{token}.ics', [CalendarController::class, 'feed'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:60,1')
    ->name('buttidesk.calendar.feed');

// Keep the existing PitMetric Studio mailbox wired to exactly the same mail service.
Route::middleware(['auth', 'verified', 'can:manage-updates'])
    ->prefix('studio/mail')
    ->name('studio.mail.')
    ->group(function (): void {
        Route::get('/', [StudioMailboxController::class, 'index'])->name('index');
        Route::get('/compose', [StudioMailboxController::class, 'compose'])->name('compose');
        Route::post('/send', [StudioMailboxController::class, 'send'])->name('send');
        Route::get('/{emailId}', [StudioMailboxController::class, 'show'])->name('show');
        Route::post('/{emailId}/reply', [StudioMailboxController::class, 'reply'])->name('reply');
    });

Route::middleware(['auth', 'verified', 'database.access', 'can:team-view'])
    ->prefix('buttidesk')
    ->name('buttidesk.')
    ->group(function (): void {
        Route::get('/', [DeskController::class, 'index'])->name('index');
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/reports', [TicketController::class, 'reports'])->name('tickets.reports');
        Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
        Route::get('/tickets/{ticket}/report', [TicketController::class, 'report'])->name('tickets.report');
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/download.ics', [CalendarController::class, 'download'])->name('calendar.download');

        Route::middleware('can:team-write')->group(function (): void {
            Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
            Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
            Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
            Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
            Route::post('/tickets/{ticket}/worklogs', [TicketController::class, 'log'])->name('tickets.worklogs.store');
            Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store');
            Route::put('/calendar/{appointment}', [CalendarController::class, 'update'])->name('calendar.update');
            Route::delete('/calendar/{appointment}', [CalendarController::class, 'destroy'])->name('calendar.destroy');
            Route::post('/calendar/feed', [CalendarController::class, 'newFeed'])->name('calendar.feed.create');
            Route::delete('/calendar/feed', [CalendarController::class, 'removeFeed'])->name('calendar.feed.remove');
        });

        // The mailbox is shared with PitMetric Studio, so it must retain the same editor gate.
        Route::middleware('can:manage-updates')->prefix('mail')->name('mail.')->group(function (): void {
            Route::get('/', [MailController::class, 'index'])->name('index');
            Route::post('/', [MailController::class, 'send'])->middleware('throttle:10,1')->name('send');
            Route::get('/received/{id}', [MailController::class, 'show'])->where('id', '[a-zA-Z0-9_-]+')->name('show');
            Route::post('/received/{id}/reply', [MailController::class, 'reply'])->middleware('throttle:10,1')->where('id', '[a-zA-Z0-9_-]+')->name('reply');
        });
    });
