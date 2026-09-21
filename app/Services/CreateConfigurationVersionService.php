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
     * Create an immutable configuration snapshot from the vehicle's current
     * physical component state.
     *
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
            sort($componentIds);

            $installedComponentIds = ComponentInstallation::query()
                ->where('vehicle_id', $lockedConfiguration->vehicle_id)
                ->whereNull('removed_at')
                ->lockForUpdate()
                ->pluck('component_id')
                ->map(static fn ($id): int => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($installedComponentIds === []) {
                throw ValidationException::withMessages([
                    'components' => __('Install at least one component on the vehicle before creating a configuration version.'),
                ]);
            }

            if ($componentIds !== $installedComponentIds) {
                throw ValidationException::withMessages([
                    'components' => __('A configuration version must match the components currently installed on the vehicle. Update the physical components first, then create a new configuration version.'),
                ]);
            }

            $components = Component::query()
                ->where('workspace_id', $lockedConfiguration->workspace_id)
                ->whereIn('id', $installedComponentIds)
                ->where('status', 'active')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($components->count() !== count($installedComponentIds)) {
                throw ValidationException::withMessages([
                    'components' => __('One or more installed components are unavailable. Fix the vehicle physical state before creating a configuration version.'),
                ]);
            }

            $nextVersion = ((int) $lockedConfiguration->versions()->max('version_number')) + 1;

            $version = $lockedConfiguration->versions()->create([
                'version_number' => $nextVersion,
                'created_by' => $user->getKey(),
                'notes' => $notes,
            ]);

            $version->components()->attach($installedComponentIds);

            return $version->load('components.trackers.metric');
        });
    }
}
