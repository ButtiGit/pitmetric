<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileSyncReceipt;
use App\Models\TrackCapture;
use App\Models\User;
use App\Services\TrackCaptureService;
use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MobileSyncController extends Controller
{
    public function __invoke(
        Request $request,
        WorkspaceContext $workspaceContext,
        TrackCaptureService $captureService,
    ): JsonResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        if (! $user->hasVerifiedEmail() || ! $user->hasDatabaseAccess()) {
            return response()->json([
                'message' => 'Cloud database access is not active. Data must remain on this device for now.',
                'code' => $user->hasVerifiedEmail() ? 'database_access_required' : 'email_verification_required',
            ], 403);
        }

        if (! $workspaceContext->canWrite($user)) {
            return response()->json([
                'message' => 'Your team role cannot write PitMetric data.',
                'code' => 'workspace_write_forbidden',
            ], 403);
        }

        $validated = $request->validate([
            'operations' => ['required', 'array', 'max:100'],
            'operations.*.id' => ['required', 'uuid'],
            'operations.*.type' => ['required', Rule::in(['capture'])],
            'operations.*.payload' => ['required', 'array'],
        ]);

        $results = [];
        foreach ($validated['operations'] as $operation) {
            $results[] = $this->syncOperation($user, $operation, $captureService);
        }

        return response()->json([
            'synced' => $results,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array{id: string, type: string, payload: array<string, mixed>}  $operation
     * @return array{id: string, duplicate: bool, resource_type: string|null, resource_id: int|null}
     */
    private function syncOperation(User $user, array $operation, TrackCaptureService $captureService): array
    {
        $existing = MobileSyncReceipt::query()->where('client_uuid', $operation['id'])->first();
        if ($existing !== null) {
            return $this->receipt($existing, true);
        }

        return DB::transaction(function () use ($user, $operation, $captureService): array {
            $existing = MobileSyncReceipt::query()
                ->where('client_uuid', $operation['id'])
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $this->receipt($existing, true);
            }

            $capture = $this->createCapture($captureService, $user, $operation['payload']);
            $receipt = MobileSyncReceipt::create([
                'user_id' => $user->getKey(),
                'client_uuid' => $operation['id'],
                'operation_type' => $operation['type'],
                'resource_type' => TrackCapture::class,
                'resource_id' => $capture->getKey(),
            ]);

            return $this->receipt($receipt, false);
        });
    }

    /** @param array<string, mixed> $payload */
    private function createCapture(TrackCaptureService $captureService, User $user, array $payload): TrackCapture
    {
        $validated = Validator::make($payload, [
            'kind' => ['required', Rule::in(['lap', 'note', 'issue', 'tyre_pressure', 'component_change'])],
            'circuit_name' => ['nullable', 'string', 'max:120'],
            'lap_time_ms' => ['nullable', 'integer', 'min:1', 'max:3600000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'occurred_at' => ['nullable', 'date'],
            'payload' => ['nullable', 'array'],
            'context' => ['nullable', 'array'],
            'context.driver_name' => ['nullable', 'string', 'max:120'],
            'context.vehicle_name' => ['nullable', 'string', 'max:120'],
            'context.configuration_name' => ['nullable', 'string', 'max:120'],
            'context.technical_setup_name' => ['nullable', 'string', 'max:120'],
            'context.component_names' => ['nullable', 'string', 'max:500'],
        ])->validate();

        if ($validated['kind'] === 'lap' && ! isset($validated['lap_time_ms'])) {
            abort(422, 'lap_time_ms is required for lap captures.');
        }

        return $captureService->create($user, $validated);
    }

    /** @return array{id: string, duplicate: bool, resource_type: string|null, resource_id: int|null} */
    private function receipt(MobileSyncReceipt $receipt, bool $duplicate): array
    {
        return [
            'id' => $receipt->client_uuid,
            'duplicate' => $duplicate,
            'resource_type' => $receipt->resource_type,
            'resource_id' => $receipt->resource_id,
        ];
    }
}
