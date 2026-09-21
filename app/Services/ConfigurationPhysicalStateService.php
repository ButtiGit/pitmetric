<?php

namespace App\Services;

use App\Models\ConfigurationVersion;
use App\Models\Workspace;
use Illuminate\Support\Collection;

class ConfigurationPhysicalStateService
{
    public function matches(ConfigurationVersion $version): bool
    {
        $version->loadMissing([
            'configuration.vehicle.componentInstallations' => fn ($query) => $query->whereNull('removed_at'),
            'components',
        ]);

        $physicalIds = $version->configuration->vehicle->componentInstallations
            ->pluck('component_id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($physicalIds === []) {
            return false;
        }

        $snapshotIds = $version->components
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        return $physicalIds === $snapshotIds;
    }

    /** @return Collection<int, ConfigurationVersion> */
    public function alignedVersions(Workspace $workspace): Collection
    {
        return ConfigurationVersion::query()
            ->whereHas('configuration', fn ($query) => $query
                ->whereNull('configurations.deleted_at')
                ->where('status', 'active')
                ->whereHas('vehicle', fn ($vehicle) => $vehicle->whereNull('vehicles.deleted_at')->where('status', 'active'))
                ->where('workspace_id', $workspace->getKey()))
            ->with([
                'configuration.vehicle.componentInstallations' => fn ($query) => $query->whereNull('removed_at'),
                'components',
            ])
            ->orderByDesc('id')
            ->get()
            ->filter(fn (ConfigurationVersion $version): bool => $this->matches($version))
            ->values();
    }
}
