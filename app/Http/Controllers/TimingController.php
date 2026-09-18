<?php

namespace App\Http\Controllers;

use App\Models\Circuit;
use App\Models\Driver;
use App\Models\Session;
use App\Models\TimingLap;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\WorkspaceContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimingController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $workspaceContext->isTechnicalSetupReady()) {
            return view('garage.unavailable');
        }

        $workspace = $workspaceContext->personal($user);
        $filters = $request->validate([
            'session_id' => ['nullable', 'integer'],
            'circuit_id' => ['nullable', 'integer'],
            'driver_id' => ['nullable', 'integer'],
            'vehicle_category' => ['nullable', 'string', 'max:40'],
        ]);

        $query = TimingLap::query()
            ->with([
                'session',
                'driver',
                'vehicle',
                'circuitLayout.circuit',
                'telemetryImport',
            ])
            ->where('is_valid', true);

        if (! empty($filters['session_id'])) {
            $query->where('session_id', (int) $filters['session_id']);
        }

        if (! empty($filters['circuit_id'])) {
            $query->whereHas('circuitLayout.circuit', fn ($circuitQuery) => $circuitQuery->whereKey((int) $filters['circuit_id']));
        }

        if (! empty($filters['driver_id'])) {
            $query->where('driver_id', (int) $filters['driver_id']);
        }

        if (! empty($filters['vehicle_category'])) {
            $query->whereHas('vehicle', fn ($vehicleQuery) => $vehicleQuery->where('category', $filters['vehicle_category']));
        }

        $laps = $query
            ->orderBy('lap_time_ms')
            ->limit(250)
            ->get();

        return view('timing.index', [
            'laps' => $laps,
            'filters' => $filters,
            'summary' => [
                'count' => $laps->count(),
                'best_ms' => $laps->min('lap_time_ms'),
                'average_ms' => $laps->isEmpty() ? null : (int) round($laps->avg('lap_time_ms')),
                'drivers' => $laps->pluck('driver_id')->filter()->unique()->count(),
            ],
            'sessions' => Session::query()
                ->with(['vehicle', 'circuitLayout.circuit', 'eventEntry.driver'])
                ->latest('started_at')
                ->latest('id')
                ->limit(150)
                ->get(),
            'circuits' => Circuit::query()->where('workspace_id', $workspace->getKey())->orderBy('name')->get(),
            'drivers' => Driver::query()->where('status', 'active')->orderBy('display_name')->get(),
            'vehicleCategories' => Vehicle::query()
                ->where('workspace_id', $workspace->getKey())
                ->where('status', 'active')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
        ]);
    }
}
