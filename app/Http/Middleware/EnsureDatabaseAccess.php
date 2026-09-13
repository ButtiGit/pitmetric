<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureDatabaseAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        if (Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess()) {
            return $next($request);
        }

        abort(403, 'Database access is not enabled for this account.');
    }
}
