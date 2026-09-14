<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\User;
use App\Models\Vehicle;
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

        $vehicles = Vehicle::query()->orderBy('name')->get();

        return view('garage.index', compact('workspace', 'vehicles'));
    }

    public function store(StoreVehicleRequest $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        if (! $workspaceContext->isReady()) {
            return to_route('demo.garage')->with('error', __('garage.messages.unavailable'));
        }

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceContext->personal($user);
        Vehicle::create($request->validated());

        return to_route('demo.garage')->with('status', __('garage.messages.created'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return to_route('demo.garage')->with('status', __('garage.messages.updated'));
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('delete', $vehicle);
        $vehicle->delete();

        return to_route('demo.garage')->with('status', __('garage.messages.deleted'));
    }
}
