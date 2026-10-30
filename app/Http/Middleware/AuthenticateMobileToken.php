<?php

namespace App\Http\Middleware;

use App\Models\MobileAccessToken;
use App\Models\Workspace;
use App\Services\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! is_string($plainToken) || $plainToken === '') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = MobileAccessToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('revoked_at')
            ->first();

        if ($accessToken === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($accessToken->user);
        $request->setUserResolver(fn () => $accessToken->user);
        $request->attributes->set('mobile_access_token', $accessToken);

        if ($request->hasHeader('X-Pitmetric-Workspace')) {
            $workspaceId = filter_var($request->header('X-Pitmetric-Workspace'), FILTER_VALIDATE_INT);
            abort_unless(is_int($workspaceId) && $workspaceId > 0, 403);
            $workspace = Workspace::query()->findOrFail($workspaceId);
            app(WorkspaceContext::class)->select($accessToken->user, $workspace);
        }

        return $next($request);
    }
}
