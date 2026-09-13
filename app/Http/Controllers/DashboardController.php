<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vehicle;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $domainReady = $workspaceContext->isReady();

        if ($domainReady) {
            $workspaceContext->personal($user);
        }

        return view('dashboard', [
            'domainReady' => $domainReady,
            'vehicleCount' => $domainReady ? Vehicle::query()->count() : 0,
        ]);
    }
}
