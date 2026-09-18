<?php

namespace App\Http\Controllers;

use App\Models\CircuitLayout;
use App\Models\ConfigurationVersion;
use App\Models\EventEntry;
use App\Models\EventScheduleItem;
use App\Models\Expense;
use App\Models\MaintenanceSchedule;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\User;
use App\Services\FinalizeSessionService;
use App\Services\MaintenanceHealthService;
use App\Services\SetupSnapshotService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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

        if (! $workspaceContext->isTechnicalSetupReady()) {
            return view('garage.unavailable');
        }

        $workspace = $workspaceContext->personal($user);
        $sessions = Session::query()
            ->with([
                'vehicle',
                'configurationVersion.configuration',
                'circuitLayout.circuit',
                'usageValues.metric',
                'raceEvent',
                'eventEntry.driver',
                'setupSnapshot.technicalSetup',
            ])
            ->latest('started_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

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
                ->whereHas('configuration', fn ($query) => $query->whereNull('configurations.deleted_at')->where('status', 'active')->whereHas('vehicle', fn ($vehicle) => $vehicle->whereNull('vehicles.deleted_at')->where('status', 'active'))->where('workspace_id', $workspace->getKey()))
                ->with(['configuration.vehicle', 'components'])
                ->orderByDesc('id')
                ->get(),
            'setups' => TechnicalSetup::query()
                ->with('vehicle')
                ->whereHas('vehicle', fn ($query) => $query->whereNull('vehicles.deleted_at')->where('status', 'active'))
                ->where('status', 'active')
                ->orderBy('vehicle_id')
                ->orderBy('name')
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
                'technical_setup_id' => $lastSession?->setupSnapshot?->technical_setup_id,
                'circuit_layout_id' => $lastSession?->circuit_layout_id,
                'session_type' => $lastSession->session_type ?? 'practice',
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
        SetupSnapshotService $snapshotService,
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

        $raceEvent = null;
        $eventEntry = null;
        $scheduleItem = null;

        if (! empty($validated['event_id'])) {
            $raceEvent = RaceEvent::query()->whereKey((int) $validated['event_id'])->firstOrFail();
            $eventEntry = EventEntry::query()
                ->whereKey((int) $validated['event_entry_id'])
                ->where('event_id', $raceEvent->getKey())
                ->firstOrFail();

            $startedAt = Carbon::parse($validated['started_at']);
            $eventStartsAt = Carbon::parse($raceEvent->start_date)->startOfDay();
            $eventEndsAt = Carbon::parse($raceEvent->end_date)->endOfDay();

            if ($startedAt->lt($eventStartsAt) || $startedAt->gt($eventEndsAt)) {
                throw ValidationException::withMessages([
                    'started_at' => __('The session date must be inside the race weekend.'),
                ]);
            }

            if (! empty($validated['schedule_item_id'])) {
                $scheduleItem = EventScheduleItem::query()
                    ->whereKey((int) $validated['schedule_item_id'])
                    ->where('event_id', $raceEvent->getKey())
                    ->firstOrFail();

                if ($scheduleItem->session_id !== null) {
                    throw ValidationException::withMessages([
                        'schedule_item_id' => __('This scheduled session has already been recorded.'),
                    ]);
                }

                if ($scheduleItem->event_entry_id !== null && (int) $scheduleItem->event_entry_id !== (int) $eventEntry->getKey()) {
                    throw ValidationException::withMessages([
                        'event_entry_id' => __('The scheduled session belongs to a different event entry.'),
                    ]);
                }

                if ($scheduleItem->session_type !== $validated['session_type']) {
                    throw ValidationException::withMessages([
                        'session_type' => __('The recorded session type must match the Trackside schedule item.'),
                    ]);
                }
            }
        }

        $version = ConfigurationVersion::query()
            ->whereKey((int) $validated['configuration_version_id'])
            ->whereHas('configuration', function ($query) use ($workspace, $eventEntry): void {
                $query->whereNull('configurations.deleted_at')->where('status', 'active')->whereHas('vehicle', fn ($vehicle) => $vehicle->whereNull('vehicles.deleted_at')->where('status', 'active'))->where('workspace_id', $workspace->getKey());

                if ($eventEntry instanceof EventEntry) {
                    $query->where('vehicle_id', $eventEntry->vehicle_id);
                }
            })
            ->with('configuration.vehicle')
            ->firstOrFail();

        $technicalSetup = null;

        if (! empty($validated['technical_setup_id'])) {
            $technicalSetup = TechnicalSetup::query()
                ->whereKey((int) $validated['technical_setup_id'])
                ->where('vehicle_id', $version->configuration->vehicle_id)
                ->where('status', 'active')
                ->firstOrFail();
        } else {
            $technicalSetup = TechnicalSetup::query()
                ->where('vehicle_id', $version->configuration->vehicle_id)
                ->where('status', 'active')
                ->latest('updated_at')
                ->latest('id')
                ->first();
        }

        $layoutId = $raceEvent?->circuit_layout_id;

        if ($layoutId === null && ! empty($validated['circuit_layout_id'])) {
            $layoutId = CircuitLayout::query()
                ->whereKey((int) $validated['circuit_layout_id'])
                ->where('is_active', true)
                ->whereHas('circuit', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
                ->value('id');

            abort_if($layoutId === null, 404);
        }

        $session = DB::transaction(function () use ($validated, $version, $layoutId, $user, $finalizeSessionService, $snapshotService, $technicalSetup, $raceEvent, $eventEntry, $scheduleItem): Session {
            if ($scheduleItem instanceof EventScheduleItem) {
                $scheduleItem = EventScheduleItem::query()->whereKey($scheduleItem->getKey())->lockForUpdate()->firstOrFail();

                if ($scheduleItem->session_id !== null) {
                    throw ValidationException::withMessages([
                        'schedule_item_id' => __('This scheduled session has already been recorded.'),
                    ]);
                }
            }

            $session = Session::create([
                'event_id' => $raceEvent?->getKey(),
                'event_entry_id' => $eventEntry?->getKey(),
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
            $snapshotService->capture($finalizedSession, $technicalSetup, $user);

            $sessionCostCents = isset($validated['session_cost']) && (float) $validated['session_cost'] > 0
                ? (int) round(((float) $validated['session_cost']) * 100)
                : null;

            if ($sessionCostCents !== null) {
                Expense::create([
                    'event_id' => $raceEvent?->getKey(),
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

            if ($scheduleItem instanceof EventScheduleItem) {
                $scheduleItem->update([
                    'session_id' => $finalizedSession->getKey(),
                    'status' => 'completed',
                ]);
            }

            if ($raceEvent instanceof RaceEvent && $raceEvent->status === 'planned') {
                $raceEvent->update(['status' => 'active']);
            }

            return $finalizedSession;
        });

        $schedules = MaintenanceSchedule::query()
            ->with(['tracker.component', 'tracker.metric'])
            ->where('is_active', true)
            ->get();
        $health = $healthService->snapshot($schedules);

        if ($raceEvent instanceof RaceEvent) {
            return to_route('events.show', ['raceEvent' => $raceEvent, 'recorded' => $session->getKey()])
                ->with('status', __('Event session recorded, usage updated and immutable setup snapshot captured.'))
                ->with('maintenance_attention', $health['summary']['attention']);
        }

        return to_route('sessions.index', ['recorded' => $session->getKey()])
            ->with('status', __('Session recorded, usage updated and immutable setup snapshot captured.'))
            ->with('maintenance_attention', $health['summary']['attention']);
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
