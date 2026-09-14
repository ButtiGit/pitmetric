<?php

namespace App\Http\Controllers;

use App\Models\ComponentTracker;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Services\CompleteMaintenanceService;
use App\Services\ComponentUsageCalculator;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext, ComponentUsageCalculator $usageCalculator): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $this->hasDatabaseAccess($user)) {
            return view('demo.workspace', ['initialSection' => 'maintenance']);
        }

        if (! $workspaceContext->isCoreReady()) {
            return view('garage.unavailable');
        }

        $workspace = $workspaceContext->personal($user);

        $trackers = ComponentTracker::query()
            ->whereHas('component', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
            ->with(['component.type', 'metric'])
            ->orderBy('component_id')
            ->get();

        $schedules = MaintenanceSchedule::query()
            ->with(['tracker.component', 'tracker.metric'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $states = [];

        foreach ($schedules as $schedule) {
            $states[$schedule->id] = [
                'used' => $usageCalculator->sinceLastService($schedule->tracker),
                'lifetime' => $usageCalculator->lifetime($schedule->tracker),
                'status' => $usageCalculator->status($schedule->tracker, $schedule->interval_value, $schedule->warning_value),
            ];
        }

        return view('maintenance.index', [
            'trackers' => $trackers,
            'schedules' => $schedules,
            'states' => $states,
            'records' => MaintenanceRecord::query()
                ->with(['component', 'schedule.tracker.metric'])
                ->latest('performed_at')
                ->limit(25)
                ->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);

        $validated = $request->validate([
            'component_tracker_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'interval_display' => ['required', 'numeric', 'gt:0'],
            'warning_display' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tracker = ComponentTracker::query()
            ->whereKey($validated['component_tracker_id'])
            ->whereHas('component', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
            ->with('metric')
            ->firstOrFail();

        MaintenanceSchedule::create([
            'component_tracker_id' => $tracker->getKey(),
            'name' => $validated['name'],
            'interval_value' => $this->toStorageValue($tracker, (float) $validated['interval_display']),
            'warning_value' => isset($validated['warning_display'])
                ? $this->toStorageValue($tracker, (float) $validated['warning_display'])
                : null,
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return to_route('demo.maintenance')->with('status', __('Maintenance schedule created.'));
    }

    public function complete(Request $request, MaintenanceSchedule $maintenanceSchedule, CompleteMaintenanceService $maintenanceService): RedirectResponse
    {
        Gate::authorize('update', $maintenanceSchedule);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $validated = $request->validate([
            'performed_at' => ['required', 'date'],
            'description' => ['required', 'string', 'max:180'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $maintenanceService->complete(
            $maintenanceSchedule,
            $user,
            Carbon::parse($validated['performed_at']),
            $validated['description'],
            isset($validated['cost']) ? (int) round(((float) $validated['cost']) * 100) : null,
            $validated['notes'] ?? null,
        );

        return to_route('demo.maintenance')->with('status', __('Maintenance completed and interval reset.'));
    }

    private function toStorageValue(ComponentTracker $tracker, float $value): int
    {
        return match ($tracker->metric->key) {
            'distance' => (int) round($value * 1000),
            'runtime' => (int) round($value * 3600),
            default => (int) round($value),
        };
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
