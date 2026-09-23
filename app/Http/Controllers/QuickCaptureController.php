<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TrackCaptureService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class QuickCaptureController extends Controller
{
    public function storeLap(
        Request $request,
        WorkspaceContext $workspaceContext,
        TrackCaptureService $captureService,
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
            'pit_mode' => ['nullable', 'boolean'],
        ]);

        $capture = $captureService->create($user, [
            'kind' => 'lap_time',
            'circuit_name' => $validated['circuit_name'],
            'lap_time_ms' => $this->parseLapTime($validated['lap_time']),
            'occurred_at' => $validated['occurred_at'] ?? now(),
            'notes' => $validated['notes'] ?? null,
            'context' => [
                'driver_name' => $validated['driver_name'] ?? null,
                'vehicle_name' => $validated['vehicle_name'] ?? null,
                'configuration_name' => $validated['configuration_name'] ?? null,
                'technical_setup_name' => $validated['technical_setup_name'] ?? null,
                'component_names' => $validated['component_names'] ?? null,
            ],
        ]);

        $needsAttention = $capture->status === 'needs_attention';
        $message = $needsAttention
            ? __('Lap saved. Missing context can be completed later from the alert inbox.')
            : __('Lap saved.');

        if ($request->boolean('pit_mode')) {
            return to_route('pit-mode.index', ['captured' => $capture->getKey()])->with('status', $message);
        }

        return to_route('sessions.index', ['captured' => $capture->getKey()])->with('status', $message);
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
}
