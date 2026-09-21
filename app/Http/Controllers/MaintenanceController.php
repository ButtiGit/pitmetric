<?php

namespace App\Http\Controllers;

use App\Models\ComponentTracker;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\User;
use App\Services\CompleteMaintenanceService;
use App\Services\MaintenancePageService;
use App\Services\MaintenanceValueService;
use App\Services\MaintenanceWorkOrderService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(
        Request $request,
        WorkspaceContext $workspaceContext,
        MaintenancePageService $pageService,
    ): View {
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

        return view('maintenance.index', $pageService->data($workspace));
    }

    public function store(
        Request $request,
        WorkspaceContext $workspaceContext,
        MaintenanceValueService $valueService,
    ): RedirectResponse {
        $user = $this->authenticatedUser($request);
        $workspace = $workspaceContext->personal($user);
        $validated = $request->validate([
            'component_tracker_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'interval_display' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'warning_display' => ['nullable', 'numeric', 'min:0', 'lte:interval_display'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $tracker = ComponentTracker::query()
            ->whereKey($validated['component_tracker_id'])
            ->whereHas('component', fn ($query) => $query->whereNull('components.deleted_at')->where('status', 'active')->where('workspace_id', $workspace->getKey()))
            ->with('metric')
            ->firstOrFail();

        $interval = $valueService->toStorageValue($tracker, (float) $validated['interval_display']);
        if ($interval < 1) {
            throw ValidationException::withMessages(['interval_display' => __('The interval must be at least one tracked unit.')]);
        }

        MaintenanceSchedule::create([
            'component_tracker_id' => $tracker->getKey(),
            'name' => $validated['name'],
            'interval_value' => $interval,
            'warning_value' => isset($validated['warning_display'])
                ? $valueService->toStorageValue($tracker, (float) $validated['warning_display'])
                : null,
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return to_route('maintenance.index')->with('status', __('Maintenance schedule created.'));
    }

    public function update(
        Request $request,
        MaintenanceSchedule $maintenanceSchedule,
        MaintenanceValueService $valueService,
    ): RedirectResponse {
        Gate::authorize('update', $maintenanceSchedule);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'interval_display' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'warning_display' => ['nullable', 'numeric', 'min:0', 'lte:interval_display'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $interval = $valueService->toStorageValue($maintenanceSchedule->tracker, (float) $validated['interval_display']);
        if ($interval < 1) {
            throw ValidationException::withMessages(['interval_display' => __('The interval must be at least one tracked unit.')]);
        }

        $maintenanceSchedule->update([
            'name' => $validated['name'],
            'interval_value' => $interval,
            'warning_value' => isset($validated['warning_display'])
                ? $valueService->toStorageValue($maintenanceSchedule->tracker, (float) $validated['warning_display'])
                : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return to_route('maintenance.index')->with('status', __('Maintenance schedule updated.'));
    }

    public function destroy(MaintenanceSchedule $maintenanceSchedule): RedirectResponse
    {
        Gate::authorize('delete', $maintenanceSchedule);
        DB::transaction(function () use ($maintenanceSchedule): void {
            $schedule = MaintenanceSchedule::query()->whereKey($maintenanceSchedule->getKey())->lockForUpdate()->firstOrFail();
            if (MaintenanceWorkOrder::query()
                ->where('maintenance_schedule_id', $schedule->getKey())
                ->whereIn('status', MaintenanceWorkOrder::OPEN_STATUSES)
                ->exists()) {
                throw ValidationException::withMessages([
                    'schedule' => __('Complete or cancel the open work order before archiving this schedule.'),
                ]);
            }
            $schedule->update(['is_active' => false]);
        });

        return to_route('maintenance.index')->with('status', __('Maintenance schedule archived. Completed work is preserved.'));
    }

    public function restore(MaintenanceSchedule $maintenanceSchedule): RedirectResponse
    {
        Gate::authorize('update', $maintenanceSchedule);
        $maintenanceSchedule->update(['is_active' => true]);

        return to_route('maintenance.index')->with('status', __('Maintenance schedule restored.'));
    }

    public function destroyWorkOrder(
        MaintenanceWorkOrder $maintenanceWorkOrder,
        MaintenanceWorkOrderService $workOrderService,
    ): RedirectResponse {
        Gate::authorize('delete', $maintenanceWorkOrder);
        $workOrderService->cancel($maintenanceWorkOrder);

        return to_route('maintenance.index')->with('status', __('Maintenance work order cancelled.'));
    }

    public function storeWorkOrder(
        Request $request,
        WorkspaceContext $workspaceContext,
        MaintenanceWorkOrderService $workOrderService,
    ): RedirectResponse {
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

        $workOrderService->create($workspace, $user, $validated);

        return to_route('maintenance.index')->with('status', __('Maintenance work order added to the board.'));
    }

    public function updateWorkOrder(
        Request $request,
        MaintenanceWorkOrder $maintenanceWorkOrder,
        WorkspaceContext $workspaceContext,
        MaintenanceWorkOrderService $workOrderService,
    ): RedirectResponse {
        Gate::authorize('update', $maintenanceWorkOrder);
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

        $workOrderService->update($maintenanceWorkOrder, $workspace, $validated);

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
            $maintenanceWorkOrder,
        );

        return to_route('maintenance.index')->with('status', __('Maintenance completed, work order closed and cost linked to expenses.'));
    }

    public function complete(
        Request $request,
        MaintenanceSchedule $maintenanceSchedule,
        CompleteMaintenanceService $maintenanceService,
    ): RedirectResponse {
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

    private function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
