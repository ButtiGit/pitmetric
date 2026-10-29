<?php

namespace App\Http\Controllers;

use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\User;
use App\Services\RecordSessionService;
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
