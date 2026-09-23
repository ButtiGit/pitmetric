<?php

namespace App\Http\Controllers;

use App\Models\Circuit;
use App\Models\FollowUpTask;
use App\Models\TrackCapture;
use App\Models\User;
use App\Services\TrackCaptureContextService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuickCaptureController extends Controller
{
    public function storeLap(
        Request $request,
        WorkspaceContext $workspaceContext,
        TrackCaptureContextService $contextService,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceContext->personal($user);
        $validated = $request->validate([
            'circuit_name' => ['required', 'string', 'max:120'],
            'lap_time' => ['required', 'string', 'max:20'],
            'driver_name' => ['nullable', 'string', 'max:120'],
            'vehicle_name' => ['nullable', 'string', 'max:120'],
            'configuration_name' => ['nullable', 'string', 'max:120'],
            'technical_setup_name' => ['nullable', 'string', 'max:120'],
            'component_names' => ['nullable', 'string', 'max:600'],
            'occurred_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $circuitName = trim($validated['circuit_name']);
        $subjectKey = $this->normalize($circuitName);
        $circuit = Circuit::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($circuitName)])
            ->first();
        $layout = $circuit?->layouts()->where('is_active', true)->orderBy('id')->first();
        $needsCircuit = $circuit === null;

        $capture = TrackCapture::create([
            'kind' => 'lap_time',
            'circuit_name' => $circuitName,
            'circuit_id' => $circuit?->getKey(),
            'circuit_layout_id' => $layout?->getKey(),
            'lap_time_ms' => $this->parseLapTime($validated['lap_time']),
            'occurred_at' => $validated['occurred_at'] ?? now(),
            'status' => $needsCircuit ? 'needs_attention' : 'ready',
            'resolved_at' => $needsCircuit ? null : now(),
            'created_by' => $user->getKey(),
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($needsCircuit) {
            $task = FollowUpTask::query()
                ->where('kind', 'missing_circuit')
                ->where('subject_key', $subjectKey)
                ->where('status', 'open')
                ->first();

            if ($task === null) {
                FollowUpTask::create([
                    'kind' => 'missing_circuit',
                    'subject_key' => $subjectKey,
                    'title' => "Create circuit: {$circuitName}",
                    'description' => 'A lap time was captured before this circuit existed. Create the circuit when you have time; the pending timing records will be linked automatically.',
                    'target_route' => 'circuits.index',
                    'context' => ['circuit_name' => $circuitName, 'first_capture_id' => $capture->getKey()],
                    'status' => 'open',
                    'created_by' => $user->getKey(),
                ]);
            }
        }

        $pendingContext = $contextService->attach($capture, $user, [
            'driver_name' => $validated['driver_name'] ?? null,
            'vehicle_name' => $validated['vehicle_name'] ?? null,
            'configuration_name' => $validated['configuration_name'] ?? null,
            'technical_setup_name' => $validated['technical_setup_name'] ?? null,
            'component_names' => $validated['component_names'] ?? null,
        ]);

        if ($pendingContext > 0) {
            $capture->update([
                'status' => 'needs_attention',
                'resolved_at' => null,
            ]);
        }

        $needsAttention = $needsCircuit || $pendingContext > 0;

        return to_route('sessions.index', ['captured' => $capture->getKey()])
            ->with('status', $needsAttention
                ? __('Lap saved. Missing context can be completed later from the alert inbox.')
                : __('Lap saved.'));
    }

    private function parseLapTime(string $value): int
    {
        $value = str_replace(',', '.', trim($value));

        if (str_contains($value, ':')) {
            [$minutes, $seconds] = array_pad(explode(':', $value, 2), 2, null);

            if (! ctype_digit($minutes) || ! is_numeric($seconds) || (float) $seconds >= 60) {
                throw ValidationException::withMessages(['lap_time' => __('Use a lap time like 1:02.345.')]);
            }

            $milliseconds = ((int) $minutes * 60000) + (int) round((float) $seconds * 1000);
        } elseif (is_numeric($value)) {
            $milliseconds = (int) round((float) $value * 1000);
        } else {
            throw ValidationException::withMessages(['lap_time' => __('Use a lap time like 1:02.345 or 62.345.')]);
        }

        if ($milliseconds < 1000 || $milliseconds > 3600000) {
            throw ValidationException::withMessages(['lap_time' => __('Lap time must be between 1 second and 60 minutes.')]);
        }

        return $milliseconds;
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
    }
}
