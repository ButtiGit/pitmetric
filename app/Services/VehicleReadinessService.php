<?php

namespace App\Services;

use App\Models\Component;
use App\Models\EventEntry;
use App\Models\EventTask;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\Vehicle;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class VehicleReadinessService
{
    public function __construct(
        private readonly ComponentUsageCalculator $usage,
        private readonly ConfigurationPhysicalStateService $physicalState,
    ) {}

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function data(Workspace $workspace, array $filters): array
    {
        $vehicles = Vehicle::query()->where('workspace_id', $workspace->getKey())->where('status', 'active')
            ->when($filters['vehicle_id'] ?? null, fn (Builder $q, $id) => $q->whereKey($id))
            ->when($filters['event_id'] ?? null, fn (Builder $q, $id) => $q->whereIn('id', EventEntry::query()->select('vehicle_id')->where('event_id', $id)))
            ->with(['componentInstallations' => fn ($q) => $q->whereNull('removed_at')
                ->with(['component.trackers' => fn ($trackers) => $trackers->where('is_active', true)])])
            ->orderBy('name')->orderBy('id')->paginate(12, ['*'], 'readiness_page')->withQueryString();
        $ids = array_values($vehicles->pluck('id')->map(fn ($id): int => (int) $id)->all());
        $componentMap = [];
        foreach ($vehicles as $vehicle) {
            foreach ($vehicle->componentInstallations as $installation) {
                $component = $installation->component;
                if ($component instanceof Component && (int) $component->workspace_id === (int) $workspace->getKey()) {
                    $componentMap[$component->id] = $component;
                }
            }
        }
        $components = new Collection($componentMap);
        $trackers = $components->flatMap(fn (Component $component) => $component->trackers)->unique('id')->values();
        $usage = $this->usage->snapshot($trackers);
        $schedules = MaintenanceSchedule::query()->where('workspace_id', $workspace->getKey())
            ->where('is_active', true)->whereIn('component_tracker_id', $trackers->pluck('id'))->get()->groupBy('component_tracker_id');
        $health = [];
        foreach ($components as $component) {
            $counts = ['overdue' => 0, 'due_soon' => 0, 'untracked' => 0];
            if ($component->trackers->isEmpty() || $component->trashed() || $component->status !== 'active') {
                $counts['untracked']++;
            }
            foreach ($component->trackers as $tracker) {
                $rules = $schedules->get($tracker->id);
                if ($rules === null || $rules->isEmpty()) {
                    $status = $this->usage->statusForUsage($tracker, $usage[$tracker->id]['used']);
                    if (isset($counts[$status])) {
                        $counts[$status]++;
                    }
                } else {
                    foreach ($rules as $schedule) {
                        $status = $this->usage->statusForUsage($tracker, $usage[$tracker->id]['used'], $schedule->interval_value, $schedule->warning_value);
                        if (isset($counts[$status])) {
                            $counts[$status]++;
                        }
                    }
                }
            }
            $health[$component->id] = $counts;
        }
        $orders = MaintenanceWorkOrder::query()->where('workspace_id', $workspace->getKey())
            ->whereIn('status', MaintenanceWorkOrder::OPEN_STATUSES)
            ->whereHas('schedule.tracker', fn (Builder $q) => $q->whereIn('component_id', $components->pluck('id')))
            ->with(['schedule.tracker', 'assignee'])
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->orderBy('id')->get()->groupBy(fn (MaintenanceWorkOrder $order) => $order->schedule->tracker->component_id);
        $entries = EventEntry::query()->where('workspace_id', $workspace->getKey())->whereIn('vehicle_id', $ids)
            ->when($filters['event_id'] ?? null, fn (Builder $q, $id) => $q->where('event_id', $id))
            ->withCount([
                'tasks as open_tasks' => fn (Builder $q) => $q->where('status', '!=', 'done'),
                'tasks as urgent_tasks' => fn (Builder $q) => $q->where('status', '!=', 'done')
                    ->where(fn (Builder $urgent) => $urgent->whereIn('priority', ['high', 'critical'])->orWhere('due_at', '<', now())),
            ])->get()->groupBy('vehicle_id');
        $configurations = $this->physicalState->alignedVersions($workspace, $ids)->groupBy('configuration.vehicle_id');
        $cards = [];
        foreach ($vehicles as $vehicle) {
            $componentIds = $vehicle->componentInstallations->pluck('component_id')->unique();
            $counts = ['overdue' => 0, 'due_soon' => 0, 'untracked' => 0];
            $details = [];
            $vehicleOrders = collect();
            foreach ($componentIds as $componentId) {
                $componentHealth = $health[$componentId] ?? ['overdue' => 0, 'due_soon' => 0, 'untracked' => 1];
                foreach ($counts as $key => $count) {
                    $counts[$key] += $componentHealth[$key];
                }
                if (array_sum($componentHealth) > 0) {
                    $component = $componentMap[$componentId] ?? null;
                    $details[] = ['name' => $component instanceof Component ? $component->name : __('readiness.component_unavailable'), ...$componentHealth];
                }
                $vehicleOrders = $vehicleOrders->merge($orders->get($componentId, collect()));
            }
            $vehicleEntries = $entries->get($vehicle->id, collect());
            $openTasks = (int) $vehicleEntries->sum('open_tasks');
            $urgentTasks = (int) $vehicleEntries->sum('urgent_tasks');
            $urgentOrders = $vehicleOrders->filter(fn (MaintenanceWorkOrder $order): bool => $order->status === 'blocked'
                || in_array($order->priority, ['high', 'critical'], true) || ($order->due_at !== null && Carbon::parse($order->due_at)->isPast()))->count();
            $configuration = $configurations->get($vehicle->id)?->first();
            $state = match (true) {
                $counts['overdue'] > 0 || $urgentOrders > 0 || $urgentTasks > 0 => 'attention',
                $openTasks > 0 || $vehicleOrders->isNotEmpty() || $counts['due_soon'] > 0 => 'pending',
                $componentIds->isEmpty() || $configuration === null || $counts['untracked'] > 0 => 'incomplete',
                default => 'clear',
            };
            $cards[] = [
                'vehicle' => $vehicle, 'state' => $state, 'configuration' => $configuration,
                'component_count' => $componentIds->count(), 'open_tasks' => $openTasks, 'urgent_tasks' => $urgentTasks,
                'work_orders' => $vehicleOrders, 'urgent_orders' => $urgentOrders, 'health' => $counts, 'details' => $details,
            ];
        }

        return [
            'vehicles' => $vehicles, 'cards' => $cards,
            'general_tasks' => EventTask::query()->where('workspace_id', $workspace->getKey())
                ->whereNull('event_entry_id')->where('status', '!=', 'done')
                ->when($filters['event_id'] ?? null, fn (Builder $q, $id) => $q->where('event_id', $id))->count(),
        ];
    }
}
