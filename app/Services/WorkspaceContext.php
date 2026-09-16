<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

class WorkspaceContext
{
    private const SESSION_KEY = 'pitmetric.current_workspace_id';

    private ?bool $ready = null;

    private ?bool $teamReady = null;

    private ?bool $technicalSetupReady = null;

    /** @var array<int, int|null> */
    private array $currentIds = [];

    public function isReady(): bool
    {
        return $this->ready ??= Schema::hasTable('workspaces')
            && Schema::hasTable('workspace_user')
            && Schema::hasTable('vehicles');
    }

    public function isTeamReady(): bool
    {
        return $this->teamReady ??= $this->isReady()
            && Schema::hasColumns('workspace_user', ['role', 'status', 'joined_at'])
            && Schema::hasTable('team_invitations');
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

    public function isTechnicalSetupReady(): bool
    {
        return $this->technicalSetupReady ??= $this->isCoreReady()
            && Schema::hasTable('technical_setups')
            && Schema::hasTable('setup_snapshots');
    }

    public function isEventsReady(): bool
    {
        if (! $this->isTechnicalSetupReady()) {
            return false;
        }

        foreach (['drivers', 'events', 'event_entries', 'event_tasks', 'event_notes', 'event_schedule_items'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return Schema::hasColumn('event_notes', 'kind')
            && Schema::hasColumn('track_sessions', 'event_id')
            && Schema::hasColumn('track_sessions', 'event_entry_id')
            && Schema::hasColumn('expenses', 'event_id')
            && Schema::hasColumn('maintenance_records', 'event_id');
    }

    public function currentId(User $user): ?int
    {
        $userId = (int) $user->getKey();

        if (array_key_exists($userId, $this->currentIds)) {
            return $this->currentIds[$userId];
        }

        if (! $this->isReady()) {
            return $this->currentIds[$userId] = null;
        }

        if (! $this->isTeamReady()) {
            $workspaceId = DB::table('workspace_user')
                ->where('user_id', $user->getKey())
                ->orderBy('workspace_id')
                ->value('workspace_id');

            return $this->currentIds[$userId] = $workspaceId === null ? null : (int) $workspaceId;
        }

        $selected = $this->sessionWorkspaceId();

        if ($selected !== null && $this->hasActiveMembership($user, $selected)) {
            return $this->currentIds[$userId] = $selected;
        }

        $workspaceId = DB::table('workspace_user')
            ->where('user_id', $user->getKey())
            ->where('status', 'active')
            ->orderByRaw('joined_at IS NULL')
            ->orderBy('joined_at')
            ->orderBy('workspace_id')
            ->value('workspace_id');

        if ($workspaceId === null) {
            $this->forgetSelection();

            return $this->currentIds[$userId] = null;
        }

        $workspaceId = (int) $workspaceId;
        $this->rememberSelection($workspaceId);

        return $this->currentIds[$userId] = $workspaceId;
    }

    public function current(User $user): ?Workspace
    {
        $workspaceId = $this->currentId($user);

        return $workspaceId === null ? null : Workspace::query()->find($workspaceId);
    }

    public function personal(User $user): Workspace
    {
        if (! $this->isReady()) {
            throw new LogicException('The workspace domain is not ready. Run the pending database migrations first.');
        }

        $workspace = $this->current($user);

        if ($workspace instanceof Workspace) {
            return $workspace;
        }

        if ($user->workspaces()->exists()) {
            throw new AuthorizationException('This account has team memberships, but none of them are active.');
        }

        return DB::transaction(function () use ($user): Workspace {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $workspace = $this->current($lockedUser);

            if ($workspace instanceof Workspace) {
                return $workspace;
            }

            if ($lockedUser->workspaces()->exists()) {
                throw new AuthorizationException('This account has team memberships, but none of them are active.');
            }

            $name = trim($lockedUser->name);
            $workspace = Workspace::create(['name' => ($name !== '' ? $name : 'Personal').' Team']);
            $lockedUser->workspaces()->attach($workspace, $this->ownerPivot());
            $workspaceId = (int) $workspace->getKey();
            $this->rememberSelection($workspaceId);
            $this->currentIds[(int) $lockedUser->getKey()] = $workspaceId;

            return $workspace;
        });
    }

    public function select(User $user, Workspace $workspace): void
    {
        if (! $this->hasActiveMembership($user, (int) $workspace->getKey())) {
            abort(403, 'You do not have an active membership in this team.');
        }

        $workspaceId = (int) $workspace->getKey();
        $this->rememberSelection($workspaceId);
        $this->currentIds[(int) $user->getKey()] = $workspaceId;
    }

    public function role(User $user, ?int $workspaceId = null): ?string
    {
        $workspaceId ??= $this->currentId($user);

        if ($workspaceId === null) {
            return null;
        }

        if (! $this->isTeamReady()) {
            return $user->workspaces()->whereKey($workspaceId)->exists() ? 'owner' : null;
        }

        $role = DB::table('workspace_user')
            ->where('user_id', $user->getKey())
            ->where('workspace_id', $workspaceId)
            ->where('status', 'active')
            ->value('role');

        return is_string($role) ? $role : null;
    }

    public function canWrite(User $user): bool
    {
        return in_array($this->role($user), WorkspaceMembership::WRITABLE_ROLES, true);
    }

    public function canManage(User $user): bool
    {
        return in_array($this->role($user), WorkspaceMembership::MANAGER_ROLES, true);
    }

    public function isOwner(User $user): bool
    {
        return $this->role($user) === 'owner';
    }

    /** @return array{role: string, status: string, joined_at: CarbonInterface} */
    public function ownerPivot(): array
    {
        return [
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ];
    }

    private function hasActiveMembership(User $user, int $workspaceId): bool
    {
        $query = DB::table('workspace_user')
            ->where('user_id', $user->getKey())
            ->where('workspace_id', $workspaceId);

        if ($this->isTeamReady()) {
            $query->where('status', 'active');
        }

        return $query->exists();
    }

    private function sessionWorkspaceId(): ?int
    {
        if (! request()->hasSession()) {
            return null;
        }

        $workspaceId = request()->session()->get(self::SESSION_KEY);

        return is_numeric($workspaceId) ? (int) $workspaceId : null;
    }

    private function rememberSelection(int $workspaceId): void
    {
        if (request()->hasSession()) {
            request()->session()->put(self::SESSION_KEY, $workspaceId);
        }
    }

    private function forgetSelection(): void
    {
        if (request()->hasSession()) {
            request()->session()->forget(self::SESSION_KEY);
        }
    }
}
