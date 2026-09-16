<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditTrailService
{
    /** @var list<string> */
    private const IGNORED_ATTRIBUTES = [
        'workspace_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function created(Model $model): void
    {
        $this->recordModel($model, 'created', null, $this->snapshot($model));
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), self::IGNORED_ATTRIBUTES);

        if ($changes === []) {
            return;
        }

        $before = [];
        $after = [];

        foreach (array_keys($changes) as $attribute) {
            $before[$attribute] = $this->normalizeValue($model->getRawOriginal($attribute));
            $after[$attribute] = $this->normalizeValue($model->getAttribute($attribute));
        }

        $this->recordModel($model, 'updated', $before, $after);
    }

    public function deleted(Model $model): void
    {
        $action = method_exists($model, 'trashed') && $model->trashed() ? 'archived' : 'deleted';
        $this->recordModel($model, $action, $this->snapshot($model), null);
    }

    public function restored(Model $model): void
    {
        $this->recordModel($model, 'restored', null, $this->snapshot($model));
    }

    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    public function custom(
        int $workspaceId,
        string $action,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        ?string $subjectLabel = null,
        ?array $before = null,
        ?array $after = null,
    ): void {
        $this->write(
            $workspaceId,
            $action,
            $subjectType,
            is_numeric($subjectId) ? (int) $subjectId : null,
            $subjectLabel,
            $before,
            $after,
        );
    }

    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    private function recordModel(Model $model, string $action, ?array $before, ?array $after): void
    {
        $workspaceId = $model->getAttribute('workspace_id');

        if (! is_numeric($workspaceId)) {
            return;
        }

        $this->write(
            (int) $workspaceId,
            $action,
            $model::class,
            is_numeric($model->getKey()) ? (int) $model->getKey() : null,
            $this->label($model),
            $before,
            $after,
        );
    }

    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    private function write(
        int $workspaceId,
        string $action,
        ?string $subjectType,
        ?int $subjectId,
        ?string $subjectLabel,
        ?array $before,
        ?array $after,
    ): void {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $user = Auth::user();
        $request = app()->bound('request') ? request() : null;

        AuditLog::query()->create([
            'workspace_id' => $workspaceId,
            'user_id' => $user instanceof User ? $user->getKey() : null,
            'action' => Str::limit($action, 40, ''),
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_label' => $subjectLabel,
            'before_values' => $before,
            'after_values' => $after,
            'route' => $request?->route()?->getName(),
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 500, ''),
        ]);
    }

    /** @return array<string, mixed> */
    private function snapshot(Model $model): array
    {
        $attributes = Arr::except($model->getAttributes(), self::IGNORED_ATTRIBUTES);

        return collect($attributes)
            ->map(fn (mixed $value): mixed => $this->normalizeValue($value))
            ->all();
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_array($value) || is_scalar($value) || $value === null) {
            return $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    private function label(Model $model): string
    {
        foreach (['name', 'title', 'display_name', 'description', 'label'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_string($value) && trim($value) !== '') {
                return Str::limit(trim($value), 180, '');
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }
}
