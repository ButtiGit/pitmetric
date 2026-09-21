<?php

namespace App\Services;

use App\Models\ComponentInstallation;
use App\Models\Configuration;
use App\Models\MaintenanceSchedule;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\UsageBatch;
use App\Models\Vehicle;

class CoreWorkflowService
{
    /**
     * @return array{
     *     steps: array<string, array{complete: bool, route: string|null}>,
     *     next: array{stage: string, route: string},
     *     complete: bool
     * }
     */
    public function snapshot(bool $eventsReady): array
    {
        $vehicleReady = Vehicle::query()
            ->where('status', 'active')
            ->exists();

        $componentsReady = $vehicleReady && ComponentInstallation::query()
            ->whereNull('removed_at')
            ->whereHas('vehicle', fn ($query) => $query->whereNull('vehicles.deleted_at')->where('status', 'active'))
            ->whereHas('component', fn ($query) => $query->whereNull('components.deleted_at')->where('status', 'active'))
            ->exists();

        $configurationReady = $componentsReady && $this->hasAlignedConfiguration();

        $eventReady = $configurationReady && $eventsReady && RaceEvent::query()->exists();

        $sessionReady = $configurationReady && Session::query()
            ->where('status', 'finalized')
            ->exists();

        $usageReady = $sessionReady && UsageBatch::query()
            ->where('source_type', 'session')
            ->whereHas('entries')
            ->exists();

        $maintenanceReady = $usageReady && MaintenanceSchedule::query()
            ->where('is_active', true)
            ->exists();

        $steps = [
            'vehicle' => ['complete' => $vehicleReady, 'route' => 'garage.index'],
            'components' => ['complete' => $componentsReady, 'route' => 'components.index'],
            'configuration' => ['complete' => $configurationReady, 'route' => 'configurations.index'],
            'event' => ['complete' => $eventReady, 'route' => $eventsReady ? 'events.index' : null],
            'session' => ['complete' => $sessionReady, 'route' => 'sessions.index'],
            'usage' => ['complete' => $usageReady, 'route' => null],
            'maintenance' => ['complete' => $maintenanceReady, 'route' => 'maintenance.index'],
        ];

        $next = match (true) {
            ! $vehicleReady => ['stage' => 'vehicle', 'route' => 'garage.index'],
            ! $componentsReady => ['stage' => 'components', 'route' => 'components.index'],
            ! $configurationReady => ['stage' => 'configuration', 'route' => 'configurations.index'],
            $eventsReady && ! $eventReady => ['stage' => 'event', 'route' => 'events.index'],
            ! $sessionReady => ['stage' => 'session', 'route' => 'sessions.index'],
            ! $usageReady => ['stage' => 'usage', 'route' => 'sessions.index'],
            ! $maintenanceReady => ['stage' => 'maintenance', 'route' => 'maintenance.index'],
            $eventsReady => ['stage' => 'event', 'route' => 'events.index'],
            default => ['stage' => 'session', 'route' => 'sessions.index'],
        };

        return [
            'steps' => $steps,
            'next' => $next,
            'complete' => $vehicleReady
                && $componentsReady
                && $configurationReady
                && $sessionReady
                && $usageReady
                && $maintenanceReady,
        ];
    }

    private function hasAlignedConfiguration(): bool
    {
        $configurations = Configuration::query()
            ->where('status', 'active')
            ->whereHas('vehicle', fn ($query) => $query->whereNull('vehicles.deleted_at')->where('status', 'active'))
            ->with([
                'vehicle.componentInstallations' => fn ($query) => $query->whereNull('removed_at'),
                'versions' => fn ($query) => $query->orderByDesc('version_number')->with('components:id'),
            ])
            ->get();

        return $configurations->contains(function (Configuration $configuration): bool {
            $latest = $configuration->versions->first();

            if ($latest === null) {
                return false;
            }

            $physicalIds = $configuration->vehicle->componentInstallations
                ->pluck('component_id')
                ->map(static fn ($id): int => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($physicalIds === []) {
                return false;
            }

            $snapshotIds = $latest->components
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->sort()
                ->values()
                ->all();

            return $physicalIds === $snapshotIds;
        });
    }
}
