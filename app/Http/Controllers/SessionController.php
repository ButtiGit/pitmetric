<?php

namespace App\Http\Controllers;

use App\Models\CircuitLayout;
use App\Models\ConfigurationVersion;
use App\Models\Expense;
use App\Models\MaintenanceSchedule;
use App\Models\Session;
use App\Models\User;
use App\Services\FinalizeSessionService;
use App\Services\MaintenanceHealthService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext, MaintenanceHealthService $healthService): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $this->hasDatabaseAccess($user)) {
            return view('demo.workspace', ['initialSection' => 'sessions']);
        }

        if (! $workspaceContext->isCoreReady()) {
            return view('garage.unavailable');
        }

        $workspace = $workspaceContext->personal($user);
        $sessions = Session::query()
            ->with(['vehicle', 'configurationVersion.configuration', 'circuitLayout.circuit', 'usageValues.metric'])
            ->latest('started_at')
            ->latest('id')
            ->limit(50)
            ->get();

        $schedules = MaintenanceSchedule::query()
            ->with(['tracker.component', 'tracker.metric'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $health = $healthService->snapshot($schedules);
        $attentionSchedules = $schedules->filter(function (MaintenanceSchedule $schedule) use ($health): bool {
            $status = $health['states'][$schedule->getKey()]['status'] ?? 'untracked';

            return in_array($status, ['due_soon', 'overdue'], true);
        });
        $lastSession = $sessions->first();

        return view('sessions.index', [
            'versions' => ConfigurationVersion::query()
                ->whereHas('configuration', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
                ->with(['configuration.vehicle', 'components'])
                ->orderByDesc('id')
                ->get(),
            'layouts' => CircuitLayout::query()
                ->whereHas('circuit', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
                ->with('circuit')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'sessions' => $sessions,
            'defaults' => [
                'configuration_version_id' => $lastSession?->configuration_version_id,
                'circuit_layout_id' => $lastSession?->circuit_layout_id,
                'session_type' => $lastSession?->session_type ?? 'practice',
                'started_at' => now()->format('Y-m-d\TH:i'),
            ],
            'maintenanceSummary' => $health['summary'],
            'maintenanceStates' => $health['states'],
            'attentionSchedules' => $attentionSchedules,
        ]);
    }

    public function store(
        Request $request,
        WorkspaceContext $workspaceContext,
        FinalizeSessionService $finalizeSessionService,
        MaintenanceHealthService $healthService,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);

        $validated = $request->validate([
            'configuration_version_id' => ['required', 'integer'],
            'circuit_layout_id' => ['nullable', 'integer'],
            'session_type' => ['required', 'in:practice,qualifying,race,test'],
            'started_at' => ['required', 'date'],
            'completed_laps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'duration_minutes' => ['nullable', 'numeric', 'min:0', 'max:1440'],
            'distance_override_km' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'session_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'cost_description' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $version = ConfigurationVersion::query()
            ->whereKey($validated['configuration_version_id'])
            ->whereHas('configuration', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
            ->with('configuration.vehicle')
            ->firstOrFail();

        $layoutId = null;

        if (! empty($validated['circuit_layout_id'])) {
            $layoutId = CircuitLayout::query()
                ->whereKey($validated['circuit_layout_id'])
                ->whereHas('circuit', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
                ->value('id');

            abort_if($layoutId === null, 404);
        }

        $session = DB::transaction(function () use ($validated, $version, $layoutId, $user, $finalizeSessionService): Session {
            $session = Session::create([
                'vehicle_id' => $version->configuration->vehicle_id,
                'configuration_version_id' => $version->getKey(),
                'circuit_layout_id' => $layoutId,
                'session_type' => $validated['session_type'],
                'started_at' => Carbon::parse($validated['started_at']),
                'completed_laps' => $validated['completed_laps'] ?? null,
                'duration_seconds' => isset($validated['duration_minutes'])
                    ? (int) round(((float) $validated['duration_minutes']) * 60)
                    : null,
                'distance_override_meters' => isset($validated['distance_override_km'])
                    ? (int) round(((float) $validated['distance_override_km']) * 1000)
                    : null,
                'status' => 'draft',
                'created_by' => $user->getKey(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $finalizedSession = $finalizeSessionService->finalize($session);
            $sessionCostCents = isset($validated['session_cost']) && (float) $validated['session_cost'] > 0
                ? (int) round(((float) $validated['session_cost']) * 100)
                : null;

            if ($sessionCostCents !== null) {
                Expense::create([
                    'amount_cents' => $sessionCostCents,
                    'currency' => 'EUR',
                    'category' => 'track',
                    'description' => $validated['cost_description']
                        ?? __('Track session').': '.$version->configuration->vehicle->name.' · '.ucfirst($validated['session_type']),
                    'occurred_at' => Carbon::parse($validated['started_at']),
                    'related_type' => 'session',
                    'related_id' => $finalizedSession->getKey(),
                    'created_by' => $user->getKey(),
                ]);
            }

            return $finalizedSession;
        });

        $schedules = MaintenanceSchedule::query()
            ->with(['tracker.component', 'tracker.metric'])
            ->where('is_active', true)
            ->get();
        $health = $healthService->snapshot($schedules);

        return to_route('demo.sessions', ['recorded' => $session->getKey()])
            ->with('status', __('Session recorded, component usage updated and session cost linked to expenses.'))
            ->with('maintenance_attention', $health['summary']['attention']);
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
