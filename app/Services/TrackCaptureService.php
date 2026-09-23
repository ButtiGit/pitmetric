<?php

namespace App\Services;

use App\Models\Circuit;
use App\Models\FollowUpTask;
use App\Models\TrackCapture;
use App\Models\User;

class TrackCaptureService
{
    public function __construct(private TrackCaptureContextService $contextService)
    {
    }

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): TrackCapture
    {
        $circuitName = trim((string) ($data['circuit_name'] ?? ''));
        $circuit = null;
        $layout = null;

        if ($circuitName !== '') {
            $circuit = Circuit::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($circuitName)])
                ->first();
            $layout = $circuit?->layouts()->where('is_active', true)->orderBy('id')->first();
        }

        $needsCircuit = $circuitName !== '' && $circuit === null;
        $payload = is_array($data['payload'] ?? null) ? $data['payload'] : null;
        $notes = isset($data['notes']) && is_string($data['notes']) ? $data['notes'] : null;

        $capture = TrackCapture::create([
            'kind' => (string) ($data['kind'] ?? 'note'),
            'circuit_name' => $circuitName === '' ? null : $circuitName,
            'circuit_id' => $circuit?->getKey(),
            'circuit_layout_id' => $layout?->getKey(),
            'lap_time_ms' => isset($data['lap_time_ms']) ? (int) $data['lap_time_ms'] : null,
            'payload' => $payload,
            'occurred_at' => $data['occurred_at'] ?? now(),
            'status' => $needsCircuit ? 'needs_attention' : 'ready',
            'resolved_at' => $needsCircuit ? null : now(),
            'created_by' => $user->getKey(),
            'notes' => $notes,
        ]);

        if ($needsCircuit) {
            $this->ensureCircuitFollowUp($circuitName, $capture, $user);
        }

        $context = $this->context($data);
        $pendingContext = $this->contextService->attach($capture, $user, $context);

        if ($pendingContext > 0) {
            $capture->update([
                'status' => 'needs_attention',
                'resolved_at' => null,
            ]);
        }

        return $capture;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string|null>
     */
    private function context(array $data): array
    {
        $raw = is_array($data['context'] ?? null) ? $data['context'] : [];
        $context = [];

        foreach (['driver_name', 'vehicle_name', 'configuration_name', 'technical_setup_name', 'component_names'] as $key) {
            $value = $raw[$key] ?? null;
            $context[$key] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        return $context;
    }

    private function ensureCircuitFollowUp(string $circuitName, TrackCapture $capture, User $user): void
    {
        $subjectKey = $this->normalize($circuitName);
        $exists = FollowUpTask::query()
            ->where('kind', 'missing_circuit')
            ->where('subject_key', $subjectKey)
            ->where('status', 'open')
            ->exists();

        if ($exists) {
            return;
        }

        FollowUpTask::create([
            'kind' => 'missing_circuit',
            'subject_key' => $subjectKey,
            'title' => "Create circuit: {$circuitName}",
            'description' => 'Trackside data was captured before this circuit existed. Create it when convenient; pending records will be linked automatically.',
            'target_route' => 'circuits.index',
            'context' => ['circuit_name' => $circuitName, 'first_capture_id' => $capture->getKey()],
            'status' => 'open',
            'created_by' => $user->getKey(),
        ]);
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
    }
}
