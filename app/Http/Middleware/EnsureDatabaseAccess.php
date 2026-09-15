<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureDatabaseAccess
{
    public function __construct(private readonly WorkspaceContext $workspaceContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        $isAdmin = Gate::forUser($user)->allows('manage-updates');

        if (! $isAdmin && ! $user->hasDatabaseAccess()) {
            abort(403, 'Database access is not enabled for this account.');
        }

        if ($this->workspaceContext->isTeamReady()) {
            $hasMemberships = $user->workspaces()->exists();
            $hasActiveMembership = $user->workspaces()->wherePivot('status', 'active')->exists();

            if (! $isAdmin && $hasMemberships && ! $hasActiveMembership) {
                abort(403, 'Your team membership is suspended.');
            }

            if (! $hasMemberships) {
                $this->workspaceContext->personal($user);
            }
        }

        return $next($request);
    }
}
