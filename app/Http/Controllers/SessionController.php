<?php

namespace App\Http\Controllers;

use App\Models\CircuitLayout;
use App\Models\ConfigurationVersion;
use App\Models\Session;
use App\Models\User;
use App\Services\FinalizeSessionService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
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
            'sessions' => Session::query()
                ->with(['vehicle', 'configurationVersion.configuration', 'circuitLayout.circuit', 'usageValues.metric'])
                ->latest('started_at')
                ->latest('id')
                ->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $workspaceContext, FinalizeSessionService $finalizeSessionService): RedirectResponse
    {
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
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $version = ConfigurationVersion::query()
            ->whereKey($validated['configuration_version_id'])
            ->whereHas('configuration', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
            ->with('configuration')
            ->firstOrFail();

        $layoutId = null;

        if (! empty($validated['circuit_layout_id'])) {
            $layoutId = CircuitLayout::query()
                ->whereKey($validated['circuit_layout_id'])
                ->whereHas('circuit', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
                ->value('id');

            abort_if($layoutId === null, 404);
        }

        DB::transaction(function () use ($validated, $version, $layoutId, $user, $finalizeSessionService): void {
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

            $finalizeSessionService->finalize($session);
        });

        return to_route('demo.sessions')->with('status', __('Session recorded and usage updated.'));
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
