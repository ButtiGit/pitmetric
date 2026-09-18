<?php

namespace App\Http\Controllers;

use App\Models\ComponentTracker;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Models\Workspace;
use App\Services\CompleteMaintenanceService;
use App\Services\MaintenanceHealthService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext, MaintenanceHealthService $healthService): View
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
            ->whereHas('component', fn ($query) => $query->whereNull('components.deleted_at')->where('status', 'active')->where('workspace_id', $workspace->getKey()))
            ->with(['component.type', 'metric'])
            ->orderBy('component_id')
            ->get();

        $schedules = MaintenanceSchedule::query()
            ->with(['tracker.component', 'tracker.metric'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $health = $healthService->snapshot($schedules);

        $workOrders = MaintenanceWorkOrder::query()
            ->with(['schedule.tracker.component', 'schedule.tracker.metric', 'assignee'])
            ->whereIn('status', MaintenanceWorkOrder::OPEN_STATUSES)
            ->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->orderBy('created_at')
            ->get();

        return view('maintenance.index', [
            'trackers' => $trackers,
            'schedules' => $schedules,
            'states' => $health['states'],
            'summary' => $health['summary'],
            'workOrders' => $workOrders,
            'workSummary' => [
                'open' => $workOrders->count(),
                'in_progress' => $workOrders->where('status', 'in_progress')->count(),
                'blocked' => $workOrders->where('status', 'blocked')->count(),
            ],
            'activeWorkOrderScheduleIds' => $workOrders->pluck('maintenance_schedule_id')->all(),
            'members' => $workspace->users()
                ->wherePivot('status', 'active')
                ->orderBy('name')
                ->get(),
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
            ->whereHas('component', fn ($query) => $query->whereNull('components.deleted_at')->where('status', 'active')->where('workspace_id', $workspace->getKey()))
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

        return to_route('maintenance.index')->with('status', __('Maintenance schedule created.'));
    }

    public function storeWorkOrder(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $workspace = $workspaceContext->personal($user);

        $validated = $request->validate([
            'maintenance_schedule_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'assigned_to' => ['nullable', 'integer'],
            'priority' => ['required', 'in:low,normal,high,critical'],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $schedule = MaintenanceSchedule::query()
            ->whereKey($validated['maintenance_schedule_id'])
            ->where('is_active', true)
            ->firstOrFail();

        if (MaintenanceWorkOrder::query()
            ->where('maintenance_schedule_id', $schedule->getKey())
            ->whereIn('status', MaintenanceWorkOrder::OPEN_STATUSES)
            ->exists()) {
            throw ValidationException::withMessages([
                'maintenance_schedule_id' => __('This maintenance schedule already has an open work order.'),
            ]);
        }

        $assignedTo = $this->activeAssigneeId($workspace, $validated['assigned_to'] ?? null);

        MaintenanceWorkOrder::create([
            'maintenance_schedule_id' => $schedule->getKey(),
            'assigned_to' => $assignedTo,
            'title' => $validated['title'],
            'priority' => $validated['priority'],
            'status' => 'todo',
            'due_at' => isset($validated['due_at']) ? Carbon::parse($validated['due_at']) : null,
            'created_by' => $user->getKey(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return to_route('maintenance.index')->with('status', __('Maintenance work order added to the board.'));
    }

    public function updateWorkOrder(
        Request $request,
        MaintenanceWorkOrder $maintenanceWorkOrder,
        WorkspaceContext $workspaceContext,
    ): RedirectResponse {
        Gate::authorize('update', $maintenanceWorkOrder);

        if (! in_array($maintenanceWorkOrder->status, MaintenanceWorkOrder::OPEN_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => __('Closed maintenance work orders cannot be changed.'),
            ]);
        }

        $user = $this->authenticatedUser($request);
        $workspace = $workspaceContext->personal($user);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'assigned_to' => ['nullable', 'integer'],
            'priority' => ['required', 'in:low,normal,high,critical'],
            'status' => ['required', 'in:todo,in_progress,blocked,cancelled'],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $assignedTo = $this->activeAssigneeId($workspace, $validated['assigned_to'] ?? null);
        $startedAt = $maintenanceWorkOrder->started_at;

        if ($validated['status'] === 'in_progress' && $startedAt === null) {
            $startedAt = now();
        }

        $maintenanceWorkOrder->update([
            'title' => $validated['title'],
            'assigned_to' => $assignedTo,
            'priority' => $validated['priority'],
            'status' => $validated['status'],
            'due_at' => isset($validated['due_at']) ? Carbon::parse($validated['due_at']) : null,
            'started_at' => $startedAt,
            'notes' => $validated['notes'] ?? null,
        ]);

        return to_route('maintenance.index')->with('status', __('Maintenance work order updated.'));
    }

    public function completeWorkOrder(
        Request $request,
        MaintenanceWorkOrder $maintenanceWorkOrder,
        CompleteMaintenanceService $maintenanceService,
    ): RedirectResponse {
        Gate::authorize('update', $maintenanceWorkOrder);

        if (! in_array($maintenanceWorkOrder->status, MaintenanceWorkOrder::OPEN_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => __('Only open maintenance work orders can be completed.'),
            ]);
        }

        $user = $this->authenticatedUser($request);
        $validated = $this->validatedCompletion($request);

        $schedule = $maintenanceWorkOrder->schedule()->firstOrFail();
        Gate::authorize('update', $schedule);

        $maintenanceService->complete(
            $schedule,
            $user,
            Carbon::parse($validated['performed_at']),
            $validated['description'],
            isset($validated['cost']) ? (int) round(((float) $validated['cost']) * 100) : null,
            $validated['notes'] ?? null,
        );

        return to_route('maintenance.index')->with('status', __('Maintenance completed, work order closed and cost linked to expenses.'));
    }

    public function complete(Request $request, MaintenanceSchedule $maintenanceSchedule, CompleteMaintenanceService $maintenanceService): RedirectResponse
    {
        Gate::authorize('update', $maintenanceSchedule);

        $user = $this->authenticatedUser($request);
        $validated = $this->validatedCompletion($request);

        $maintenanceService->complete(
            $maintenanceSchedule,
            $user,
            Carbon::parse($validated['performed_at']),
            $validated['description'],
            isset($validated['cost']) ? (int) round(((float) $validated['cost']) * 100) : null,
            $validated['notes'] ?? null,
        );

        return to_route('maintenance.index')->with('status', __('Maintenance completed, interval reset and cost linked to expenses.'));
    }

    /**
     * @return array{performed_at: string, description: string, cost?: numeric-string|float|int|null, notes?: string|null}
     */
    private function validatedCompletion(Request $request): array
    {
        return $request->validate([
            'performed_at' => ['required', 'date'],
            'description' => ['required', 'string', 'max:180'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function activeAssigneeId(Workspace $workspace, mixed $assignedTo): ?int
    {
        if ($assignedTo === null || $assignedTo === '') {
            return null;
        }

        $assigneeId = (int) $assignedTo;
        $exists = $workspace->users()
            ->where('users.id', $assigneeId)
            ->wherePivot('status', 'active')
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'assigned_to' => __('The assignee must be an active member of the current team.'),
            ]);
        }

        return $assigneeId;
    }

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
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
