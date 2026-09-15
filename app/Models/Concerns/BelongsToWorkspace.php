<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use LogicException;

trait BelongsToWorkspace
{
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder): void {
            $user = Auth::user();

            if (! $user instanceof User) {
                return;
            }

            $workspaceId = app(WorkspaceContext::class)->currentId($user);

            if ($workspaceId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($builder->getModel()->qualifyColumn('workspace_id'), $workspaceId);
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('workspace_id') !== null) {
                return;
            }

            $user = Auth::user();

            if (! $user instanceof User) {
                throw new LogicException('A workspace-owned model requires an authenticated user or an explicit workspace_id.');
            }

            $workspaceId = app(WorkspaceContext::class)->currentId($user);

            if ($workspaceId === null) {
                throw new LogicException('The authenticated user does not have an active team selected.');
            }

            $model->setAttribute('workspace_id', $workspaceId);
        });
    }
}
