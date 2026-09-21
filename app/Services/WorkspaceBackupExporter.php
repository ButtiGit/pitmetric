<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class WorkspaceBackupExporter
{
    /** @param list<string> $workspaceTables */
    public function __construct(private readonly array $workspaceTables = [
        'vehicles',
        'component_types',
        'components',
        'component_installations',
        'configurations',
        'circuits',
        'track_sessions',
        'usage_batches',
        'maintenance_schedules',
        'maintenance_records',
        'expenses',
        'drivers',
        'events',
        'event_entries',
        'event_tasks',
        'event_notes',
        'event_schedule_items',
        'technical_setups',
        'setup_snapshots',
        'maintenance_work_orders',
    ]) {}

    /** @return array<string, mixed> */
    public function export(Workspace $workspace): array
    {
        $workspaceId = (int) $workspace->getKey();
        $tables = [];

        foreach ($this->workspaceTables as $table) {
            $tables[$table] = DB::table($table)
                ->where('workspace_id', $workspaceId)
                ->orderBy('id')
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all();
        }

        $componentIds = $this->ids($tables['components']);
        $configurationIds = $this->ids($tables['configurations']);
        $circuitIds = $this->ids($tables['circuits']);
        $sessionIds = $this->ids($tables['track_sessions']);
        $batchIds = $this->ids($tables['usage_batches']);

        $tables['component_trackers'] = $this->children('component_trackers', 'component_id', $componentIds);
        $tables['configuration_versions'] = $this->children('configuration_versions', 'configuration_id', $configurationIds);
        $tables['configuration_version_components'] = $this->children(
            'configuration_version_components',
            'configuration_version_id',
            $this->ids($tables['configuration_versions']),
        );
        $tables['circuit_layouts'] = $this->children('circuit_layouts', 'circuit_id', $circuitIds);
        $tables['session_usage_values'] = $this->children('session_usage_values', 'session_id', $sessionIds);
        $tables['component_usage_entries'] = $this->children('component_usage_entries', 'usage_batch_id', $batchIds);
        $tables['tracker_reset_events'] = $this->children(
            'tracker_reset_events',
            'component_tracker_id',
            $this->ids($tables['component_trackers']),
        );

        $metricIds = collect($tables['component_trackers'])->pluck('usage_metric_type_id')
            ->merge(collect($tables['session_usage_values'])->pluck('usage_metric_type_id'))
            ->merge(collect($tables['usage_batches'])->pluck('usage_metric_type_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $usageMetrics = $metricIds === []
            ? []
            : DB::table('usage_metric_types')
                ->whereIn('id', $metricIds)
                ->orderBy('id')
                ->get(['id', 'key'])
                ->map(fn (object $row): array => (array) $row)
                ->all();

        return [
            'format' => DataHubService::FORMAT,
            'version' => DataHubService::VERSION,
            'exported_at' => now()->toIso8601String(),
            'workspace' => ['name' => $workspace->name],
            'references' => ['usage_metric_types' => $usageMetrics],
            'tables' => $tables,
            'excluded' => [
                'documents' => DB::table('documents')->where('workspace_id', $workspaceId)->count(),
                'audit_logs' => DB::table('audit_logs')->where('workspace_id', $workspaceId)->count(),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, int>
     */
    private function ids(array $rows): array
    {
        return collect($rows)->pluck('id')->filter()->map(fn (mixed $id): int => (int) $id)->values()->all();
    }

    /**
     * @param  array<int, int>  $parentIds
     * @return array<int, array<string, mixed>>
     */
    private function children(string $table, string $column, array $parentIds): array
    {
        if ($parentIds === []) {
            return [];
        }

        return DB::table($table)
            ->whereIn($column, $parentIds)
            ->orderBy('id')
            ->get()
            ->map(fn (object $row): array => (array) $row)
            ->all();
    }
}
