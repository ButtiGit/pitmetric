<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use App\Models\TrackCapture;
use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileBootstrapController extends Controller
{
    public function __invoke(Request $request, WorkspaceContext $workspaceContext): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $cloudEnabled = $user->hasDatabaseAccess() && $user->hasVerifiedEmail();
        $workspace = $cloudEnabled ? $workspaceContext->personal($user) : null;

        $captures = $cloudEnabled
            ? TrackCapture::query()
                ->latest('occurred_at')
                ->limit(50)
                ->get(['id', 'kind', 'circuit_name', 'lap_time_ms', 'payload', 'occurred_at', 'status', 'notes'])
                ->values()
            : collect();

        return response()->json([
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'email_verified' => $user->hasVerifiedEmail(),
                'cloud_enabled' => $cloudEnabled,
                'cloud_reason' => $user->hasVerifiedEmail()
                    ? ($user->hasDatabaseAccess() ? null : 'database_access_required')
                    : 'email_verification_required',
            ],
            'workspace' => $workspace === null ? null : [
                'id' => $workspace->getKey(),
                'name' => $workspace->name,
                'role' => $workspaceContext->role($user, (int) $workspace->getKey()),
                'can_write' => $workspaceContext->canWrite($user),
            ],
            'captures' => $captures,
            'gallery_count' => $cloudEnabled ? GalleryPhoto::query()->count() : 0,
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
