<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSyspilotAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // SysPilot is a private pilot: never allow every PitMetric user by default.
        $allowed = array_filter(array_map(
            static fn (string $email): string => mb_strtolower(trim($email)),
            explode(',', (string) config('syspilot.allowed_emails', ''))
        ));

        $email = mb_strtolower((string) $request->user()?->email);

        abort_unless($email !== '' && in_array($email, $allowed, true), 403, 'SysPilot non è abilitato per questo account.');

        return $next($request);
    }
}
