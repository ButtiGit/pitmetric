<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\Configuration;
use App\Models\ConfigurationVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateConfigurationVersionService
{
    /**
     * @param  list<int>  $componentIds
     */
    public function create(
        Configuration $configuration,
        User $user,
        array $componentIds,
        ?string $notes = null,
    ): ConfigurationVersion {
        return DB::transaction(function () use ($configuration, $user, $componentIds, $notes): ConfigurationVersion {
            $lockedConfiguration = Configuration::query()
                ->whereKey($configuration->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedConfiguration->vehicle()->whereNull('vehicles.deleted_at')->where('status', 'active')->exists()) {
                throw ValidationException::withMessages([
                    'configuration' => __('This vehicle is archived. Choose an active vehicle to create a configuration version.'),
                ]);
            }

            $componentIds = array_values(array_unique(array_map('intval', $componentIds)));

            $components = Component::query()
                ->where('workspace_id', $lockedConfiguration->workspace_id)
                ->whereIn('id', $componentIds)
                ->where('status', 'active')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($components->count() !== count($componentIds)) {
                throw ValidationException::withMessages([
                    'component_ids' => __('One or more selected components are unavailable.'),
                ]);
            }

            if (ComponentInstallation::query()
                ->whereIn('component_id', $componentIds)
                ->whereNull('removed_at')
                ->where('vehicle_id', '!=', $lockedConfiguration->vehicle_id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'component_ids' => __('Remove components from their current vehicle before using them in another configuration.'),
                ]);
            }

            $nextVersion = ((int) $lockedConfiguration->versions()->max('version_number')) + 1;

            $version = $lockedConfiguration->versions()->create([
                'version_number' => $nextVersion,
                'created_by' => $user->getKey(),
                'notes' => $notes,
            ]);

            if ($componentIds !== []) {
                $version->components()->attach($componentIds);
            }

            $this->syncInstallations($lockedConfiguration, $user, $componentIds);

            return $version->load('components.trackers.metric');
        });
    }

    /**
     * @param  list<int>  $componentIds
     */
    private function syncInstallations(Configuration $configuration, User $user, array $componentIds): void
    {
        $active = ComponentInstallation::query()
            ->where('vehicle_id', $configuration->vehicle_id)
            ->whereNull('removed_at')
            ->lockForUpdate()
            ->get();

        $selected = collect($componentIds);
        $now = now();

        foreach ($active as $installation) {
            if (! $selected->contains($installation->component_id)) {
                $installation->update(['removed_at' => $now]);
            }
        }

        $activeComponentIds = $active
            ->whereNull('removed_at')
            ->pluck('component_id')
            ->map(fn ($id): int => (int) $id);

        foreach ($selected->diff($activeComponentIds) as $componentId) {
            ComponentInstallation::create([
                'vehicle_id' => $configuration->vehicle_id,
                'component_id' => (int) $componentId,
                'created_by' => $user->getKey(),
                'installed_at' => $now,
            ]);
        }
    }
}
