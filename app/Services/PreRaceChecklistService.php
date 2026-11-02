<?php

namespace App\Services;

use App\Models\EventEntry;
use App\Models\EventTask;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;

class PreRaceChecklistService
{
    public function __construct(
        private readonly VehicleReadinessService $readiness,
        private readonly ConfigurationPhysicalStateService $physicalState,
    ) {}

    /** @return array<string, mixed> */
    public function data(EventEntry $entry): array
    {
        $entry->load(['vehicle.componentInstallations' => fn ($query) => $query->whereNull('removed_at')->with('component')->orderBy('id'), 'driver', 'raceEvent', 'configurationVersion.configuration']);
        $workspace = Workspace::query()->findOrFail($entry->workspace_id);
        $vehicleId = (int) $entry->vehicle_id;
        $readiness = $this->readiness->data($workspace, ['vehicle_id' => $vehicleId, 'event_id' => $entry->event_id], 1);
        $tasks = EventTask::query()->where('workspace_id', $workspace->id)->where('event_id', $entry->event_id)
            ->where('status', '!=', 'done')->where(fn (Builder $q) => $q->whereNull('event_entry_id')
            ->orWhereHas('eventEntry', fn (Builder $entryQuery) => $entryQuery->where('vehicle_id', $vehicleId)))
            ->with(['assignee', 'eventEntry.driver'])->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->orderBy('due_at')->orderBy('id')->get();

        return [
            'entry' => $entry, 'vehicle' => $entry->vehicle, 'card' => $readiness['cards'][0] ?? null,
            'assignedAligned' => $this->physicalState->alignedVersions($workspace, [$vehicleId])->contains('id', $entry->configuration_version_id),
            'tasks' => $tasks->whereNotNull('event_entry_id'), 'generalTasks' => $tasks->whereNull('event_entry_id'),
        ];
    }
}
