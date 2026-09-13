<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vehicle;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $databaseAccessEnabled = Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
        $domainReady = $databaseAccessEnabled && $workspaceContext->isReady();

        if ($domainReady) {
            $workspaceContext->personal($user);
        }

        return view('dashboard', [
            'databaseAccessEnabled' => $databaseAccessEnabled,
            'domainReady' => $domainReady,
            'vehicleCount' => $domainReady ? Vehicle::query()->count() : 0,
        ]);
    }
}
