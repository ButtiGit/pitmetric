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
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $workspace = $lockedUser->workspaces()->orderBy('workspaces.id')->first();

            if ($workspace instanceof Workspace) {
                return $workspace;
            }

            $name = trim($lockedUser->name);
            $workspace = Workspace::create([
                'name' => ($name !== '' ? $name : 'Personal').' Workspace',
            ]);

            $lockedUser->workspaces()->attach($workspace);

            return $workspace;
        });
    }
}
