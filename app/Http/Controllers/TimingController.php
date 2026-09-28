<?php

namespace App\Http\Controllers;

use App\Models\Circuit;
use App\Models\Driver;
use App\Models\Session;
use App\Models\TimingLap;
use App\Models\TracksideCapture;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\OperationalAlertNotification;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TimingController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $workspaceContext->isCoreReady()) {
            return view('garage.unavailable');
        }

        $workspace = $workspaceContext->personal($user);
        $filters = $request->validate([
            'session_id' => ['nullable', 'integer'],
            'circuit_id' => ['nullable', 'integer'],
            'driver_id' => ['nullable', 'integer'],
            'vehicle_category' => ['nullable', 'string', 'max:40'],
            'pending' => ['nullable', 'boolean'],
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

        $quickCapturesQuery = TracksideCapture::query()
            ->with(['circuitLayout.circuit', 'driver', 'vehicle'])
            ->where('capture_type', 'lap_time');

        if (! empty($filters['pending'])) {
            $quickCapturesQuery->where('status', 'pending');
        }

        $quickCaptures = $quickCapturesQuery
            ->latest('captured_at')
            ->latest('id')
            ->limit(100)
            ->get();

        return view('timing.index', [
            'laps' => $laps,
            'quickCaptures' => $quickCaptures,
            'pendingCaptureCount' => TracksideCapture::query()
                ->where('capture_type', 'lap_time')
                ->where('status', 'pending')
                ->count(),
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
            'vehicles' => Vehicle::query()
                ->where('workspace_id', $workspace->getKey())
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(150)
                ->get(),
            'vehicleCategories' => Vehicle::query()
                ->where('workspace_id', $workspace->getKey())
                ->where('status', 'active')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
        ]);
    }

    public function storeQuickCapture(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $workspaceContext->isCoreReady()) {
            abort(503, 'PitMetric workspace is not ready.');
        }

        $workspace = $workspaceContext->personal($user);
        $validated = $request->validate([
            'lap_time' => ['required', 'string', 'max:20'],
            'circuit_name' => ['required', 'string', 'max:120'],
            'driver_name' => ['nullable', 'string', 'max:120'],
            'vehicle_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $lapTimeMs = $this->parseLapTime((string) $validated['lap_time']);
        $circuitName = trim((string) $validated['circuit_name']);
        $driverName = trim((string) ($validated['driver_name'] ?? '')) ?: null;
        $vehicleName = trim((string) ($validated['vehicle_name'] ?? '')) ?: null;
        $circuitLayoutId = $this->matchCircuitLayout((int) $workspace->getKey(), $circuitName);

        $driverId = $driverName === null
            ? null
            : Driver::query()->whereRaw('LOWER(display_name) = LOWER(?)', [$driverName])->value('id');
        $vehicleId = $vehicleName === null
            ? null
            : Vehicle::query()
                ->where('workspace_id', $workspace->getKey())
                ->whereRaw('LOWER(name) = LOWER(?)', [$vehicleName])
                ->value('id');

        $capture = TracksideCapture::query()->create([
            'workspace_id' => $workspace->getKey(),
            'created_by' => $user->getKey(),
            'capture_type' => 'lap_time',
            'captured_at' => now(),
            'lap_time_ms' => $lapTimeMs,
            'circuit_name' => $circuitName,
            'driver_name' => $driverName,
            'vehicle_name' => $vehicleName,
            'notes' => $validated['notes'] ?? null,
            'circuit_layout_id' => $circuitLayoutId,
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'status' => $circuitLayoutId === null ? 'pending' : 'resolved',
            'resolved_at' => $circuitLayoutId === null ? null : now(),
        ]);

        if ($capture->needsCircuitCompletion()) {
            $user->notify(new OperationalAlertNotification([
                'workspace_id' => $workspace->getKey(),
                'alert_key' => 'trackside-capture:'.$capture->getKey(),
                'category' => 'data-completion',
                'severity' => 'warning',
                'title' => app()->getLocale() === 'it' ? 'Circuito da completare' : 'Circuit needs completion',
                'message' => $circuitName.' · '.$this->formatLapTime($lapTimeMs),
                'route_name' => 'timing.index',
                'route_params' => ['pending' => 1],
            ]));
        }

        return to_route('timing.index')->with(
            'status',
            app()->getLocale() === 'it'
                ? 'Tempo salvato subito. Gli eventuali dati mancanti restano da completare.'
                : 'Lap saved immediately. Missing details can be completed later.',
        );
    }

    private function matchCircuitLayout(int $workspaceId, string $circuitName): ?int
    {
        $circuit = Circuit::query()
            ->where('workspace_id', $workspaceId)
            ->whereRaw('LOWER(name) = LOWER(?)', [$circuitName])
            ->with(['layouts' => fn ($query) => $query->where('is_active', true)])
            ->first();

        if (! $circuit instanceof Circuit || $circuit->layouts->count() !== 1) {
            return null;
        }

        return (int) $circuit->layouts->firstOrFail()->getKey();
    }

    private function parseLapTime(string $value): int
    {
        $normalized = str_replace(',', '.', trim($value));

        if ($normalized === '') {
            throw ValidationException::withMessages(['lap_time' => 'Enter a lap time.']);
        }

        $minutes = 0;
        $secondsText = $normalized;

        if (str_contains($normalized, ':')) {
            $parts = explode(':', $normalized);

            if (count($parts) !== 2 || ! ctype_digit($parts[0])) {
                throw ValidationException::withMessages(['lap_time' => 'Use a time like 1:02.345 or 62.345.']);
            }

            $minutes = (int) $parts[0];
            $secondsText = $parts[1];
        }

        if (! preg_match('/^\d{1,4}(?:\.\d{1,3})?$/', $secondsText)) {
            throw ValidationException::withMessages(['lap_time' => 'Use a time like 1:02.345 or 62.345.']);
        }

        $seconds = (float) $secondsText;

        if (str_contains($normalized, ':') && $seconds >= 60) {
            throw ValidationException::withMessages(['lap_time' => 'Seconds must be below 60 when minutes are provided.']);
        }

        $milliseconds = (int) round((($minutes * 60) + $seconds) * 1000);

        if ($milliseconds < 1000 || $milliseconds > 3_600_000) {
            throw ValidationException::withMessages(['lap_time' => 'Lap time must be between 1 second and 60 minutes.']);
        }

        return $milliseconds;
    }

    private function formatLapTime(int $milliseconds): string
    {
        $minutes = intdiv($milliseconds, 60000);
        $seconds = ($milliseconds % 60000) / 1000;

        return $minutes > 0 ? sprintf('%d:%06.3f', $minutes, $seconds) : sprintf('%.3f', $seconds);
    }
}
