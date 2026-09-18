<?php

namespace App\Http\Controllers;

use App\Models\CircuitLayout;
use App\Models\Driver;
use App\Models\Session;
use App\Models\TelemetryImport;
use App\Models\TelemetrySample;
use App\Models\TimingLap;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\TelemetryImportService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class TelemetryController extends Controller
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
            'driver_id' => ['nullable', 'integer'],
            'vehicle_category' => ['nullable', 'string', 'max:40'],
            'circuit_layout_id' => ['nullable', 'integer'],
            'compare' => ['nullable', 'array', 'max:4'],
            'compare.*' => ['integer'],
        ]);

        $importsQuery = TelemetryImport::query()
            ->with(['session', 'driver', 'vehicle', 'circuitLayout.circuit', 'laps'])
            ->latest('imported_at');

        if (! empty($filters['session_id'])) {
            $importsQuery->where('session_id', (int) $filters['session_id']);
        }

        if (! empty($filters['driver_id'])) {
            $importsQuery->where('driver_id', (int) $filters['driver_id']);
        }

        if (! empty($filters['vehicle_category'])) {
            $importsQuery->whereHas('vehicle', fn ($query) => $query->where('category', $filters['vehicle_category']));
        }

        if (! empty($filters['circuit_layout_id'])) {
            $importsQuery->where('circuit_layout_id', (int) $filters['circuit_layout_id']);
        }

        $imports = $importsQuery->limit(100)->get();
        $compareValues = $filters['compare'] ?? [];
        $compareIds = [];

        if (is_array($compareValues)) {
            foreach ($compareValues as $id) {
                $compareIds[] = (int) $id;
            }
        }

        $requestedIds = collect($compareIds)->unique()->take(4)->values();

        if ($requestedIds->isEmpty() && $imports->isNotEmpty()) {
            $requestedIds = collect([(int) $imports->first()->getKey()]);
        }

        $selectedImports = TelemetryImport::query()
            ->with(['driver', 'vehicle', 'circuitLayout.circuit', 'session', 'laps'])
            ->whereIn('id', $requestedIds)
            ->get()
            ->sortBy(function (TelemetryImport $item) use ($requestedIds): int {
                $position = $requestedIds->search((int) $item->getKey(), true);

                return $position === false ? PHP_INT_MAX : (int) $position;
            })
            ->values();

        return view('telemetry.index', [
            'filters' => $filters,
            'imports' => $imports,
            'selectedImports' => $selectedImports,
            'series' => $selectedImports->map(fn (TelemetryImport $telemetryImport): array => $this->seriesFor($telemetryImport))->values(),
            'availableChannels' => $selectedImports
                ->flatMap(fn (TelemetryImport $telemetryImport): array => $this->channelKeysFor($telemetryImport))
                ->prepend('speed_kmh')
                ->unique()
                ->values(),
            'sessions' => Session::query()
                ->with(['vehicle', 'circuitLayout.circuit', 'eventEntry.driver'])
                ->latest('started_at')
                ->latest('id')
                ->limit(150)
                ->get(),
            'drivers' => Driver::query()->where('status', 'active')->orderBy('display_name')->get(),
            'vehicles' => Vehicle::query()->where('status', 'active')->orderBy('name')->get(),
            'layouts' => CircuitLayout::query()
                ->whereHas('circuit', fn ($query) => $query->where('workspace_id', $workspace->getKey()))
                ->with('circuit')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'vehicleCategories' => Vehicle::query()
                ->where('workspace_id', $workspace->getKey())
                ->where('status', 'active')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
        ]);
    }

    public function store(
        Request $request,
        WorkspaceContext $workspaceContext,
        TelemetryImportService $importService,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);
        $validated = $request->validate([
            'session_id' => ['required', 'integer'],
            'driver_id' => ['nullable', 'integer'],
            'source_vendor' => ['required', 'in:generic,aim,racebox,racechrono,vbox'],
            'telemetry_file' => [
                'required',
                'file',
                'max:51200',
                'extensions:csv,txt,vbo',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel,application/octet-stream',
            ],
        ]);

        $session = Session::query()
            ->whereKey((int) $validated['session_id'])
            ->where('workspace_id', $workspace->getKey())
            ->with(['eventEntry.driver', 'vehicle', 'circuitLayout'])
            ->firstOrFail();

        $driver = $session->eventEntry?->driver;

        if (! empty($validated['driver_id'])) {
            $driver = Driver::query()
                ->whereKey((int) $validated['driver_id'])
                ->where('workspace_id', $workspace->getKey())
                ->firstOrFail();
        }

        try {
            $telemetryImport = $importService->import(
                $request->file('telemetry_file'),
                $session,
                $driver,
                $user,
                $validated['source_vendor'],
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'telemetry_file' => $exception->getMessage(),
            ]);
        }

        return to_route('telemetry.index', ['compare' => [$telemetryImport->getKey()]])
            ->with('status', __('Telemetry imported and normalized.'));
    }

    /**
     * @return array{id:int,label:string,channels:list<string>,laps:list<array<string,mixed>>,points:list<array<string,mixed>>}
     */
    private function seriesFor(TelemetryImport $telemetryImport): array
    {
        $sampleCount = max(1, (int) $telemetryImport->sample_count);
        $stride = max(1, (int) ceil($sampleCount / 1800));

        $points = TelemetrySample::query()
            ->where('telemetry_import_id', $telemetryImport->getKey())
            ->whereRaw('sequence % ? = 0', [$stride])
            ->orderBy('sequence')
            ->limit(2000)
            ->get(['sequence', 'lap_number', 'elapsed_ms', 'distance_meters', 'latitude', 'longitude', 'speed_kmh', 'channels'])
            ->map(function (TelemetrySample $sample): array {
                $channels = $sample->channels;

                return [
                    'sequence' => (int) $sample->sequence,
                    'lap' => $sample->lap_number === null ? null : (int) $sample->lap_number,
                    'time' => (int) $sample->elapsed_ms,
                    'distance' => $sample->distance_meters === null ? null : (float) $sample->distance_meters,
                    'lat' => $sample->latitude === null ? null : (float) $sample->latitude,
                    'lon' => $sample->longitude === null ? null : (float) $sample->longitude,
                    'speed_kmh' => $sample->speed_kmh === null ? null : (float) $sample->speed_kmh,
                    'channels' => is_array($channels) ? $channels : [],
                ];
            })
            ->values()
            ->all();

        $driverRelation = $telemetryImport->getRelation('driver');
        $vehicleRelation = $telemetryImport->getRelation('vehicle');
        $driver = $driverRelation instanceof Driver ? $driverRelation->display_name : __('Unknown driver');
        $vehicle = $vehicleRelation instanceof Vehicle ? $vehicleRelation->name : __('Unknown vehicle');

        $laps = $telemetryImport->laps
            ->sortBy('lap_number')
            ->map(fn (TimingLap $lap): array => [
                'number' => (int) $lap->lap_number,
                'time_ms' => (int) $lap->lap_time_ms,
                'sectors_ms' => is_array($lap->sector_times_ms) ? $lap->sector_times_ms : [],
            ])
            ->values()
            ->all();

        return [
            'id' => (int) $telemetryImport->getKey(),
            'label' => $driver.' · '.$vehicle.' · '.$telemetryImport->original_filename,
            'channels' => $this->channelKeysFor($telemetryImport),
            'laps' => $laps,
            'points' => $points,
        ];
    }

    /** @return list<string> */
    private function channelKeysFor(TelemetryImport $telemetryImport): array
    {
        $channelKeys = $telemetryImport->channel_keys;

        if (! is_array($channelKeys)) {
            return [];
        }

        $normalized = [];

        foreach ($channelKeys as $channelKey) {
            $channelKey = trim((string) $channelKey);

            if ($channelKey !== '') {
                $normalized[] = $channelKey;
            }
        }

        return $normalized;
    }
}
