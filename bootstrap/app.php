<?php

use App\Http\Controllers\ResendWebhookController;
use App\Http\Middleware\AuthenticateMobileToken;
use App\Http\Middleware\EnsureDatabaseAccess;
use App\Http\Middleware\RecordStudioSiteTraffic;
use App\Http\Middleware\SecureResponses;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::post('/webhooks/resend', ResendWebhookController::class)
                ->middleware('throttle:120,1')
                ->name('webhooks.resend');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(SecureResponses::class);
        $middleware->appendToGroup('web', [SetLocale::class, RecordStudioSiteTraffic::class]);
        $middleware->alias([
            'database.access' => EnsureDatabaseAccess::class,
            'mobile.auth' => AuthenticateMobileToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation', 'details', 'message', 'code', 'recovery_code', 'two_factor_code']);
        $exceptions->respond(fn (Response $response) => app(SecureResponses::class)->protect(request(), $response));
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
