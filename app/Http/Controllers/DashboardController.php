<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\MaintenanceRecord;
use App\Models\Session;
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
        $baseReady = $databaseAccessEnabled && $workspaceContext->isReady();
        $domainReady = $databaseAccessEnabled && $workspaceContext->isCoreReady();

        if ($baseReady) {
            $workspaceContext->personal($user);
        }

        return view('dashboard', [
            'databaseAccessEnabled' => $databaseAccessEnabled,
            'domainReady' => $domainReady,
            'vehicleCount' => $baseReady ? Vehicle::query()->count() : 0,
            'configurationCount' => $domainReady ? Configuration::query()->count() : 0,
            'sessionCount' => $domainReady ? Session::query()->count() : 0,
            'maintenanceCount' => $domainReady ? MaintenanceRecord::query()->count() : 0,
        ]);
    }
}
