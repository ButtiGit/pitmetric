<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\EventEntry;
use App\Models\EventTask;
use App\Models\MaintenanceRecord;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Services\MaintenanceReportService;
use App\Services\OpenWorklistService;
use App\Services\PreRaceChecklistService;
use App\Services\PrintDocumentService;
use App\Services\WeekendReportService;
use App\Services\WorkspaceContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PrintDocumentController extends Controller
{
    public function weekend(RaceEvent $raceEvent, WeekendReportService $reports): Response
    {
        Gate::authorize('view', $raceEvent);

        return $this->document('weekend', $raceEvent, route('insights.events.report', $raceEvent), ['report' => $reports->data($raceEvent)]);
    }

    public function maintenance(Request $request, WorkspaceContext $context, MaintenanceReportService $reports): Response
    {
        Gate::authorize('viewAny', MaintenanceRecord::class);
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $workspace = $context->personal($user);
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'component_id' => ['nullable', 'integer', 'min:1'], 'vehicle_id' => ['nullable', 'integer', 'min:1'], 'event_id' => ['nullable', 'integer', 'min:1'],
        ]);
        foreach (['component_id' => Component::class, 'vehicle_id' => Vehicle::class, 'event_id' => RaceEvent::class] as $key => $model) {
            if (! empty($filters[$key])) {
                $model::withTrashed()->findOrFail($request->integer($key));
            }
        }

        return $this->document('maintenance', $workspace, route('maintenance.index'), [
            ...$reports->data($workspace, $filters), 'filters' => $filters,
            'components' => Component::withTrashed()->orderBy('name')->get(),
            'vehicles' => Vehicle::withTrashed()->orderBy('name')->get(),
            'events' => RaceEvent::withTrashed()->orderByDesc('start_date')->orderBy('name')->get(),
        ]);
    }

    public function worklist(Request $request, WorkspaceContext $context, OpenWorklistService $worklists): Response
    {
        Gate::authorize('viewAny', EventTask::class);
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $workspace = $context->personal($user);
        $filters = $request->validate([
            'event_id' => ['nullable', 'integer', 'min:1'], 'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'assigned_to' => ['nullable', 'integer', 'min:1'], 'mine' => ['nullable', 'in:1'], 'q' => ['nullable', 'string', 'max:120'],
        ]);
        if (empty($filters['assigned_to']) && ($filters['mine'] ?? null) === '1') {
            $filters['assigned_to'] = $user->id;
        }
        $event = empty($filters['event_id']) ? null : RaceEvent::query()->findOrFail($request->integer('event_id'));
        $vehicle = empty($filters['vehicle_id']) ? null : Vehicle::query()->findOrFail($request->integer('vehicle_id'));
        $members = $workspace->users()->orderBy('name')->get(['users.id', 'users.name']);
        $assignee = empty($filters['assigned_to']) ? null : $members->firstWhere('id', $filters['assigned_to']);
        abort_if(! empty($filters['assigned_to']) && $assignee === null, 404);

        return $this->document('worklist', $workspace, route('tasks.index', $filters), [
            ...$worklists->data($workspace, $filters), 'filters' => $filters, 'members' => $members,
            'eventLabel' => $event?->name, 'vehicleLabel' => $vehicle?->name, 'assigneeLabel' => $assignee?->name,
        ]);
    }

    public function checklist(EventEntry $eventEntry, PreRaceChecklistService $checklists): Response
    {
        Gate::authorize('view', $eventEntry);

        return $this->document('checklist', $eventEntry, route('events.show', $eventEntry->event_id), $checklists->data($eventEntry));
    }

    public function vehicle(Vehicle $vehicle): Response
    {
        Gate::authorize('view', $vehicle);
        $vehicle->load(['componentInstallations' => fn ($query) => $query->whereNull('removed_at')->with('component.type')->orderBy('position_or_role')->orderBy('id')]);

        return $this->document('vehicle', $vehicle, route('garage.index'), ['vehicle' => $vehicle]);
    }

    public function setup(TechnicalSetup $technicalSetup, PrintDocumentService $documents): Response
    {
        Gate::authorize('view', $technicalSetup);
        $technicalSetup->load('vehicle');
        $values = $technicalSetup->getAttribute('values');

        return $this->document('setup', $technicalSetup, route('setups.index'), [
            'setup' => $technicalSetup, 'vehicle' => $technicalSetup->vehicle,
            'setupRows' => $documents->setupRows(is_array($values) ? $values : [], $technicalSetup->vehicle?->category === 'kart'),
        ]);
    }

    public function session(Session $session, PrintDocumentService $documents): Response
    {
        return $this->sessionDocument($session, $documents, false);
    }

    public function tyres(Session $session, PrintDocumentService $documents): Response
    {
        abort_unless($session->vehicle?->category === 'kart', 404);

        return $this->sessionDocument($session, $documents, true);
    }

    private function sessionDocument(Session $session, PrintDocumentService $documents, bool $tyresOnly): Response
    {
        Gate::authorize('view', $session);
        $session->load(['vehicle', 'configurationVersion.configuration', 'eventEntry.driver', 'raceEvent.circuitLayout.circuit', 'circuitLayout.circuit', 'setupSnapshot', 'tyreReadings.creator', 'weatherObservations',
            'timingLaps' => fn ($query) => $query->orderBy('lap_number')->orderBy('id')]);
        $bestLap = $session->timingLaps->where('is_valid', true)->sortBy('lap_time_ms')->first();
        $values = $session->setupSnapshot->values ?? [];

        return $this->document($tyresOnly ? 'tyres' : 'session', $session, route('sessions.index').'#session-'.$session->id, [
            'session' => $session, 'vehicle' => $session->vehicle, 'documents' => $documents,
            'bestLap' => $bestLap, 'bestTime' => $documents->lapTime($bestLap?->lap_time_ms),
            'setupRows' => $documents->setupRows(is_array($values) ? $values : [], $session->vehicle?->category === 'kart'),
            'weatherCards' => $documents->weather($session),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function document(string $kind, Model $record, string $backUrl, array $data): Response
    {
        return response()->view('prints.'.$kind, [
            ...$data, 'kind' => $kind, 'reference' => '#'.$record->getKey(),
            'workspace' => $record instanceof Workspace ? $record : Workspace::query()->findOrFail($record->getAttribute('workspace_id')),
            'generatedAt' => now()->timezone('Europe/Rome'), 'backUrl' => $backUrl,
        ])->header('Cache-Control', 'private, no-store');
    }
}
