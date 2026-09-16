<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WorkspaceAuditObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    private function record(Model $model, string $action): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $workspaceId = $model->getAttribute('workspace_id');

        if (! is_numeric($workspaceId)) {
            return;
        }

        $changedFields = array_values(array_diff(
            array_keys($model->getChanges()),
            ['created_at', 'updated_at', 'deleted_at', 'workspace_id'],
        ));

        $request = app()->bound('request') ? request() : null;
        $entity = class_basename($model);

        AuditLog::withoutEvents(function () use ($workspaceId, $action, $model, $changedFields, $request, $entity): void {
            AuditLog::withoutGlobalScopes()->create([
                'workspace_id' => (int) $workspaceId,
                'actor_id' => Auth::id(),
                'action' => $action,
                'entity_type' => $entity,
                'entity_id' => is_numeric($model->getKey()) ? (int) $model->getKey() : null,
                'summary' => Str::headline($action).' '.$entity.' #'.$model->getKey(),
                'metadata' => $changedFields === [] ? null : ['changed_fields' => $changedFields],
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        });
    }
}
