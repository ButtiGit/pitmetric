<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class WorkspaceBackupTableRegistry
{
    /** @return list<string> */
    public function workspaceTables(): array
    {
        $tables = [
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
        foreach (['championships', 'championship_rounds', 'championship_participants', 'championship_results'] as $table) {
            if (Schema::hasTable($table)) {
                $tables[] = $table;
            }
        }

        return $tables;
    }
}
