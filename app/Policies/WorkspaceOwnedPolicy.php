<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class WorkspaceOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->workspaces()->exists();
    }

    public function view(User $user, Model $model): bool
    {
        return $this->belongsToWorkspace($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->workspaces()->exists();
    }

    public function update(User $user, Model $model): bool
    {
        return $this->belongsToWorkspace($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->belongsToWorkspace($user, $model);
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->belongsToWorkspace($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }

    private function belongsToWorkspace(User $user, Model $model): bool
    {
        $workspaceId = $model->getAttribute('workspace_id');

        return is_numeric($workspaceId)
            && $user->workspaces()->whereKey((int) $workspaceId)->exists();
    }
}
