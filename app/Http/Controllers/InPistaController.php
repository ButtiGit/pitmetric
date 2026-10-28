<?php

namespace App\Http\Controllers;

use App\Models\Session;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\InPistaSessionService;
use App\Services\LapTimeParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class InPistaController extends Controller
{
    public function start(Request $request, InPistaSessionService $service): RedirectResponse
    {
        Gate::authorize('create', Session::class);
        $validated = $request->validate([
            'kart_id' => ['required', 'integer'],
            'session_type' => ['required', 'in:practice,qualifying,heat,prefinal,final,race,test'],
        ]);

        $kart = Vehicle::query()->where('category', 'kart')->where('status', 'active')
            ->findOrFail((int) $validated['kart_id']);
        $entry = $service->currentEventEntry($kart);
        $version = $service->recommendedVersion($kart, $entry);

        if ($version === null) {
            throw ValidationException::withMessages(['kart' => __('Create a kart configuration before starting a session.')]);
        }

        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $service->start($user, $kart, $version, $entry, $validated['session_type']);

        return to_route('pit-mode.index', ['kart' => $kart->getKey()])
            ->with('status', __('Trackside session started.'));
    }

    public function lap(Request $request, Session $session, InPistaSessionService $service, LapTimeParser $parser): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $session);
        $validated = $request->validate(['lap_time' => ['required', 'string', 'max:20']]);
        $lap = $service->addLap($session, $validated['lap_time'], $parser);
        $label = sprintf('%d:%02d.%03d', intdiv($lap->lap_time_ms, 60000), intdiv($lap->lap_time_ms % 60000, 1000), $lap->lap_time_ms % 1000);

        return $this->saved($request, $session, __('Lap recorded.'), 'Tempo #'.$lap->lap_number, $label);
    }

    public function adjustment(Request $request, Session $session, InPistaSessionService $service): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $session);
        $validated = $request->validate([
            'camber_front_deg' => ['nullable', 'numeric', 'between:-15,15'],
            'caster_front_deg' => ['nullable', 'numeric', 'between:-15,30'],
            'track_width_front_mm' => ['nullable', 'integer', 'between:500,3000'],
            'track_width_rear_mm' => ['nullable', 'integer', 'between:500,3000'],
            'pinion_teeth' => ['nullable', 'required_with:crown_teeth', 'integer', 'between:1,200'],
            'crown_teeth' => ['nullable', 'required_with:pinion_teeth', 'integer', 'between:1,200'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $values = [];
        foreach ($validated as $field => $value) {
            if ($field !== 'notes' && $value !== null && $value !== '') {
                $values[$field] = $value;
            }
        }
        if ($values === []) {
            throw ValidationException::withMessages(['adjustment' => __('Enter at least one adjustment.')]);
        }

        if (isset($values['pinion_teeth'], $values['crown_teeth'])) {
            $values['final_drive'] = $values['pinion_teeth'].'/'.$values['crown_teeth'];
            $values['gear_ratio'] = round((int) $values['crown_teeth'] / (int) $values['pinion_teeth'], 3);
        }

        $capture = $service->addCapture($session, $this->user($request), 'setup_adjustment', ['values' => $values], $validated['notes'] ?? null);

        return $this->saved($request, $session, __('Session setup adjustment recorded.'), 'Regolazione', implode(' · ', array_keys($values)), Carbon::parse($capture->occurred_at)->format('H:i'));
    }

    public function note(Request $request, Session $session, InPistaSessionService $service): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $session);
        $validated = $request->validate([
            'kind' => ['required', 'in:note,issue'],
            'note' => ['required', 'string', 'max:2000'],
            'severity' => ['nullable', 'in:info,warning,critical'],
        ]);
        $kind = $validated['kind'];
        $capture = $service->addCapture($session, $this->user($request), $kind, $kind === 'issue'
            ? ['severity' => $validated['severity'] ?? 'warning'] : [], $validated['note']);

        return $this->saved($request, $session, __('Session note recorded.'), $kind === 'issue' ? 'Problema' : 'Nota', $validated['note'], Carbon::parse($capture->occurred_at)->format('H:i'));
    }

    public function finish(Request $request, Session $session, InPistaSessionService $service): RedirectResponse
    {
        Gate::authorize('update', $session);
        $service->finish($session);

        return to_route('pit-mode.index', ['kart' => $session->vehicle_id])
            ->with('status', __('Trackside session completed. Usage recorded.'));
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function saved(Request $request, Session $session, string $message, string $kind, string $summary, ?string $time = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'entry' => [
                    'kind' => $kind,
                    'summary' => $summary,
                    'time' => $time ?? now()->format('H:i'),
                ],
            ]);
        }

        return to_route('pit-mode.index', ['kart' => $session->vehicle_id])
            ->with('status', $message);
    }
}
