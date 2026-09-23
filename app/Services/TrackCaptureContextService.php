<?php

namespace App\Services;

use App\Models\Component;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\FollowUpTask;
use App\Models\TechnicalSetup;
use App\Models\TrackCapture;
use App\Models\TrackCaptureReference;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TrackCaptureContextService
{
    /** @var array<string, array{model: class-string<Model>, column: string, route: string, label: string}> */
    private const TYPES = [
        'driver' => ['model' => Driver::class, 'column' => 'display_name', 'route' => 'events.index', 'label' => 'driver'],
        'vehicle' => ['model' => Vehicle::class, 'column' => 'name', 'route' => 'garage.index', 'label' => 'vehicle'],
        'configuration' => ['model' => Configuration::class, 'column' => 'name', 'route' => 'configurations.index', 'label' => 'configuration'],
        'technical_setup' => ['model' => TechnicalSetup::class, 'column' => 'name', 'route' => 'setups.index', 'label' => 'technical setup'],
        'component' => ['model' => Component::class, 'column' => 'name', 'route' => 'components.index', 'label' => 'component'],
    ];

    /**
     * @param  array<string, string|null>  $context
     */
    public function attach(TrackCapture $capture, User $user, array $context): int
    {
        if (Schema::hasTable('track_capture_references') === false || Schema::hasTable('follow_up_tasks') === false) {
            return 0;
        }

        $items = [
            'driver' => $this->single($context['driver_name'] ?? null),
            'vehicle' => $this->single($context['vehicle_name'] ?? null),
            'configuration' => $this->single($context['configuration_name'] ?? null),
            'technical_setup' => $this->single($context['technical_setup_name'] ?? null),
            'component' => $this->many($context['component_names'] ?? null),
        ];

        $pending = 0;

        foreach ($items as $kind => $names) {
            foreach ($names as $name) {
                $resolvedId = $this->findExistingId($kind, (int) $capture->workspace_id, $name);
                $isPending = $resolvedId === null;

                TrackCaptureReference::create([
                    'track_capture_id' => $capture->getKey(),
                    'kind' => $kind,
                    'raw_name' => $name,
                    'normalized_name' => $this->normalize($name),
                    'reference_id' => $resolvedId,
                    'status' => $isPending ? 'pending' : 'resolved',
                    'resolved_at' => $isPending ? null : now(),
                ]);

                if ($isPending) {
                    $pending++;
                    $this->ensureFollowUp($kind, $name, $capture, $user);
                }
            }
        }

        return $pending;
    }

    public function resolve(string $kind, string $name, int $workspaceId, int $entityId): void
    {
        if (isset(self::TYPES[$kind]) === false
            || Schema::hasTable('track_capture_references') === false
            || Schema::hasTable('follow_up_tasks') === false) {
            return;
        }

        $normalized = $this->normalize($name);
        $captureIds = TrackCaptureReference::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('kind', $kind)
            ->where('normalized_name', $normalized)
            ->where('status', 'pending')
            ->pluck('track_capture_id')
            ->unique()
            ->values();

        TrackCaptureReference::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('kind', $kind)
            ->where('normalized_name', $normalized)
            ->where('status', 'pending')
            ->update([
                'reference_id' => $entityId,
                'status' => 'resolved',
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);

        FollowUpTask::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('kind', 'missing_'.$kind)
            ->where('subject_key', $normalized)
            ->where('status', 'open')
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        foreach ($captureIds as $captureId) {
            $this->refreshCaptureStatus($workspaceId, (int) $captureId);
        }
    }

    public function modelCreated(Model $model): void
    {
        foreach (self::TYPES as $kind => $definition) {
            $modelClass = $definition['model'];

            if (($model instanceof $modelClass) === false) {
                continue;
            }

            $workspaceId = (int) $model->getAttribute('workspace_id');
            $name = $model->getAttribute($definition['column']);

            if ($workspaceId > 0 && is_string($name) && trim($name) !== '') {
                $this->resolve($kind, $name, $workspaceId, (int) $model->getKey());
            }

            return;
        }
    }

    private function ensureFollowUp(string $kind, string $name, TrackCapture $capture, User $user): void
    {
        $definition = self::TYPES[$kind];
        $subjectKey = $this->normalize($name);
        $existing = FollowUpTask::query()
            ->where('kind', 'missing_'.$kind)
            ->where('subject_key', $subjectKey)
            ->where('status', 'open')
            ->exists();

        if ($existing) {
            return;
        }

        FollowUpTask::create([
            'kind' => 'missing_'.$kind,
            'subject_key' => $subjectKey,
            'title' => 'Create '.$definition['label'].': '.$name,
            'description' => 'Trackside data already references this '.$definition['label'].'. Create it when convenient; pending quick captures will be linked automatically.',
            'target_route' => $definition['route'],
            'context' => [
                'name' => $name,
                'reference_kind' => $kind,
                'first_capture_id' => $capture->getKey(),
            ],
            'status' => 'open',
            'created_by' => $user->getKey(),
        ]);
    }

    private function findExistingId(string $kind, int $workspaceId, string $name): ?int
    {
        $definition = self::TYPES[$kind];
        $model = $definition['model'];
        $column = $definition['column'];
        $id = $model::query()
            ->withoutGlobalScope('workspace')
            ->where('workspace_id', $workspaceId)
            ->whereRaw('LOWER('.$column.') = ?', [mb_strtolower($name)])
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    private function refreshCaptureStatus(int $workspaceId, int $captureId): void
    {
        $capture = TrackCapture::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->find($captureId);

        if (($capture instanceof TrackCapture) === false) {
            return;
        }

        $hasPendingReference = TrackCaptureReference::query()
            ->withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('track_capture_id', $captureId)
            ->where('status', 'pending')
            ->exists();
        $needsAttention = $capture->circuit_id === null || $hasPendingReference;

        TrackCapture::withoutEvents(function () use ($capture, $needsAttention): void {
            $capture->update([
                'status' => $needsAttention ? 'needs_attention' : 'ready',
                'resolved_at' => $needsAttention ? null : now(),
            ]);
        });
    }

    /** @return list<string> */
    private function single(?string $value): array
    {
        $value = trim((string) $value);

        return $value === '' ? [] : [$value];
    }

    /** @return list<string> */
    private function many(?string $value): array
    {
        $parts = preg_split('/[,;\n]+/', (string) $value) ?: [];
        $names = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $names[$this->normalize($part)] = $part;
            }
        }

        return array_values($names);
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
    }
}
