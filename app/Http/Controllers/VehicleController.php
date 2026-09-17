<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\OperationCostService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $hasDatabaseAccess = Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();

        if (! $hasDatabaseAccess) {
            return view('demo.workspace', ['initialSection' => 'garage']);
        }

        if (! $workspaceContext->isReady()) {
            return view('garage.unavailable');
        }

        $workspace = $workspaceContext->personal($user);

        Gate::authorize('viewAny', Vehicle::class);

        $vehicles = Vehicle::query()
            ->where('status', 'active')
            ->with([
                'componentInstallations' => fn ($query) => $query
                    ->whereNull('removed_at')
                    ->with('component.type')
                    ->orderBy('position_or_role')
                    ->orderByDesc('installed_at'),
            ])
            ->orderBy('name')
            ->get();

        return view('garage.index', compact('workspace', 'vehicles'));
    }

    public function store(
        StoreVehicleRequest $request,
        WorkspaceContext $workspaceContext,
        OperationCostService $costService,
    ): RedirectResponse {
        if (! $workspaceContext->isReady()) {
            return to_route('garage.index')->with('error', __('garage.messages.unavailable'));
        }

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceContext->personal($user);
        $validated = $request->validated();
        $purchaseCost = isset($validated['purchase_cost']) ? (float) $validated['purchase_cost'] : null;
        unset($validated['purchase_cost']);

        $vehicle = Vehicle::create($validated);

        $costService->record(
            $user,
            $purchaseCost,
            'vehicle',
            __('Vehicle acquisition').': '.$vehicle->name,
            'vehicle',
            (int) $vehicle->getKey(),
            now(),
        );

        return to_route('garage.index')->with('status', __('garage.messages.created'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return to_route('garage.index')->with('status', __('garage.messages.updated'));
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('delete', $vehicle);
        $vehicle->update(['status' => 'inactive']);

        return to_route('garage.index')->with('status', __('garage.messages.deleted'));
    }
}
