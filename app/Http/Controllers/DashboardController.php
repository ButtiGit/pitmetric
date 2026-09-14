<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Configuration;
use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\Session;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\MaintenanceHealthService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        WorkspaceContext $workspaceContext,
        MaintenanceHealthService $healthService,
    ): View {
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

        $vehicleCount = $baseReady ? Vehicle::query()->where('status', 'active')->count() : 0;
        $componentCount = $domainReady ? Component::query()->where('status', 'active')->count() : 0;
        $configurationCount = $domainReady ? Configuration::query()->where('status', 'active')->count() : 0;
        $sessionCount = $domainReady ? Session::query()->count() : 0;
        $maintenanceCount = $domainReady ? MaintenanceRecord::query()->count() : 0;
        $maintenanceSummary = [
            'ok' => 0,
            'due_soon' => 0,
            'overdue' => 0,
            'untracked' => 0,
            'attention' => 0,
        ];
        $monthExpenseCents = 0;
        $lastSession = null;

        if ($domainReady) {
            $schedules = MaintenanceSchedule::query()
                ->with(['tracker.component', 'tracker.metric'])
                ->where('is_active', true)
                ->get();
            $maintenanceSummary = $healthService->snapshot($schedules)['summary'];
            $monthExpenseCents = (int) Expense::query()
                ->whereBetween('occurred_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount_cents');
            $lastSession = Session::query()
                ->with(['vehicle', 'configurationVersion.configuration', 'circuitLayout.circuit', 'usageValues.metric'])
                ->latest('started_at')
                ->latest('id')
                ->first();
        }

        return view('dashboard', [
            'databaseAccessEnabled' => $databaseAccessEnabled,
            'domainReady' => $domainReady,
            'vehicleCount' => $vehicleCount,
            'componentCount' => $componentCount,
            'configurationCount' => $configurationCount,
            'sessionCount' => $sessionCount,
            'maintenanceCount' => $maintenanceCount,
            'maintenanceSummary' => $maintenanceSummary,
            'monthExpenseCents' => $monthExpenseCents,
            'lastSession' => $lastSession,
        ]);
    }
}
