<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

class WorkspaceContext
{
    public function isReady(): bool
    {
        return Schema::hasTable('workspaces')
            && Schema::hasTable('workspace_user')
            && Schema::hasTable('vehicles');
    }

    public function isCoreReady(): bool
    {
        if (! $this->isReady()) {
            return false;
        }

        foreach ([
            'component_types',
            'components',
            'usage_metric_types',
            'component_trackers',
            'component_installations',
            'configurations',
            'configuration_versions',
            'configuration_version_components',
            'circuits',
            'circuit_layouts',
            'track_sessions',
            'session_usage_values',
            'usage_batches',
            'component_usage_entries',
            'maintenance_schedules',
            'maintenance_records',
            'tracker_reset_events',
            'expenses',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    public function isEventsReady(): bool
    {
        if (! $this->isCoreReady()) {
            return false;
        }

        foreach (['drivers', 'events', 'event_entries', 'event_tasks', 'event_notes'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return Schema::hasColumn('track_sessions', 'event_id')
            && Schema::hasColumn('track_sessions', 'event_entry_id')
            && Schema::hasColumn('expenses', 'event_id')
            && Schema::hasColumn('maintenance_records', 'event_id');
    }

    public function personal(User $user): Workspace
    {
        if (! $this->isReady()) {
            throw new LogicException('The workspace domain is not ready. Run the pending database migrations first.');
        }

        $workspace = $user->workspaces()->orderBy('workspaces.id')->first();

        if ($workspace instanceof Workspace) {
            return $workspace;
        }

        return DB::transaction(function () use ($user): Workspace {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $workspace = $lockedUser->workspaces()->orderBy('workspaces.id')->first();

            if ($workspace instanceof Workspace) {
                return $workspace;
            }

            $name = trim($lockedUser->name);
            $workspace = Workspace::create(['name' => ($name !== '' ? $name : 'Personal').' Workspace']);
            $lockedUser->workspaces()->attach($workspace);

            return $workspace;
        });
    }
}
