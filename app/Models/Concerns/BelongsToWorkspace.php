<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

trait BelongsToWorkspace
{
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder): void {
            $userId = Auth::id();

            if ($userId === null) {
                return;
            }

            $builder->whereIn(
                $builder->getModel()->qualifyColumn('workspace_id'),
                DB::table('workspace_user')
                    ->select('workspace_id')
                    ->where('user_id', $userId),
            );
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('workspace_id') !== null) {
                return;
            }

            $userId = Auth::id();

            if ($userId === null) {
                throw new LogicException('A workspace-owned model requires an authenticated user or an explicit workspace_id.');
            }

            $workspaceId = DB::table('workspace_user')
                ->where('user_id', $userId)
                ->orderBy('workspace_id')
                ->value('workspace_id');

            if ($workspaceId === null) {
                throw new LogicException('The authenticated user does not belong to a workspace.');
            }

            $model->setAttribute('workspace_id', $workspaceId);
        });
    }
}
