<?php

use App\Http\Controllers\PublicUpdateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.home')->name('home');
Route::view('/about', 'public.about')->name('about');
Route::view('/cookies', 'public.cookies')->name('cookies');
Route::get('/updates', [PublicUpdateController::class, 'index'])->name('updates.index');
Route::get('/updates/{update:slug}', [PublicUpdateController::class, 'show'])->name('updates.show');

Route::post('/locale', function (Request $request) {
    $validated = $request->validate([
        'locale' => ['required', 'in:en,it'],
    ]);

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
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
