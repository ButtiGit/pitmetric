<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\EventEntry;
use App\Models\EventTask;
use App\Models\MaintenanceWorkOrder;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;

class TeamTaskBoardService
{
    public function __construct(private readonly VehicleReadinessService $vehicleReadiness) {}

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function data(Workspace $workspace, User $user, array $filters): array
    {
        $query = EventTask::query()->where('workspace_id', $workspace->getKey())
            ->when($filters['event_id'] ?? null, fn (Builder $q, $id) => $q->where('event_id', $id))
            ->when($filters['vehicle_id'] ?? null, fn (Builder $q, $id) => $q->whereHas('eventEntry', fn (Builder $entry) => $entry->where('vehicle_id', $id)))
            ->when(($filters['mine'] ?? null) === '1', fn (Builder $q) => $q->where('assigned_to', $user->getKey()))
            ->when($filters['q'] ?? null, fn (Builder $q, $search) => $q->where('title', 'like', '%'.$search.'%'));

        $counts = [
            'urgent' => $this->bucket(clone $query, 'urgent')->count(),
            'todo' => (clone $query)->where('status', 'todo')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'done' => (clone $query)->where('status', 'done')->count(),
        ];
        $bucket = $filters['bucket'] ?? ($counts['urgent'] > 0 ? 'urgent' : 'todo');
        $tasks = $this->bucket($query, $bucket)
            ->with(['raceEvent', 'eventEntry.vehicle', 'eventEntry.driver', 'assignee', 'completedBy'])
            ->when($bucket === 'done', fn (Builder $q) => $q->orderByDesc('completed_at'))
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')->orderBy('due_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();
        $history = AuditLog::query()->where('workspace_id', $workspace->getKey())
            ->where('subject_type', EventTask::class)->whereIn('subject_id', $tasks->pluck('id')->all())
            ->with('user')->latest('id')->limit(500)->get()->groupBy('subject_id');
        $workOrders = MaintenanceWorkOrder::query()->where('workspace_id', $workspace->getKey())
            ->whereIn('status', MaintenanceWorkOrder::OPEN_STATUSES);

        return [
            'workspace' => $workspace, 'tasks' => $tasks, 'counts' => $counts, 'bucket' => $bucket, 'filters' => $filters,
            'taskHistory' => $history,
            'vehicleReadiness' => $this->vehicleReadiness->data($workspace, $filters),
            'events' => RaceEvent::query()->latest('start_date')->get(['id', 'name', 'status']),
            'entries' => EventEntry::query()->with(['driver', 'vehicle', 'raceEvent'])->orderBy('event_id')->get(),
            'vehicles' => Vehicle::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'members' => $workspace->users()->wherePivot('status', 'active')->orderBy('name')->get(['users.id', 'users.name']),
            'maintenance' => [
                'open' => (clone $workOrders)->count(),
                'blocked' => (clone $workOrders)->where('status', 'blocked')->count(),
                'overdue' => (clone $workOrders)->where('due_at', '<', now())->count(),
            ],
        ];
    }

    /** @param Builder<EventTask> $query
     * @return Builder<EventTask>
     */
    private function bucket(Builder $query, string $bucket): Builder
    {
        if ($bucket !== 'urgent') {
            return $query->where('status', $bucket);
        }

        return $query->where('status', '!=', 'done')->where(fn (Builder $q) => $q
            ->whereIn('priority', ['high', 'critical'])->orWhere('due_at', '<', now()));
    }
}
