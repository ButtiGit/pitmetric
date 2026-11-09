<?php

namespace App\Http\Middleware;

use App\Services\StudioSiteTraffic;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class RecordStudioSiteTraffic
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($response->getStatusCode() >= 500) {
            $this->record('http_error', (string) ($request->route()?->getName() ?? 'unknown'));

            return;
        }

        if (! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return;
        }

        if (! $request->routeIs('localized.home', 'localized.about', 'localized.app', 'localized.updates.index', 'localized.updates.show')) {
            return;
        }

        if (Str::contains(Str::lower((string) $request->userAgent()), ['bot', 'crawler', 'spider', 'slurp', 'preview', 'lighthouse'])) {
            return;
        }

        $this->record('page_view', '/'.ltrim($request->path(), '/'));
    }

    private function record(string $kind, string $subject): void
    {
        try {
            app(StudioSiteTraffic::class)->record($kind, $subject);
        } catch (Throwable $exception) {
            Log::warning('Site monitoring could not record a request.', [
                'error_type' => class_basename($exception),
            ]);
        }
    }
}
