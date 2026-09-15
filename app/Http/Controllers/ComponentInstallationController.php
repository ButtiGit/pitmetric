<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ComponentInstallationController extends Controller
{
    public function store(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
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
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $vehicle = Vehicle::query()
            ->whereKey((int) $validated['vehicle_id'])
            ->where('workspace_id', $workspace->getKey())
            ->where('status', 'active')
            ->firstOrFail();

        DB::transaction(function () use ($validated, $workspace, $vehicle, $user): void {
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

            ComponentInstallation::create([
                'vehicle_id' => $vehicle->getKey(),
                'component_id' => $component->getKey(),
                'created_by' => $user->getKey(),
                'position_or_role' => $validated['position_or_role'] ?? null,
                'installed_at' => Carbon::parse($validated['installed_at']),
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return to_route('components.index')->with('status', __('Component installed.'));
    }

    public function remove(Request $request, ComponentInstallation $componentInstallation): RedirectResponse
    {
        Gate::authorize('update', $componentInstallation);

        $validated = $request->validate([
            'removed_at' => ['required', 'date'],
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

        $componentInstallation->update(['removed_at' => $removedAt]);

        return to_route('components.index')->with('status', __('Component removed. Installation history preserved.'));
    }
}
