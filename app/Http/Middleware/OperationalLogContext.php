<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\WorkspaceContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class OperationalLogContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        $user = $request->user();
        $workspaceId = $user instanceof User ? app(WorkspaceContext::class)->currentId($user) : null;

        Log::withContext([
            'request_id' => $requestId,
            'user_id' => $user instanceof User ? $user->getKey() : null,
            'workspace_id' => $workspaceId,
            'route' => $request->route()?->getName(),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
