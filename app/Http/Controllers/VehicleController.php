<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Vehicle::class);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $user->workspaces()->orderBy('workspaces.id')->firstOrFail();
        $vehicles = Vehicle::query()->orderBy('name')->get();

        return view('garage.index', compact('workspace', 'vehicles'));
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
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
