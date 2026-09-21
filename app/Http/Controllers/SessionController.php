<?php

namespace App\Http\Controllers;

use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\User;
use App\Services\RecordSessionService;
use App\Services\SessionPageService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(
        Request $request,
        WorkspaceContext $workspaceContext,
        SessionPageService $pageService,
    ): View {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $this->hasDatabaseAccess($user)) {
            return view('demo.workspace', ['initialSection' => 'sessions']);
        }

        if (! $workspaceContext->isTechnicalSetupReady()) {
            return view('garage.unavailable');
        }

        $workspace = $workspaceContext->personal($user);

        return view('sessions.index', $pageService->data($request, $workspace));
    }

    public function update(Request $request, Session $session): RedirectResponse
    {
        Gate::authorize('update', $session);
        $session->update($request->validate(['notes' => ['nullable', 'string', 'max:4000']]));

        return to_route('sessions.index')->with('status', __('Session notes updated. Recorded usage is unchanged.'));
    }

    public function store(
        Request $request,
        WorkspaceContext $workspaceContext,
        RecordSessionService $recordSessionService,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);
        $validated = $request->validate([
            'event_id' => ['nullable', 'integer', 'required_with:event_entry_id,schedule_item_id'],
            'event_entry_id' => ['nullable', 'integer', 'required_with:event_id'],
            'schedule_item_id' => ['nullable', 'integer'],
            'configuration_version_id' => ['required', 'integer'],
            'technical_setup_id' => ['nullable', 'integer'],
            'circuit_layout_id' => ['nullable', 'integer'],
            'session_type' => ['required', 'in:practice,qualifying,heat,prefinal,final,race,test'],
            'started_at' => ['required', 'date'],
            'completed_laps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'duration_minutes' => ['nullable', 'numeric', 'min:0', 'max:1440'],
            'distance_override_km' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'session_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'cost_description' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $result = $recordSessionService->record($workspace, $user, $validated);
        $session = $result['session'];
        $raceEvent = $result['raceEvent'];
        $maintenanceAttention = $result['maintenanceAttention'];

        if ($raceEvent instanceof RaceEvent) {
            return to_route('events.show', ['raceEvent' => $raceEvent, 'recorded' => $session->getKey()])
                ->with('status', __('Event session recorded, usage updated and immutable setup snapshot captured.'))
                ->with('maintenance_attention', $maintenanceAttention);
        }

        return to_route('sessions.index', ['recorded' => $session->getKey()])
            ->with('status', __('Session recorded, usage updated and immutable setup snapshot captured.'))
            ->with('maintenance_attention', $maintenanceAttention);
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
