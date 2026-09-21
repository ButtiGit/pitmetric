<?php

namespace App\Services;

class WorkspaceBackupTableRegistry
{
    /** @return list<string> */
    public function workspaceTables(): array
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
}
