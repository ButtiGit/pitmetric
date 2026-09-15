<?php

namespace App\Policies;

use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class WorkspaceOwnedPolicy
{
    public function __construct(private readonly WorkspaceContext $workspaceContext)
    {
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $this->workspaceContext->role($user) !== null;
    }

    public function view(User $user, Model $model): bool
    {
        return $this->isAdmin($user) || $this->belongsToCurrentWorkspace($user, $model);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $this->workspaceContext->canWrite($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->isAdmin($user)
            || ($this->workspaceContext->canWrite($user) && $this->belongsToCurrentWorkspace($user, $model));
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->isAdmin($user)
            || ($this->workspaceContext->canWrite($user) && $this->belongsToCurrentWorkspace($user, $model));
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->update($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }

    private function belongsToCurrentWorkspace(User $user, Model $model): bool
    {
        $workspaceId = $model->getAttribute('workspace_id');
        $currentWorkspaceId = $this->workspaceContext->currentId($user);

        return is_numeric($workspaceId)
            && $currentWorkspaceId !== null
            && (int) $workspaceId === $currentWorkspaceId;
    }

    private function isAdmin(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates');
    }
}
