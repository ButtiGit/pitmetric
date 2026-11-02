<?php

namespace App\Http\Controllers;

use App\Models\EventEntry;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Services\PreRaceChecklistService;
use App\Services\PrintDocumentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PrintDocumentController extends Controller
{
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
            'workspace' => Workspace::query()->findOrFail($record->getAttribute('workspace_id')),
            'generatedAt' => now()->timezone('Europe/Rome'), 'backUrl' => $backUrl,
        ])->header('Cache-Control', 'private, no-store');
    }
}
