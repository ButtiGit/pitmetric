<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeLocale = $request->route('locale');

        $locale = is_string($routeLocale) && in_array($routeLocale, ['en', 'it'], true)
            ? $routeLocale
            : $request->cookie('pitmetric_locale', config('app.locale', 'en'));

        if (! in_array($locale, ['en', 'it'], true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
