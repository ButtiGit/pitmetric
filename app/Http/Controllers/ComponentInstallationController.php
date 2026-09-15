<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\OperationCostService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ComponentInstallationController extends Controller
{
    public function store(
        Request $request,
        WorkspaceContext $workspaceContext,
        OperationCostService $costService,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);
        $validated = $request->validate([
            'component_id' => ['required', 'integer'],
            'vehicle_id' => ['required', 'integer'],
            'position_or_role' => ['nullable', 'string', 'max:100'],
            'installed_at' => ['required', 'date'],
            'operation_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $vehicle = Vehicle::query()
            ->whereKey((int) $validated['vehicle_id'])
            ->where('workspace_id', $workspace->getKey())
            ->where('status', 'active')
            ->firstOrFail();

        DB::transaction(function () use ($validated, $workspace, $vehicle, $user, $costService): void {
            $component = Component::query()
                ->whereKey((int) $validated['component_id'])
                ->where('workspace_id', $workspace->getKey())
                ->where('status', 'active')
                ->lockForUpdate()
                ->firstOrFail();

            if ($component->installations()->whereNull('removed_at')->exists()) {
                throw ValidationException::withMessages([
                    'component_id' => __('This component is already installed on a vehicle. Remove it before installing it elsewhere.'),
                ]);
            }

            $installedAt = Carbon::parse($validated['installed_at']);
            $installation = ComponentInstallation::create([
                'vehicle_id' => $vehicle->getKey(),
                'component_id' => $component->getKey(),
                'created_by' => $user->getKey(),
                'position_or_role' => $validated['position_or_role'] ?? null,
                'installed_at' => $installedAt,
                'notes' => $validated['notes'] ?? null,
            ]);

            $costService->record(
                $user,
                isset($validated['operation_cost']) ? (float) $validated['operation_cost'] : null,
                'workshop',
                __('Install').': '.$component->name.' → '.$vehicle->name,
                'component_installation_install',
                (int) $installation->getKey(),
                $installedAt,
            );
        });

        return to_route('components.index')->with('status', __('Component installed.'));
    }

    public function remove(
        Request $request,
        ComponentInstallation $componentInstallation,
        OperationCostService $costService,
    ): RedirectResponse {
        Gate::authorize('update', $componentInstallation);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $validated = $request->validate([
            'removed_at' => ['required', 'date'],
            'operation_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);

        $removedAt = Carbon::parse($validated['removed_at']);

        if ($componentInstallation->removed_at !== null) {
            throw ValidationException::withMessages([
                'removed_at' => __('This component installation is already closed.'),
            ]);
        }

        if ($removedAt->lt($componentInstallation->installed_at)) {
            throw ValidationException::withMessages([
                'removed_at' => __('Removal time cannot be earlier than installation time.'),
            ]);
        }

        DB::transaction(function () use ($componentInstallation, $removedAt, $validated, $user, $costService): void {
            $componentInstallation->update(['removed_at' => $removedAt]);
            $componentInstallation->loadMissing(['component', 'vehicle']);

            $costService->record(
                $user,
                isset($validated['operation_cost']) ? (float) $validated['operation_cost'] : null,
                'workshop',
                __('Remove').': '.$componentInstallation->component->name.' ← '.$componentInstallation->vehicle->name,
                'component_installation_remove',
                (int) $componentInstallation->getKey(),
                $removedAt,
            );
        });

        return to_route('components.index')->with('status', __('Component removed. Installation history preserved.'));
    }
}
