<?php

namespace App\Services;

use App\Models\CircuitLayout;
use App\Models\ConfigurationVersion;
use App\Models\MaintenanceSchedule;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\Workspace;
use Illuminate\Http\Request;

class SessionPageService
{
    public function __construct(
        private readonly ConfigurationPhysicalStateService $physicalState,
        private readonly MaintenanceHealthService $healthService,
    ) {}

    /** @return array<string, mixed> */
    public function data(Request $request, Workspace $workspace): array
    {
        $sessions = Session::query()
            ->with([
                'vehicle',
                'configurationVersion.configuration',
                'circuitLayout.circuit',
                'usageValues.metric',
                'raceEvent',
                'eventEntry.driver',
                'setupSnapshot.technicalSetup',
            ])
            ->latest('started_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $versions = $this->physicalState->alignedVersions($workspace);

        $schedules = MaintenanceSchedule::query()
            ->with(['tracker.component', 'tracker.metric'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $health = $this->healthService->snapshot($schedules);
        $attentionSchedules = $schedules->filter(function (MaintenanceSchedule $schedule) use ($health): bool {
            $status = $health['states'][$schedule->getKey()]['status'] ?? 'untracked';

            return in_array($status, ['due_soon', 'overdue'], true);
        });

        $lastSession = $sessions->first();
        $requestedVersionId = $request->integer('configuration_version_id');
        $defaultVersionId = $versions->contains(fn (ConfigurationVersion $version): bool => $version->getKey() === $requestedVersionId)
            ? $requestedVersionId
            : ($versions->contains(fn (ConfigurationVersion $version): bool => $version->getKey() === $lastSession?->configuration_version_id)
                ? $lastSession?->configuration_version_id
                : $versions->first()?->getKey());

        return [
            'versions' => $versions,
            'setups' => TechnicalSetup::query()
                ->with('vehicle')
                ->whereHas('vehicle', fn ($query) => $query->whereNull('vehicles.deleted_at')->where('status', 'active'))
                ->where('status', 'active')
                ->orderBy('vehicle_id')
                ->orderBy('name')
                ->get(),
            'layouts' => CircuitLayout::query()
                ->whereHas('circuit', fn ($query) => $query->whereNull('circuits.deleted_at')->where('workspace_id', $workspace->getKey()))
                ->with('circuit')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'sessions' => $sessions,
            'defaults' => [
                'configuration_version_id' => $defaultVersionId,
                'technical_setup_id' => $lastSession?->setupSnapshot?->technical_setup_id,
                'circuit_layout_id' => $lastSession?->circuit_layout_id,
                'session_type' => $lastSession->session_type ?? 'practice',
                'started_at' => now()->format('Y-m-d\TH:i'),
            ],
            'maintenanceSummary' => $health['summary'],
            'maintenanceStates' => $health['states'],
            'attentionSchedules' => $attentionSchedules,
        ];
    }
}
