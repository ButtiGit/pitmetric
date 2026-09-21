<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Configuration;
use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CoreWorkflowService;
use App\Services\MaintenanceHealthService;
use App\Services\OperationalNotificationService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        WorkspaceContext $workspaceContext,
        CoreWorkflowService $coreWorkflow,
        MaintenanceHealthService $healthService,
        OperationalNotificationService $operationalNotifications,
    ): View {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $databaseAccessEnabled = Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
        $baseReady = $databaseAccessEnabled && $workspaceContext->isReady();
        $domainReady = $databaseAccessEnabled && $workspaceContext->isCoreReady();
        $eventsReady = $domainReady && $workspaceContext->isEventsReady();

        if ($baseReady) {
            $workspace = $workspaceContext->personal($user);
            $operationalNotifications->generate($workspace);
        }

        $vehicleCount = $baseReady ? Vehicle::query()->where('status', 'active')->count() : 0;
        $componentCount = $domainReady ? Component::query()->where('status', 'active')->count() : 0;
        $configurationCount = $domainReady ? Configuration::query()->where('status', 'active')->count() : 0;
        $sessionCount = $domainReady ? Session::query()->count() : 0;
        $maintenanceCount = $domainReady ? MaintenanceRecord::query()->count() : 0;
        $workflowSnapshot = $domainReady ? $coreWorkflow->snapshot($eventsReady) : null;
        $maintenanceSummary = [
            'ok' => 0,
            'due_soon' => 0,
            'overdue' => 0,
            'untracked' => 0,
            'attention' => 0,
        ];
        $monthExpenseCents = 0;
        $lastSession = null;
        $focusEvent = null;
        $eventCount = 0;

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
                ->with(['vehicle', 'configurationVersion.configuration', 'circuitLayout.circuit', 'usageValues.metric', 'raceEvent'])
                ->latest('started_at')
                ->latest('id')
                ->first();
        }

        if ($eventsReady) {
            $eventCount = RaceEvent::query()->count();
            $focusEvent = RaceEvent::query()
                ->with(['circuitLayout.circuit'])
                ->withCount(['entries', 'sessions', 'tasks'])
                ->where('status', 'active')
                ->orderBy('start_date')
                ->first();

            $focusEvent ??= RaceEvent::query()
                ->with(['circuitLayout.circuit'])
                ->withCount(['entries', 'sessions', 'tasks'])
                ->where('status', 'planned')
                ->whereDate('end_date', '>=', today())
                ->orderBy('start_date')
                ->first();
        }

        return view('dashboard', [
            'databaseAccessEnabled' => $databaseAccessEnabled,
            'domainReady' => $domainReady,
            'eventsReady' => $eventsReady,
            'vehicleCount' => $vehicleCount,
            'componentCount' => $componentCount,
            'configurationCount' => $configurationCount,
            'sessionCount' => $sessionCount,
            'maintenanceCount' => $maintenanceCount,
            'workflowSnapshot' => $workflowSnapshot,
            'maintenanceSummary' => $maintenanceSummary,
            'monthExpenseCents' => $monthExpenseCents,
            'lastSession' => $lastSession,
            'focusEvent' => $focusEvent,
            'eventCount' => $eventCount,
        ]);
    }
}
