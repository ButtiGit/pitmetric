<?php

namespace App\Http\Controllers;

use App\Models\CircuitLayout;
use App\Models\ConfigurationVersion;
use App\Models\Driver;
use App\Models\MaintenanceSchedule;
use App\Models\RaceEvent;
use App\Models\TechnicalSetup;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\MaintenanceHealthService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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
            ->where('is_active', true)
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

    public function show(
        Request $request,
        RaceEvent $raceEvent,
        WorkspaceContext $workspaceContext,
        MaintenanceHealthService $healthService,
    ): View {
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
            'entries.configurationVersion.components',
            'sessions.vehicle',
            'sessions.configurationVersion.configuration',
            'sessions.configurationVersion.components',
            'sessions.eventEntry.driver',
            'sessions.usageValues.metric',
            'sessions.setupSnapshot.technicalSetup',
            'scheduleItems.eventEntry.driver',
            'scheduleItems.eventEntry.vehicle',
            'scheduleItems.session',
            'tasks.eventEntry.driver',
            'tasks.eventEntry.vehicle',
            'eventNotes.eventEntry.driver',
            'expenses',
            'maintenanceRecords.component',
        ]);

        $componentIds = $raceEvent->entries
            ->flatMap(fn ($entry) => $entry->configurationVersion->components->pluck('id'))
            ->merge($raceEvent->sessions->flatMap(fn ($session) => $session->configurationVersion->components->pluck('id')))
            ->unique()
            ->values();

        $maintenanceSchedules = MaintenanceSchedule::query()
            ->with(['tracker.component', 'tracker.metric'])
            ->where('is_active', true)
            ->when(
                $componentIds->isNotEmpty(),
                fn ($query) => $query->whereHas('tracker', fn ($tracker) => $tracker->whereIn('component_id', $componentIds)),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->orderBy('name')
            ->get();
        $maintenanceHealth = $healthService->snapshot($maintenanceSchedules);
        $attentionMaintenance = $maintenanceSchedules->filter(function (MaintenanceSchedule $schedule) use ($maintenanceHealth): bool {
            $status = $maintenanceHealth['states'][$schedule->getKey()]['status'] ?? 'untracked';

            return in_array($status, ['due_soon', 'overdue'], true);
        });

        $nextScheduleItem = $raceEvent->scheduleItems
            ->reject(fn ($item) => in_array($item->status, ['completed', 'cancelled'], true))
            ->sortBy(function ($item): string {
                $rank = match ($item->status) {
                    'live' => 0,
                    'ready' => 1,
                    default => 2,
                };

                return $rank.'-'.Carbon::parse($item->starts_at)->format('YmdHis');
            })
            ->first();

        return view('events.show', [
            'event' => $raceEvent,
            'drivers' => Driver::query()->where('status', 'active')->orderBy('display_name')->get(),
            'vehicles' => Vehicle::query()->where('status', 'active')->orderBy('name')->get(),
            'versions' => ConfigurationVersion::query()
                ->whereHas('configuration', fn ($query) => $query->whereNull('configurations.deleted_at')->where('status', 'active')->whereHas('vehicle', fn ($vehicle) => $vehicle->whereNull('vehicles.deleted_at')->where('status', 'active'))->where('workspace_id', $workspace->getKey()))
                ->with(['configuration.vehicle', 'components'])
                ->orderByDesc('id')
                ->get(),
            'setups' => TechnicalSetup::query()
                ->with('vehicle')
                ->where('status', 'active')
                ->orderBy('vehicle_id')
                ->orderBy('name')
                ->get(),
            'maintenanceSchedules' => $maintenanceSchedules,
            'maintenanceStates' => $maintenanceHealth['states'],
            'maintenanceSummary' => $maintenanceHealth['summary'],
            'attentionMaintenance' => $attentionMaintenance,
            'nextScheduleItem' => $nextScheduleItem,
        ]);
    }

    public function update(Request $request, RaceEvent $raceEvent): RedirectResponse
    {
        Gate::authorize('update', $raceEvent);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'championship' => ['nullable', 'string', 'max:120'],
            'round_label' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
        $start = Carbon::parse($validated['start_date'])->startOfDay();
        $end = Carbon::parse($validated['end_date'])->endOfDay();

        if ($raceEvent->sessions()->where(fn ($query) => $query->where('started_at', '<', $start)->orWhere('started_at', '>', $end))->exists()
            || $raceEvent->scheduleItems()->where(fn ($query) => $query->where('starts_at', '<', $start)->orWhere('starts_at', '>', $end))->exists()) {
            throw ValidationException::withMessages([
                'start_date' => __('Keep the dates of existing sessions and scheduled activities inside the weekend.'),
            ]);
        }

        $raceEvent->update($validated);

        return to_route('events.show', $raceEvent)->with('status', __('Race weekend updated.'));
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
