<?php

namespace App\Services;

use App\Models\ComponentUsageEntry;
use App\Models\MaintenanceRecord;
use App\Models\TrackerResetEvent;
use App\Models\UsageMetricType;
use App\Models\Vehicle;
use App\Models\Workspace;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;

class MaintenanceReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function data(Workspace $workspace, array $filters): array
    {
        $vehicleAtTime = function (Builder $query) use ($workspace): void {
            $query->from('component_installations')->selectRaw('CASE WHEN COUNT(*) = 1 THEN MIN(vehicle_id) ELSE NULL END')
                ->where('workspace_id', $workspace->id)
                ->whereColumn('component_id', 'maintenance_records.component_id')
                ->whereColumn('installed_at', '<=', 'maintenance_records.performed_at')
                ->where(fn ($q) => $q->whereNull('removed_at')->orWhereColumn('removed_at', '>', 'maintenance_records.performed_at'));
        };
        $usage = ComponentUsageEntry::query()->whereColumn('component_tracker_id', 'report_trackers.id')
            ->whereColumn('occurred_at', '<=', 'maintenance_records.performed_at');
        $sinceService = (clone $usage)->whereNotExists(TrackerResetEvent::query()->selectRaw('1')
            ->whereColumn('component_tracker_id', 'report_trackers.id')
            ->whereColumn('reset_at', '>=', 'component_usage_entries.occurred_at')
            ->whereColumn('reset_at', '<', 'maintenance_records.performed_at'));
        $records = MaintenanceRecord::query()->select('maintenance_records.*')
            ->where('maintenance_records.workspace_id', $workspace->id)
            ->leftJoin('maintenance_schedules as report_schedules', fn (JoinClause $join) => $join
                ->on('report_schedules.id', '=', 'maintenance_records.maintenance_schedule_id')
                ->on('report_schedules.workspace_id', '=', 'maintenance_records.workspace_id'))
            ->leftJoin('component_trackers as report_trackers', fn (JoinClause $join) => $join
                ->on('report_trackers.id', '=', 'report_schedules.component_tracker_id')
                ->on('report_trackers.component_id', '=', 'maintenance_records.component_id'))
            ->selectSub($vehicleAtTime, 'report_vehicle_id')
            ->selectSub((clone $usage)->selectRaw('SUM(value)'), 'report_lifetime')
            ->selectSub($sinceService->selectRaw('SUM(value)'), 'report_since_service')
            ->selectSub(TrackerResetEvent::query()->selectRaw('COUNT(*)')->whereColumn('component_tracker_id', 'report_trackers.id')
                ->whereColumn('reset_at', 'maintenance_records.performed_at')
                ->where(fn ($q) => $q->whereNull('maintenance_record_id')->orWhereColumn('maintenance_record_id', '!=', 'maintenance_records.id')), 'report_ambiguous_reset')
            ->with(['component', 'raceEvent', 'creator', 'schedule.tracker.metric', 'resetEvents.tracker.metric',
                'workOrders' => fn ($q) => $q->where('status', 'completed')->orderBy('id')]);
        foreach (['component_id', 'event_id'] as $key) {
            if (! empty($filters[$key])) {
                $records->where('maintenance_records.'.$key, (int) $filters[$key]);
            }
        }
        if (! empty($filters['from'])) {
            $records->where('performed_at', '>=', Carbon::parse($filters['from'], 'Europe/Rome')->startOfDay()->utc());
        }
        if (! empty($filters['to'])) {
            $records->where('performed_at', '<', Carbon::parse($filters['to'], 'Europe/Rome')->startOfDay()->addDay()->utc());
        }
        if (! empty($filters['vehicle_id'])) {
            $records->getQuery()->where($vehicleAtTime, '=', (int) $filters['vehicle_id']);
        }
        $items = $records->orderByDesc('performed_at')->orderByDesc('maintenance_records.id')->get();
        $vehicles = Vehicle::withTrashed()->where('workspace_id', $workspace->id)
            ->whereIn('id', $items->pluck('report_vehicle_id')->filter()->unique())->get()->keyBy('id');

        return ['records' => $items, 'historicalVehicles' => $vehicles,
            'costTotal' => (int) $items->sum('cost_cents'), 'costCount' => $items->whereNotNull('cost_cents')->count(), 'report' => $this];
    }

    public function usage(mixed $value, ?UsageMetricType $metric): string
    {
        if ($value === null || $metric === null) {
            return __('maintenance-report.unknown');
        }
        $scale = match ($metric->key) {
            'distance' => 1000, 'runtime' => 3600, default => 1
        };

        return number_format((int) $value / $scale, $metric->precision, ',', '.').' '.$metric->display_unit;
    }
}
