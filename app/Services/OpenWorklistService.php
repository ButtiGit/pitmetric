<?php

namespace App\Services;

use App\Models\EventEntry;
use App\Models\EventTask;
use App\Models\MaintenanceWorkOrder;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class OpenWorklistService
{
    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function data(Workspace $workspace, array $filters): array
    {
        $tasks = EventTask::query()->where('workspace_id', $workspace->id)->whereIn('status', ['todo', 'in_progress'])
            ->when($filters['event_id'] ?? null, fn (Builder $q, $id) => $q->where('event_id', $id))
            ->when($filters['vehicle_id'] ?? null, fn (Builder $q, $id) => $q->whereHas('eventEntry', fn (Builder $entry) => $entry->where('vehicle_id', $id)))
            ->when($filters['assigned_to'] ?? null, fn (Builder $q, $id) => $q->where('assigned_to', $id))
            ->when($filters['q'] ?? null, fn (Builder $q, $search) => $q->where('title', 'like', '%'.$search.'%'))
            ->with(['raceEvent', 'eventEntry.vehicle', 'assignee'])->get();
        $orders = MaintenanceWorkOrder::query()->where('workspace_id', $workspace->id)->whereIn('status', MaintenanceWorkOrder::OPEN_STATUSES)
            ->when($filters['assigned_to'] ?? null, fn (Builder $q, $id) => $q->where('assigned_to', $id))
            ->when($filters['q'] ?? null, fn (Builder $q, $search) => $q->where('title', 'like', '%'.$search.'%'))
            ->when(! empty($filters['event_id']) || ! empty($filters['vehicle_id']), fn (Builder $q) => $q->whereHas('schedule.tracker.component.activeInstallation', function (Builder $installation) use ($filters): void {
                $installation->when($filters['vehicle_id'] ?? null, fn (Builder $q, $id) => $q->where('vehicle_id', $id))
                    ->when($filters['event_id'] ?? null, fn (Builder $q, $id) => $q->whereIn('vehicle_id', EventEntry::query()->select('vehicle_id')->where('event_id', $id)));
            }))
            ->with(['assignee', 'schedule.tracker.component.activeInstallation.vehicle'])->get();
        $rows = [];
        foreach ($tasks as $task) {
            $rows[] = $this->row($task, 'task', $task->description, $task->eventEntry?->vehicle?->name, null, $task->raceEvent?->name);
        }
        foreach ($orders as $order) {
            $component = $order->schedule?->tracker?->component;
            $rows[] = $this->row($order, 'maintenance', $order->notes, $component?->activeInstallation?->vehicle?->name, $component?->name, null);
        }
        $sorted = collect($rows)->sortBy([['group_rank', 'asc'], ['priority_rank', 'asc'], ['due_rank', 'asc'], ['source', 'asc'], ['id', 'asc']])->values();

        return ['rows' => $sorted, 'groups' => $sorted->groupBy('group_rank')];
    }

    /** @return array<string, mixed> */
    private function row(Model $record, string $source, ?string $notes, ?string $vehicle, ?string $component, ?string $event): array
    {
        $date = $record->getAttribute('due_at');
        $dueAt = $date === null ? null : Carbon::parse($date);
        $overdue = $dueAt !== null && $dueAt->isPast();
        $priority = $record->getAttribute('priority');
        $status = $record->getAttribute('status');
        $urgent = $overdue || $status === 'blocked' || in_array($priority, ['high', 'critical'], true);

        return [
            'id' => $record->getKey(), 'source' => $source, 'title' => $record->getAttribute('title'), 'notes' => $notes,
            'vehicle' => $vehicle, 'component' => $component, 'event' => $event,
            'assignee' => $record->getRelation('assignee')?->getAttribute('name'), 'due_at' => $dueAt,
            'priority' => $priority, 'status' => $status, 'overdue' => $overdue,
            'group_rank' => $urgent ? 0 : ($status === 'in_progress' ? 1 : 2),
            'priority_rank' => array_search($priority, ['critical', 'high', 'normal', 'low'], true),
            'due_rank' => $dueAt?->getTimestamp() ?? PHP_INT_MAX,
        ];
    }
}
