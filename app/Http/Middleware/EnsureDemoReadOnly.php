<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDemoReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('pitmetric.demo_read_only', false)) {
            return $next($request);
        }

        if ($request->routeIs('newsletter.unsubscribe', 'team.invitations.accept')) {
            abort(403, 'Demo mode is read-only.');
        }

        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        if ($request->routeIs('locale.update', 'logout')) {
            return $next($request);
        }

        abort(403, 'Demo mode is read-only.');
    }
}
