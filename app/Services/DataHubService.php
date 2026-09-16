<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DataHubService
{
    public const FORMAT = 'pitmetric-workspace-backup';

    public const VERSION = 1;

    /**
     * @return array<string, mixed>
     */
    public function export(Workspace $workspace): array
    {
        $workspaceId = (int) $workspace->getKey();
        $tables = [];

        foreach ($this->workspaceTables() as $table) {
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
        $tables['tracker_reset_events'] = $this->children('tracker_reset_events', 'component_tracker_id', $this->ids($tables['component_trackers']));

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
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'workspace' => [
                'name' => $workspace->name,
            ],
            'references' => [
                'usage_metric_types' => $usageMetrics,
            ],
            'tables' => $tables,
            'excluded' => [
                'documents' => DB::table('documents')->where('workspace_id', $workspaceId)->count(),
                'audit_logs' => DB::table('audit_logs')->where('workspace_id', $workspaceId)->count(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $backup
     * @return array<string, int>
     */
    public function import(Workspace $workspace, User $user, array $backup): array
    {
        $this->validateBackup($backup);
        $this->ensureWorkspaceIsEmpty((int) $workspace->getKey());

        /** @var array<string, list<array<string, mixed>>> $tables */
        $tables = $backup['tables'];
        $workspaceId = (int) $workspace->getKey();
        $userId = (int) $user->getKey();
        $metricMap = $this->metricMap($backup);

        return DB::transaction(function () use ($tables, $workspaceId, $userId, $metricMap): array {
            $maps = [];

            $maps['vehicles'] = $this->insertRows('vehicles', $tables['vehicles'] ?? [], fn (array $row): array => $this->prepare($row, $workspaceId, $userId));
            $maps['component_types'] = $this->insertRows('component_types', $tables['component_types'] ?? [], fn (array $row): array => $this->prepare($row, $workspaceId, $userId));
            $maps['components'] = $this->insertRows('components', $tables['components'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['component_type_id'] = $this->mapped($maps['component_types'], $row['component_type_id']);

                return $row;
            });
            $maps['component_trackers'] = $this->insertRows('component_trackers', $tables['component_trackers'] ?? [], function (array $row) use (&$maps, $metricMap): array {
                $row = $this->prepare($row, null, null);
                $row['component_id'] = $this->mapped($maps['components'], $row['component_id']);
                $row['usage_metric_type_id'] = $this->mapped($metricMap, $row['usage_metric_type_id']);

                return $row;
            });
            $maps['component_installations'] = $this->insertRows('component_installations', $tables['component_installations'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['vehicle_id'] = $this->mapped($maps['vehicles'], $row['vehicle_id']);
                $row['component_id'] = $this->mapped($maps['components'], $row['component_id']);

                return $row;
            });
            $maps['configurations'] = $this->insertRows('configurations', $tables['configurations'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['vehicle_id'] = $this->mapped($maps['vehicles'], $row['vehicle_id']);

                return $row;
            });
            $maps['configuration_versions'] = $this->insertRows('configuration_versions', $tables['configuration_versions'] ?? [], function (array $row) use ($userId, &$maps): array {
                $row = $this->prepare($row, null, $userId);
                $row['configuration_id'] = $this->mapped($maps['configurations'], $row['configuration_id']);

                return $row;
            });
            $maps['configuration_version_components'] = $this->insertRows('configuration_version_components', $tables['configuration_version_components'] ?? [], function (array $row) use (&$maps): array {
                $row = $this->prepare($row, null, null);
                $row['configuration_version_id'] = $this->mapped($maps['configuration_versions'], $row['configuration_version_id']);
                $row['component_id'] = $this->mapped($maps['components'], $row['component_id']);

                return $row;
            });
            $maps['circuits'] = $this->insertRows('circuits', $tables['circuits'] ?? [], fn (array $row): array => $this->prepare($row, $workspaceId, $userId));
            $maps['circuit_layouts'] = $this->insertRows('circuit_layouts', $tables['circuit_layouts'] ?? [], function (array $row) use (&$maps): array {
                $row = $this->prepare($row, null, null);
                $row['circuit_id'] = $this->mapped($maps['circuits'], $row['circuit_id']);

                return $row;
            });
            $maps['drivers'] = $this->insertRows('drivers', $tables['drivers'] ?? [], fn (array $row): array => $this->prepare($row, $workspaceId, $userId));
            $maps['events'] = $this->insertRows('events', $tables['events'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['circuit_layout_id'] = $this->mappedNullable($maps['circuit_layouts'], $row['circuit_layout_id'] ?? null);

                return $row;
            });
            $maps['technical_setups'] = $this->insertRows('technical_setups', $tables['technical_setups'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['vehicle_id'] = $this->mapped($maps['vehicles'], $row['vehicle_id']);

                return $row;
            });
            $maps['event_entries'] = $this->insertRows('event_entries', $tables['event_entries'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['event_id'] = $this->mapped($maps['events'], $row['event_id']);
                $row['driver_id'] = $this->mapped($maps['drivers'], $row['driver_id']);
                $row['vehicle_id'] = $this->mapped($maps['vehicles'], $row['vehicle_id']);
                $row['configuration_version_id'] = $this->mapped($maps['configuration_versions'], $row['configuration_version_id']);

                return $row;
            });
            $maps['track_sessions'] = $this->insertRows('track_sessions', $tables['track_sessions'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['event_id'] = $this->mappedNullable($maps['events'], $row['event_id'] ?? null);
                $row['event_entry_id'] = $this->mappedNullable($maps['event_entries'], $row['event_entry_id'] ?? null);
                $row['vehicle_id'] = $this->mapped($maps['vehicles'], $row['vehicle_id']);
                $row['configuration_version_id'] = $this->mapped($maps['configuration_versions'], $row['configuration_version_id']);
                $row['circuit_layout_id'] = $this->mappedNullable($maps['circuit_layouts'], $row['circuit_layout_id'] ?? null);

                return $row;
            });
            $maps['setup_snapshots'] = $this->insertRows('setup_snapshots', $tables['setup_snapshots'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['session_id'] = $this->mapped($maps['track_sessions'], $row['session_id']);
                $row['technical_setup_id'] = $this->mappedNullable($maps['technical_setups'], $row['technical_setup_id'] ?? null);
                $row['vehicle_id'] = $this->mapped($maps['vehicles'], $row['vehicle_id']);
                $row['configuration_version_id'] = $this->mapped($maps['configuration_versions'], $row['configuration_version_id']);

                return $row;
            });
            $maps['session_usage_values'] = $this->insertRows('session_usage_values', $tables['session_usage_values'] ?? [], function (array $row) use (&$maps, $metricMap): array {
                $row = $this->prepare($row, null, null);
                $row['session_id'] = $this->mapped($maps['track_sessions'], $row['session_id']);
                $row['usage_metric_type_id'] = $this->mapped($metricMap, $row['usage_metric_type_id']);

                return $row;
            });
            $maps['usage_batches'] = $this->insertRows('usage_batches', $tables['usage_batches'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps, $metricMap): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['usage_metric_type_id'] = $this->mapped($metricMap, $row['usage_metric_type_id']);

                if (($row['source_type'] ?? null) === 'session') {
                    $row['source_id'] = $this->mapped($maps['track_sessions'], $row['source_id']);
                }

                return $row;
            });
            $maps['component_usage_entries'] = $this->insertRows('component_usage_entries', $tables['component_usage_entries'] ?? [], function (array $row) use ($userId, &$maps): array {
                $row = $this->prepare($row, null, $userId);
                $row['component_tracker_id'] = $this->mapped($maps['component_trackers'], $row['component_tracker_id']);
                $row['usage_batch_id'] = $this->mapped($maps['usage_batches'], $row['usage_batch_id']);

                return $row;
            });
            $maps['maintenance_schedules'] = $this->insertRows('maintenance_schedules', $tables['maintenance_schedules'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['component_tracker_id'] = $this->mapped($maps['component_trackers'], $row['component_tracker_id']);

                return $row;
            });
            $maps['maintenance_records'] = $this->insertRows('maintenance_records', $tables['maintenance_records'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['event_id'] = $this->mappedNullable($maps['events'], $row['event_id'] ?? null);
                $row['component_id'] = $this->mapped($maps['components'], $row['component_id']);
                $row['maintenance_schedule_id'] = $this->mappedNullable($maps['maintenance_schedules'], $row['maintenance_schedule_id'] ?? null);

                return $row;
            });
            $maps['tracker_reset_events'] = $this->insertRows('tracker_reset_events', $tables['tracker_reset_events'] ?? [], function (array $row) use ($userId, &$maps): array {
                $row = $this->prepare($row, null, $userId);
                $row['component_tracker_id'] = $this->mapped($maps['component_trackers'], $row['component_tracker_id']);
                $row['maintenance_record_id'] = $this->mappedNullable($maps['maintenance_records'], $row['maintenance_record_id'] ?? null);

                return $row;
            });
            $maps['event_tasks'] = $this->insertRows('event_tasks', $tables['event_tasks'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['event_id'] = $this->mapped($maps['events'], $row['event_id']);
                $row['event_entry_id'] = $this->mappedNullable($maps['event_entries'], $row['event_entry_id'] ?? null);

                return $row;
            });
            $maps['event_notes'] = $this->insertRows('event_notes', $tables['event_notes'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['event_id'] = $this->mapped($maps['events'], $row['event_id']);
                $row['event_entry_id'] = $this->mappedNullable($maps['event_entries'], $row['event_entry_id'] ?? null);

                return $row;
            });
            $maps['event_schedule_items'] = $this->insertRows('event_schedule_items', $tables['event_schedule_items'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['event_id'] = $this->mapped($maps['events'], $row['event_id']);
                $row['event_entry_id'] = $this->mappedNullable($maps['event_entries'], $row['event_entry_id'] ?? null);
                $row['session_id'] = $this->mappedNullable($maps['track_sessions'], $row['session_id'] ?? null);

                return $row;
            });
            $maps['maintenance_work_orders'] = $this->insertRows('maintenance_work_orders', $tables['maintenance_work_orders'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['maintenance_schedule_id'] = $this->mapped($maps['maintenance_schedules'], $row['maintenance_schedule_id']);
                $row['maintenance_record_id'] = $this->mappedNullable($maps['maintenance_records'], $row['maintenance_record_id'] ?? null);
                $row['assigned_to'] = null;

                return $row;
            });
            $maps['expenses'] = $this->insertRows('expenses', $tables['expenses'] ?? [], function (array $row) use ($workspaceId, $userId, &$maps): array {
                $row = $this->prepare($row, $workspaceId, $userId);
                $row['event_id'] = $this->mappedNullable($maps['events'], $row['event_id'] ?? null);
                $row['related_id'] = $this->relatedId($row['related_type'] ?? null, $row['related_id'] ?? null, $maps);

                return $row;
            });

            return collect($maps)->map(fn (array $map): int => count($map))->all();
        });
    }

    /** @return list<string> */
    private function workspaceTables(): array
    {
        return [
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
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<int>
     */
    private function ids(array $rows): array
    {
        return collect($rows)->pluck('id')->filter()->map(fn (mixed $id): int => (int) $id)->values()->all();
    }

    /**
     * @param  list<int>  $parentIds
     * @return list<array<string, mixed>>
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

    /**
     * @param  array<string, mixed>  $backup
     */
    private function validateBackup(array $backup): void
    {
        if (($backup['format'] ?? null) !== self::FORMAT || (int) ($backup['version'] ?? 0) !== self::VERSION) {
            throw ValidationException::withMessages([
                'backup' => 'This file is not a supported PitMetric workspace backup.',
            ]);
        }

        if (! isset($backup['tables']) || ! is_array($backup['tables'])) {
            throw ValidationException::withMessages([
                'backup' => 'The backup does not contain a valid tables payload.',
            ]);
        }
    }

    private function ensureWorkspaceIsEmpty(int $workspaceId): void
    {
        foreach ($this->workspaceTables() as $table) {
            if (DB::table($table)->where('workspace_id', $workspaceId)->exists()) {
                throw ValidationException::withMessages([
                    'backup' => 'Import is allowed only into an empty workspace to prevent duplicates and broken history.',
                ]);
            }
        }

        if (DB::table('documents')->where('workspace_id', $workspaceId)->exists()) {
            throw ValidationException::withMessages([
                'backup' => 'Import is allowed only into an empty workspace. Remove existing private documents first.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $backup
     * @return array<int, int>
     */
    private function metricMap(array $backup): array
    {
        $map = [];
        $references = $backup['references']['usage_metric_types'] ?? [];

        foreach (is_array($references) ? $references : [] as $reference) {
            if (! is_array($reference) || ! isset($reference['id'], $reference['key'])) {
                continue;
            }

            $currentId = DB::table('usage_metric_types')->where('key', $reference['key'])->value('id');

            if ($currentId === null) {
                throw ValidationException::withMessages([
                    'backup' => 'The backup references an unsupported usage metric: '.$reference['key'].'.',
                ]);
            }

            $map[(int) $reference['id']] = (int) $currentId;
        }

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  callable(array<string, mixed>): array<string, mixed>  $transform
     * @return array<int, int>
     */
    private function insertRows(string $table, array $rows, callable $transform): array
    {
        $map = [];

        foreach ($rows as $sourceRow) {
            $oldId = (int) ($sourceRow['id'] ?? 0);
            $row = $sourceRow;
            unset($row['id']);
            $row = $transform($row);

            $newId = (int) DB::table($table)->insertGetId($row);

            if ($oldId > 0) {
                $map[$oldId] = $newId;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function prepare(array $row, ?int $workspaceId, ?int $userId): array
    {
        if (array_key_exists('workspace_id', $row) && $workspaceId !== null) {
            $row['workspace_id'] = $workspaceId;
        }

        if (array_key_exists('created_by', $row) && $userId !== null) {
            $row['created_by'] = $userId;
        }

        if (array_key_exists('uploaded_by', $row) && $userId !== null) {
            $row['uploaded_by'] = $userId;
        }

        return $row;
    }

    /** @param array<int, int> $map */
    private function mapped(array $map, mixed $oldId): int
    {
        $oldId = (int) $oldId;

        if (! isset($map[$oldId])) {
            throw ValidationException::withMessages([
                'backup' => 'The backup contains a broken relationship and cannot be restored safely.',
            ]);
        }

        return $map[$oldId];
    }

    /** @param array<int, int> $map */
    private function mappedNullable(array $map, mixed $oldId): ?int
    {
        if ($oldId === null || $oldId === '') {
            return null;
        }

        return $this->mapped($map, $oldId);
    }

    /**
     * @param  array<string, array<int, int>>  $maps
     */
    private function relatedId(mixed $type, mixed $oldId, array $maps): ?int
    {
        if ($oldId === null || $oldId === '') {
            return null;
        }

        $table = match ($type) {
            'session' => 'track_sessions',
            'event', 'race_event' => 'events',
            'event_entry' => 'event_entries',
            'event_task' => 'event_tasks',
            'maintenance_record' => 'maintenance_records',
            'maintenance_work_order', 'work_order' => 'maintenance_work_orders',
            'technical_setup' => 'technical_setups',
            'component' => 'components',
            'vehicle' => 'vehicles',
            'configuration_version' => 'configuration_versions',
            default => null,
        };

        if ($table === null || ! isset($maps[$table])) {
            return null;
        }

        return $this->mappedNullable($maps[$table], $oldId);
    }
}
