<?php

namespace App\Http\Controllers;

use App\Models\TrackCapture;
use App\Models\User;
use App\Services\TrackCaptureService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PitModeController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $this->user($request);
        $workspace = $workspaceContext->personal($user);
        $context = $this->context($request, (int) $workspace->getKey());
        $captures = TrackCapture::query()
            ->with('references')
            ->latest('occurred_at')
            ->latest('id')
            ->limit(20)
            ->get();

        return view('pit-mode.index', compact('context', 'captures'));
    }

    public function updateContext(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $this->user($request);
        $workspace = $workspaceContext->personal($user);
        $validated = $request->validate([
            'circuit_name' => ['nullable', 'string', 'max:120'],
            'driver_name' => ['nullable', 'string', 'max:120'],
            'vehicle_name' => ['nullable', 'string', 'max:120'],
            'configuration_name' => ['nullable', 'string', 'max:120'],
            'technical_setup_name' => ['nullable', 'string', 'max:120'],
        ]);

        $context = [];
        foreach (array_keys($validated) as $key) {
            $value = trim((string) ($validated[$key] ?? ''));
            $context[$key] = $value === '' ? null : $value;
        }

        $request->session()->put($this->sessionKey((int) $workspace->getKey()), $context);

        return to_route('pit-mode.index')->with('status', __('Pit context saved.'));
    }

    public function clearContext(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $this->user($request);
        $workspace = $workspaceContext->personal($user);
        $request->session()->forget($this->sessionKey((int) $workspace->getKey()));

        return to_route('pit-mode.index')->with('status', __('Pit context cleared.'));
    }

    public function storePressure(
        Request $request,
        WorkspaceContext $workspaceContext,
        TrackCaptureService $captureService,
    ): RedirectResponse {
        [$user, $workspaceId] = $this->operator($request, $workspaceContext);
        $validated = $request->validate([
            'pressure_fl' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'pressure_fr' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'pressure_rl' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'pressure_rr' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'unit' => ['required', 'in:bar,psi,kpa'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $context = $this->context($request, $workspaceId);
        $capture = $captureService->create($user, [
            'kind' => 'tyre_pressure',
            'circuit_name' => $context['circuit_name'],
            'payload' => [
                'unit' => $validated['unit'],
                'fl' => (float) $validated['pressure_fl'],
                'fr' => (float) $validated['pressure_fr'],
                'rl' => (float) $validated['pressure_rl'],
                'rr' => (float) $validated['pressure_rr'],
            ],
            'notes' => $validated['notes'] ?? null,
            'context' => $context,
        ]);

        return $this->saved($capture, __('Tyre pressures saved.'));
    }

    public function storeIssue(
        Request $request,
        WorkspaceContext $workspaceContext,
        TrackCaptureService $captureService,
    ): RedirectResponse {
        [$user, $workspaceId] = $this->operator($request, $workspaceContext);
        $validated = $request->validate([
            'issue' => ['required', 'string', 'max:1000'],
            'severity' => ['required', 'in:info,warning,critical'],
        ]);
        $context = $this->context($request, $workspaceId);
        $capture = $captureService->create($user, [
            'kind' => 'issue',
            'circuit_name' => $context['circuit_name'],
            'payload' => ['severity' => $validated['severity']],
            'notes' => $validated['issue'],
            'context' => $context,
        ]);

        return $this->saved($capture, __('Issue saved.'));
    }

    public function storeComponentChange(
        Request $request,
        WorkspaceContext $workspaceContext,
        TrackCaptureService $captureService,
    ): RedirectResponse {
        [$user, $workspaceId] = $this->operator($request, $workspaceContext);
        $validated = $request->validate([
            'removed_component' => ['nullable', 'required_without:installed_component', 'string', 'max:120'],
            'installed_component' => ['nullable', 'required_without:removed_component', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $context = $this->context($request, $workspaceId);
        $components = array_values(array_filter([
            trim((string) ($validated['removed_component'] ?? '')),
            trim((string) ($validated['installed_component'] ?? '')),
        ]));
        $context['component_names'] = implode(', ', $components);
        $capture = $captureService->create($user, [
            'kind' => 'component_change',
            'circuit_name' => $context['circuit_name'],
            'payload' => [
                'removed_component' => $validated['removed_component'] ?? null,
                'installed_component' => $validated['installed_component'] ?? null,
            ],
            'notes' => $validated['notes'] ?? null,
            'context' => $context,
        ]);

        return $this->saved($capture, __('Component change saved.'));
    }

    public function storeNote(
        Request $request,
        WorkspaceContext $workspaceContext,
        TrackCaptureService $captureService,
    ): RedirectResponse {
        [$user, $workspaceId] = $this->operator($request, $workspaceContext);
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);
        $context = $this->context($request, $workspaceId);
        $capture = $captureService->create($user, [
            'kind' => 'note',
            'circuit_name' => $context['circuit_name'],
            'notes' => $validated['note'],
            'context' => $context,
        ]);

        return $this->saved($capture, __('Note saved.'));
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }

    /** @return array{0: User, 1: int} */
    private function operator(Request $request, WorkspaceContext $workspaceContext): array
    {
        $user = $this->user($request);
        $workspace = $workspaceContext->personal($user);

        return [$user, (int) $workspace->getKey()];
    }

    /** @return array<string, string|null> */
    private function context(Request $request, int $workspaceId): array
    {
        $stored = $request->session()->get($this->sessionKey($workspaceId), []);
        $stored = is_array($stored) ? $stored : [];
        $context = [];

        foreach (['circuit_name', 'driver_name', 'vehicle_name', 'configuration_name', 'technical_setup_name'] as $key) {
            $value = $stored[$key] ?? null;
            $context[$key] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        return $context;
    }

    private function sessionKey(int $workspaceId): string
    {
        return 'pitmetric.pit_context.'.$workspaceId;
    }

    private function saved(TrackCapture $capture, string $message): RedirectResponse
    {
        return to_route('pit-mode.index', ['captured' => $capture->getKey()])->with('status', $message);
    }
}
