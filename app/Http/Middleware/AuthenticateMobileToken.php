<?php

namespace App\Http\Middleware;

use App\Models\MobileAccessToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileToken
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return $this->unauthorized();
        }

        $token = MobileAccessToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->first();

        if (! $token || ($token->expires_at !== null && $token->expires_at->isPast())) {
            return $this->unauthorized();
        }

        $user = $token->user;
        if ($user === null) {
            return $this->unauthorized();
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        if ($token->last_used_at === null || $token->last_used_at->lt(now()->subHours(6))) {
            $token->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json([
            'message' => 'Mobile authentication required.',
            'code' => 'mobile_auth_required',
        ], 401);
    }
}
