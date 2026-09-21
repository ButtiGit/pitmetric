<?php

namespace App\Services;

use App\Models\ComponentTracker;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\Workspace;

class MaintenancePageService
{
    public function __construct(private readonly MaintenanceHealthService $healthService) {}

    /** @return array<string, mixed> */
    public function data(Workspace $workspace): array
    {
        $trackers = ComponentTracker::query()
            ->whereHas('component', fn ($query) => $query->whereNull('components.deleted_at')->where('status', 'active')->where('workspace_id', $workspace->getKey()))
            ->with(['component.type', 'metric'])
            ->orderBy('component_id')
            ->get();

        $schedules = MaintenanceSchedule::query()
            ->with(['tracker.component', 'tracker.metric'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $health = $this->healthService->snapshot($schedules);

        $workOrders = MaintenanceWorkOrder::query()
            ->with(['schedule.tracker.component', 'schedule.tracker.metric', 'assignee'])
            ->whereIn('status', MaintenanceWorkOrder::OPEN_STATUSES)
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->orderBy('created_at')
            ->get();

        return [
            'trackers' => $trackers,
            'schedules' => $schedules,
            'archivedSchedules' => MaintenanceSchedule::query()
                ->with(['tracker.component', 'tracker.metric'])
                ->where('is_active', false)
                ->orderBy('name')
                ->get(),
            'states' => $health['states'],
            'summary' => $health['summary'],
            'workOrders' => $workOrders,
            'workSummary' => [
                'open' => $workOrders->count(),
                'in_progress' => $workOrders->where('status', 'in_progress')->count(),
                'blocked' => $workOrders->where('status', 'blocked')->count(),
            ],
            'activeWorkOrderScheduleIds' => $workOrders->pluck('maintenance_schedule_id')->all(),
            'members' => $workspace->users()
                ->wherePivot('status', 'active')
                ->orderBy('name')
                ->get(),
            'records' => MaintenanceRecord::query()
                ->with(['component', 'schedule.tracker.metric'])
                ->latest('performed_at')
                ->limit(25)
                ->get(),
        ];
    }
}
