<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\LapTimeParser;
use App\Services\TrackCaptureService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuickCaptureController extends Controller
{
    public function storeLap(
        Request $request,
        WorkspaceContext $workspaceContext,
        TrackCaptureService $captureService,
        LapTimeParser $lapTimeParser,
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
            'lap_time_ms' => $lapTimeParser->parse($validated['lap_time']),
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
}
