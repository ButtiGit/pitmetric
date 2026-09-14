<?php

namespace App\Http\Controllers;

use App\Models\CircuitLayout;
use App\Models\ConfigurationVersion;
use App\Models\Driver;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RaceEventController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $this->hasDatabaseAccess($user)) {
            return view('events.demo');
        }

        if (! $workspaceContext->isEventsReady()) {
            return view('garage.unavailable');
        }

        $workspace = $workspaceContext->personal($user);

        return view('events.index', [
            'events' => RaceEvent::query()
                ->with(['circuitLayout.circuit'])
                ->withCount(['entries', 'sessions', 'tasks'])
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->get(),
            'drivers' => Driver::query()->where('status', 'active')->orderBy('display_name')->get(),
            'layouts' => CircuitLayout::query()
                ->whereHas('circuit', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
                ->with('circuit')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);
        $validated = $request->validate([
            'circuit_layout_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:140'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'championship' => ['nullable', 'string', 'max:120'],
            'round_label' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $layout = CircuitLayout::query()
            ->whereKey((int) $validated['circuit_layout_id'])
            ->whereHas('circuit', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
            ->firstOrFail();

        $event = RaceEvent::create([
            'circuit_layout_id' => $layout->getKey(),
            'name' => $validated['name'],
            'start_date' => Carbon::parse($validated['start_date'])->startOfDay(),
            'end_date' => Carbon::parse($validated['end_date'])->startOfDay(),
            'status' => 'planned',
            'championship' => $validated['championship'] ?? null,
            'round_label' => $validated['round_label'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->getKey(),
        ]);

        return to_route('events.show', $event)->with('status', __('Race weekend created.'));
    }

    public function show(Request $request, RaceEvent $raceEvent, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        abort_unless($this->hasDatabaseAccess($user), 403);
        abort_unless($workspaceContext->isEventsReady(), 503);
        Gate::authorize('view', $raceEvent);

        $workspace = $workspaceContext->personal($user);
        $raceEvent->load([
            'circuitLayout.circuit',
            'entries.driver',
            'entries.vehicle',
            'entries.configurationVersion.configuration',
            'sessions.vehicle',
            'sessions.configurationVersion.configuration',
            'sessions.eventEntry.driver',
            'sessions.usageValues.metric',
            'tasks.eventEntry.driver',
            'tasks.eventEntry.vehicle',
            'eventNotes.eventEntry.driver',
            'expenses',
            'maintenanceRecords.component',
        ]);

        return view('events.show', [
            'event' => $raceEvent,
            'drivers' => Driver::query()->where('status', 'active')->orderBy('display_name')->get(),
            'vehicles' => Vehicle::query()->where('status', 'active')->orderBy('name')->get(),
            'versions' => ConfigurationVersion::query()
                ->whereHas('configuration', fn ($query) => $query->where('workspace_id', $workspace->getKey())->where('status', 'active'))
                ->with(['configuration.vehicle', 'components'])
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function updateStatus(Request $request, RaceEvent $raceEvent): RedirectResponse
    {
        Gate::authorize('update', $raceEvent);

        $validated = $request->validate([
            'status' => ['required', 'in:planned,active,completed,cancelled'],
        ]);

        $raceEvent->update(['status' => $validated['status']]);

        return to_route('events.show', $raceEvent)->with('status', __('Race weekend status updated.'));
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
