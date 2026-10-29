<?php

namespace App\Http\Controllers;

use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\User;
use App\Services\RecordSessionService;
use App\Services\SessionHistoricalWeatherService;
use App\Services\SessionPageService;
use App\Services\SessionWeatherService;
use App\Services\WorkspaceContext;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class SessionController extends Controller
{
    public function index(
        Request $request,
        WorkspaceContext $workspaceContext,
        SessionPageService $pageService,
    ): View {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if (! $this->hasDatabaseAccess($user)) {
            return view('demo.workspace', ['initialSection' => 'sessions']);
        }

        if (! $workspaceContext->isTechnicalSetupReady()) {
            return view('garage.unavailable');
        }

        $workspace = $workspaceContext->personal($user);

        return view('sessions.index', $pageService->data($request, $workspace));
    }

    public function update(Request $request, Session $session): RedirectResponse
    {
        Gate::authorize('update', $session);
        $session->update($request->validate(['notes' => ['nullable', 'string', 'max:4000']]));

        return to_route('sessions.index')->with('status', __('Session notes updated. Recorded usage is unchanged.'));
    }

    public function storeTyreReading(Request $request, Session $session): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $session);

        abort_unless(Schema::hasTable('session_tyre_readings'), 503, __('Tyre readings are not available until the database has been updated.'));

        if ($session->vehicle?->category !== 'kart') {
            throw ValidationException::withMessages(['session' => __('Tyre readings are available for kart sessions.')]);
        }

        $validated = $request->validate([
            'phase' => ['required', 'in:cold,hot'],
            'pressure_fl' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
            'pressure_fr' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
            'pressure_rl' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
            'pressure_rr' => ['nullable', 'numeric', 'min:0.1', 'max:10'],
            'measured_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $pressures = [];

        foreach (['fl', 'fr', 'rl', 'rr'] as $corner) {
            $value = $validated['pressure_'.$corner] ?? null;

            if ($value !== null && $value !== '') {
                $pressures[$corner] = round((float) $value, 2);
            }
        }

        if ($pressures === []) {
            throw ValidationException::withMessages(['pressure_fl' => __('Enter at least one tyre pressure.')]);
        }

        $reading = $session->tyreReadings()->create([
            'phase' => $validated['phase'],
            'pressures' => $pressures,
            'measured_at' => $validated['measured_at'] ?? now(),
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()?->getAuthIdentifier(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Kart tyre pressures recorded without changing the session setup snapshot.'),
                'entry' => [
                    'kind' => $reading->phase === 'cold' ? 'Pressioni fredde' : 'Pressioni calde',
                    'summary' => collect($pressures)->map(fn ($value, $corner) => strtoupper($corner).' '.$value)->join(' · ').' bar',
                    'time' => Carbon::parse($reading->measured_at)->format('H:i'),
                ],
            ]);
        }

        return to_route('sessions.index', ['recorded' => $session->getKey()])
            ->with('status', __('Kart tyre pressures recorded without changing the session setup snapshot.'));
    }

    public function storeWeather(Request $request, Session $session, SessionWeatherService $weatherService): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $session);

        abort_unless(Schema::hasColumn('track_sessions', 'weather_snapshot'), 503, __('Session weather is not available until the database has been updated.'));

        $validated = $request->validate([
            'source' => ['nullable', 'in:circuit,gps,manual'],
            'latitude' => ['required_unless:source,circuit', 'numeric', 'between:-90,90'],
            'longitude' => ['required_unless:source,circuit', 'numeric', 'between:-180,180'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $source = $validated['source'] ?? 'manual';
        $circuit = $source === 'circuit' ? $session->weatherCircuit() : null;

        if ($source === 'circuit' && $circuit === null) {
            throw ValidationException::withMessages([
                'weather' => __('The selected circuit has no saved coordinates. Use device GPS or enter them manually.'),
            ]);
        }

        try {
            $snapshot = $weatherService->capture(
                (float) ($circuit->latitude ?? $validated['latitude']),
                (float) ($circuit->longitude ?? $validated['longitude']),
            );
        } catch (ConnectionException|RuntimeException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'weather' => __('The weather service is temporarily unavailable. Try again in a moment.'),
            ]);
        }

        $snapshot['location_source'] = $source;

        if ($circuit !== null) {
            $snapshot['circuit_id'] = $circuit->getKey();
            $snapshot['circuit_name'] = $circuit->name;
        }

        $session->setAttribute('weather_snapshot', $snapshot);
        $session->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Weather forecast saved to the session without changing its setup or recorded laps.'),
                'weather' => $snapshot,
                'entry' => [
                    'kind' => 'Meteo GPS',
                    'summary' => $snapshot['air_temperature_c'].' °C',
                    'time' => now()->format('H:i'),
                ],
            ]);
        }

        return to_route('sessions.index', [
            'weather_session' => $session->getKey(),
            'recorded' => $session->getKey(),
            'page' => $validated['page'] ?? 1,
        ])
            ->with('status', __('Weather forecast saved to the session without changing its setup or recorded laps.'));
    }

    public function storeWeatherObservation(Request $request, Session $session): RedirectResponse
    {
        Gate::authorize('update', $session);

        abort_unless(Schema::hasTable('session_weather_observations'), 503, __('Weather observations are not available until the database has been updated.'));

        $validated = $request->validate([
            'air_temperature_c' => ['nullable', 'numeric', 'between:-70,70'],
            'track_temperature_c' => ['nullable', 'numeric', 'between:-50,100'],
            'humidity_pct' => ['nullable', 'numeric', 'between:0,100'],
            'wind_speed_kmh' => ['nullable', 'numeric', 'between:0,350'],
            'pressure_hpa' => ['nullable', 'numeric', 'between:500,1200'],
            'cloud_cover_pct' => ['nullable', 'numeric', 'between:0,100'],
            'precipitation_next_hour_mm' => ['nullable', 'numeric', 'between:0,500'],
            'condition' => ['nullable', 'in:clear,partly_cloudy,cloudy,rain,storm,snow,other'],
            'measured_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reset' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $isReset = (bool) ($validated['reset'] ?? false);
        $values = [];

        if (! $isReset) {
            foreach (['air_temperature_c', 'track_temperature_c', 'humidity_pct', 'wind_speed_kmh', 'pressure_hpa', 'cloud_cover_pct', 'precipitation_next_hour_mm'] as $field) {
                if (isset($validated[$field])) {
                    $values[$field] = round((float) $validated[$field], 1);
                }
            }

            if (isset($validated['condition'])) {
                $values['condition'] = $validated['condition'];
            }

            if ($values === [] && blank($validated['notes'] ?? null)) {
                throw ValidationException::withMessages(['observations' => __('Enter at least one measurement or a note.')]);
            }
        }

        $session->weatherObservations()->create([
            'values' => $values,
            'is_reset' => $isReset,
            'measured_at' => isset($validated['measured_at'])
                ? Carbon::parse($validated['measured_at'], 'Europe/Rome')->utc()
                : now(),
            'notes' => $isReset ? null : ($validated['notes'] ?? null),
            'created_by' => $request->user()?->getAuthIdentifier(),
        ]);

        return to_route('sessions.index', [
            'weather_session' => $session->getKey(),
            'page' => $validated['page'] ?? 1,
        ])->with('status', $isReset
            ? __('Manual weather readings cleared. The history and MET Norway forecast were preserved.')
            : __('Operator weather observations saved without changing the MET Norway forecast.'));
    }

    public function storeHistoricalWeather(Request $request, Session $session, SessionHistoricalWeatherService $historyService): RedirectResponse
    {
        Gate::authorize('update', $session);

        abort_unless(Schema::hasColumn('track_sessions', 'historical_weather_snapshot'), 503, __('Historical weather is not available until the database has been updated.'));

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $startedAt = Carbon::parse((string) $session->started_at);

        if ($startedAt->greaterThan(now()->subDays(3))
            || $startedAt->lessThan(Carbon::parse('2001-01-01 00:00:00', 'UTC'))) {
            throw ValidationException::withMessages([
                'history' => __('Historical model data is available for sessions from 2001, at least three days after the session.'),
            ]);
        }

        $circuit = $session->weatherCircuit();
        $weatherSnapshot = $session->getAttribute('weather_snapshot');
        $forecast = is_array($weatherSnapshot) ? $weatherSnapshot : [];

        if ($circuit !== null) {
            $latitude = $circuit->latitude;
            $longitude = $circuit->longitude;
            $locationSource = 'circuit';
        } else {
            $latitude = $forecast['latitude'] ?? null;
            $longitude = $forecast['longitude'] ?? null;
            $locationSource = 'saved_forecast';
        }

        if (is_numeric($latitude) === false || is_numeric($longitude) === false
            || abs((float) $latitude) > 90 || abs((float) $longitude) > 180) {
            throw ValidationException::withMessages([
                'history' => __('Save circuit coordinates or a session forecast with a valid location before requesting historical weather.'),
            ]);
        }

        try {
            $snapshot = $historyService->capture((float) $latitude, (float) $longitude, $startedAt);
        } catch (ConnectionException|RuntimeException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'history' => __('Historical model data is not available for that session yet. Try again later; existing readings are unchanged.'),
            ]);
        }

        $snapshot['location_source'] = $locationSource;
        $session->setAttribute('historical_weather_snapshot', $snapshot);
        $session->save();

        return to_route('sessions.index', [
            'weather_session' => $session->getKey(),
            'page' => $validated['page'] ?? 1,
        ])->withFragment('session-weather')
            ->with('status', __('Historical hourly weather retrieved and saved separately from forecasts and operator observations.'));
    }

    public function store(
        Request $request,
        WorkspaceContext $workspaceContext,
        RecordSessionService $recordSessionService,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);
        $validated = $request->validate([
            'event_id' => ['nullable', 'integer', 'required_with:event_entry_id,schedule_item_id'],
            'event_entry_id' => ['nullable', 'integer', 'required_with:event_id'],
            'schedule_item_id' => ['nullable', 'integer'],
            'configuration_version_id' => ['required', 'integer'],
            'technical_setup_id' => ['nullable', 'integer'],
            'circuit_layout_id' => ['nullable', 'integer'],
            'session_type' => ['required', 'in:practice,qualifying,heat,prefinal,final,race,test'],
            'started_at' => ['required', 'date'],
            'completed_laps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'duration_minutes' => ['nullable', 'numeric', 'min:0', 'max:1440'],
            'distance_override_km' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'session_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'cost_description' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $result = $recordSessionService->record($workspace, $user, $validated);
        $session = $result['session'];
        $raceEvent = $result['raceEvent'];
        $maintenanceAttention = $result['maintenanceAttention'];

        if ($raceEvent instanceof RaceEvent) {
            return to_route('events.show', ['raceEvent' => $raceEvent, 'recorded' => $session->getKey()])
                ->with('status', __('Event session recorded, usage updated and immutable setup snapshot captured.'))
                ->with('maintenance_attention', $maintenanceAttention);
        }

        return to_route('sessions.index', ['recorded' => $session->getKey()])
            ->with('status', __('Session recorded, usage updated and immutable setup snapshot captured.'))
            ->with('maintenance_attention', $maintenanceAttention);
    }

    private function hasDatabaseAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
