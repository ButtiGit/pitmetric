<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, array{child: string, column: string, parent: string, constraint: string, delete: 'cascade'|'restrict'}>
     */
    private array $relations = [
        ['child' => 'components', 'column' => 'component_type_id', 'parent' => 'component_types', 'constraint' => 'components_type_tenant_fk', 'delete' => 'restrict'],
        ['child' => 'component_installations', 'column' => 'vehicle_id', 'parent' => 'vehicles', 'constraint' => 'installations_vehicle_tenant_fk', 'delete' => 'restrict'],
        ['child' => 'component_installations', 'column' => 'component_id', 'parent' => 'components', 'constraint' => 'installations_component_tenant_fk', 'delete' => 'restrict'],
        ['child' => 'configurations', 'column' => 'vehicle_id', 'parent' => 'vehicles', 'constraint' => 'configurations_vehicle_tenant_fk', 'delete' => 'restrict'],
        ['child' => 'track_sessions', 'column' => 'vehicle_id', 'parent' => 'vehicles', 'constraint' => 'sessions_vehicle_tenant_fk', 'delete' => 'restrict'],
        ['child' => 'maintenance_records', 'column' => 'component_id', 'parent' => 'components', 'constraint' => 'maintenance_component_tenant_fk', 'delete' => 'restrict'],
        ['child' => 'event_entries', 'column' => 'event_id', 'parent' => 'events', 'constraint' => 'entries_event_tenant_fk', 'delete' => 'cascade'],
        ['child' => 'event_entries', 'column' => 'driver_id', 'parent' => 'drivers', 'constraint' => 'entries_driver_tenant_fk', 'delete' => 'restrict'],
        ['child' => 'event_entries', 'column' => 'vehicle_id', 'parent' => 'vehicles', 'constraint' => 'entries_vehicle_tenant_fk', 'delete' => 'restrict'],
        ['child' => 'event_tasks', 'column' => 'event_id', 'parent' => 'events', 'constraint' => 'tasks_event_tenant_fk', 'delete' => 'cascade'],
        ['child' => 'event_notes', 'column' => 'event_id', 'parent' => 'events', 'constraint' => 'notes_event_tenant_fk', 'delete' => 'cascade'],
        ['child' => 'event_schedule_items', 'column' => 'event_id', 'parent' => 'events', 'constraint' => 'schedule_event_tenant_fk', 'delete' => 'cascade'],
        ['child' => 'technical_setups', 'column' => 'vehicle_id', 'parent' => 'vehicles', 'constraint' => 'setups_vehicle_tenant_fk', 'delete' => 'cascade'],
        ['child' => 'setup_snapshots', 'column' => 'session_id', 'parent' => 'track_sessions', 'constraint' => 'snapshots_session_tenant_fk', 'delete' => 'cascade'],
        ['child' => 'setup_snapshots', 'column' => 'vehicle_id', 'parent' => 'vehicles', 'constraint' => 'snapshots_vehicle_tenant_fk', 'delete' => 'cascade'],
        ['child' => 'maintenance_work_orders', 'column' => 'maintenance_schedule_id', 'parent' => 'maintenance_schedules', 'constraint' => 'work_orders_schedule_tenant_fk', 'delete' => 'restrict'],
    ];

    /**
     * @var array<string, string>
     */
    private array $parentIndexes = [
        'component_types' => 'component_types_tenant_uq',
        'vehicles' => 'vehicles_tenant_uq',
        'components' => 'components_tenant_uq',
        'events' => 'events_tenant_uq',
        'drivers' => 'drivers_tenant_uq',
        'track_sessions' => 'track_sessions_tenant_uq',
        'maintenance_schedules' => 'maintenance_schedules_tenant_uq',
    ];

    public function up(): void
    {
        $this->assertExistingDataIsConsistent();

        foreach ($this->parentIndexes as $tableName => $indexName) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                $table->unique(['id', 'workspace_id'], $indexName);
            });
        }

        foreach ($this->relations as $relation) {
            Schema::table($relation['child'], function (Blueprint $table) use ($relation): void {
                $foreign = $table->foreign(
                    [$relation['column'], 'workspace_id'],
                    $relation['constraint'],
                )->references(['id', 'workspace_id'])
                    ->on($relation['parent']);

                if ($relation['delete'] === 'cascade') {
                    $foreign->cascadeOnDelete();
                } else {
                    $foreign->restrictOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->relations) as $relation) {
            Schema::table($relation['child'], function (Blueprint $table) use ($relation): void {
                $table->dropForeign($relation['constraint']);
            });
        }

        foreach (array_reverse($this->parentIndexes, true) as $tableName => $indexName) {
            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                $table->dropUnique($indexName);
            });
        }
    }

    private function assertExistingDataIsConsistent(): void
    {
        foreach ($this->relations as $relation) {
            $mismatchExists = DB::table($relation['child'].' as child')
                ->join(
                    $relation['parent'].' as parent',
                    'child.'.$relation['column'],
                    '=',
                    'parent.id',
                )
                ->whereColumn('child.workspace_id', '<>', 'parent.workspace_id')
                ->exists();

            if (! $mismatchExists) {
                continue;
            }

            throw new RuntimeException(sprintf(
                'Tenant integrity violation: %s.%s references a %s record from another workspace. Resolve the inconsistent data before applying this migration.',
                $relation['child'],
                $relation['column'],
                $relation['parent'],
            ));
        }
    }
};
