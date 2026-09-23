<?php

namespace App\Http\Controllers;

use App\Models\ConfigurationVersion;
use App\Models\EventNote;
use App\Models\GalleryAsset;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\MobileAccessToken;
use App\Models\MobileSyncOperation;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Services\RecordSessionService;
use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class MobileAppController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
        private readonly RecordSessionService $recordSessionService,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user instanceof User || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        return $this->issueToken($user, $validated['device_name'] ?? null);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        return $this->issueToken($user, $validated['device_name'] ?? null, 201);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->mobileUser($request);

        return response()->json(['session' => $this->sessionPayload($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->attributes->get('mobile_access_token');

        if ($accessToken instanceof MobileAccessToken) {
            $accessToken->forceFill(['revoked_at' => now()])->save();
        }

        return response()->json(['ok' => true]);
    }

    public function bootstrap(Request $request): JsonResponse
    {
        $user = $this->mobileUser($request);

        if (! $this->hasCloudAccess($user)) {
            return response()->json(['message' => 'Database access is not enabled. Keep using local storage until it is enabled.'], 403);
        }

        $workspace = $this->workspaceContext->personal($user);

        $events = RaceEvent::query()
            ->with([
                'entries.driver',
                'entries.vehicle',
                'scheduleItems' => fn ($query) => $query->orderBy('starts_at'),
            ])
            ->where('workspace_id', $workspace->getKey())
            ->latest('start_date')
            ->limit(12)
            ->get()
            ->map(fn (RaceEvent $event): array => [
                'id' => $event->getKey(),
                'name' => $event->name,
                'championship' => $event->championship,
                'round_label' => $event->round_label,
                'start_date' => $event->start_date?->toDateString(),
                'end_date' => $event->end_date?->toDateString(),
                'status' => $event->status,
                'notes' => $event->notes,
                'entries' => $event->entries->map(fn ($entry): array => [
                    'id' => $entry->getKey(),
                    'entry_number' => $entry->entry_number,
                    'vehicle_id' => $entry->vehicle_id,
                    'vehicle' => $entry->vehicle?->name,
                    'driver' => $entry->driver?->display_name,
                    'configuration_version_id' => $entry->configuration_version_id,
                ])->values(),
                'schedule' => $event->scheduleItems->map(fn ($item): array => [
                    'id' => $item->getKey(),
                    'event_entry_id' => $item->event_entry_id,
                    'session_id' => $item->session_id,
                    'label' => $item->label,
                    'session_type' => $item->session_type,
                    'starts_at' => $item->starts_at?->toIso8601String(),
                    'duration_minutes' => $item->duration_minutes,
                    'status' => $item->status,
                    'notes' => $item->notes,
                ])->values(),
            ])->values();

        $vehicles = Vehicle::query()
            ->where('workspace_id', $workspace->getKey())
            ->orderByDesc('status')
            ->orderBy('name')
            ->get()
            ->map(fn (Vehicle $vehicle): array => [
                'id' => $vehicle->getKey(),
                'name' => $vehicle->name,
                'category' => $vehicle->category,
                'manufacturer' => $vehicle->manufacturer,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'identifier' => $vehicle->identifier,
                'status' => $vehicle->status,
                'notes' => $vehicle->notes,
            ])->values();

        $sessions = Session::query()
            ->with('vehicle')
            ->where('workspace_id', $workspace->getKey())
            ->latest('started_at')
            ->limit(30)
            ->get()
            ->map(fn (Session $session): array => [
                'id' => $session->getKey(),
                'event_id' => $session->event_id,
                'event_entry_id' => $session->event_entry_id,
                'vehicle_id' => $session->vehicle_id,
                'vehicle' => $session->vehicle?->name,
                'configuration_version_id' => $session->configuration_version_id,
                'session_type' => $session->session_type,
                'started_at' => $session->started_at?->toIso8601String(),
                'completed_laps' => $session->completed_laps,
                'duration_seconds' => $session->duration_seconds,
                'status' => $session->status,
                'notes' => $session->notes,
            ])->values();

        $configurations = ConfigurationVersion::query()
            ->with('configuration.vehicle')
            ->whereHas('configuration', fn ($query) => $query
                ->where('workspace_id', $workspace->getKey())
                ->where('status', 'active'))
            ->latest('id')
            ->limit(60)
            ->get()
            ->map(fn (ConfigurationVersion $version): array => [
                'id' => $version->getKey(),
                'version_number' => $version->version_number,
                'configuration_id' => $version->configuration_id,
                'configuration' => $version->configuration->name,
                'vehicle_id' => $version->configuration->vehicle_id,
                'vehicle' => $version->configuration->vehicle?->name,
                'notes' => $version->notes,
            ])->values();

        $maintenanceSchedules = MaintenanceSchedule::query()
            ->where('workspace_id', $workspace->getKey())
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (MaintenanceSchedule $schedule): array => [
                'id' => $schedule->getKey(),
                'name' => $schedule->name,
                'interval_value' => $schedule->interval_value,
                'warning_value' => $schedule->warning_value,
                'is_active' => $schedule->is_active,
                'notes' => $schedule->notes,
            ])->values();

        $workOrders = MaintenanceWorkOrder::query()
            ->with('schedule')
            ->where('workspace_id', $workspace->getKey())
            ->whereIn('status', MaintenanceWorkOrder::OPEN_STATUSES)
            ->orderByRaw("case priority when 'critical' then 1 when 'high' then 2 when 'normal' then 3 else 4 end")
            ->orderBy('due_at')
            ->limit(50)
            ->get()
            ->map(fn (MaintenanceWorkOrder $order): array => [
                'id' => $order->getKey(),
                'maintenance_schedule_id' => $order->maintenance_schedule_id,
                'schedule' => $order->schedule?->name,
                'title' => $order->title,
                'priority' => $order->priority,
                'status' => $order->status,
                'due_at' => $order->due_at?->toIso8601String(),
                'notes' => $order->notes,
            ])->values();

        $setups = TechnicalSetup::query()
            ->with('vehicle')
            ->where('workspace_id', $workspace->getKey())
            ->latest('updated_at')
            ->limit(40)
            ->get()
            ->map(fn (TechnicalSetup $setup): array => [
                'id' => $setup->getKey(),
                'vehicle_id' => $setup->vehicle_id,
                'vehicle' => $setup->vehicle?->name,
                'name' => $setup->name,
                'description' => $setup->description,
                'values' => $setup->values,
                'status' => $setup->status,
            ])->values();

        return response()->json([
            'data' => [
                'workspace' => ['id' => $workspace->getKey(), 'name' => $workspace->name],
                'events' => $events,
                'vehicles' => $vehicles,
                'sessions' => $sessions,
                'configurations' => $configurations,
                'maintenance_schedules' => $maintenanceSchedules,
                'work_orders' => $workOrders,
                'setups' => $setups,
                'setup_fields' => TechnicalSetup::FIELD_DEFINITIONS,
            ],
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    public function syncOperation(Request $request): JsonResponse
    {
        $user = $this->mobileUser($request);

        if (! $this->hasCloudAccess($user)) {
            return response()->json(['message' => 'Database access is not enabled. Data remains on this device.'], 403);
        }

        $workspace = $this->workspaceContext->personal($user);

        if (! Gate::forUser($user)->allows('team-write')) {
            return response()->json(['message' => 'This team membership is read only.'], 403);
        }

        $validated = $request->validate([
            'client_id' => ['required', 'uuid'],
            'operation' => ['required', 'in:event.note.create,session.create,vehicle.create,maintenance.work_order.create,setup.create'],
            'payload' => ['required', 'array'],
        ]);

        $existing = MobileSyncOperation::query()
            ->where('user_id', $user->getKey())
            ->where('client_id', $validated['client_id'])
            ->first();

        if ($existing instanceof MobileSyncOperation) {
            return response()->json([
                'duplicate' => true,
                'result' => $existing->result,
            ]);
        }

        $result = DB::transaction(function () use ($validated, $workspace, $user): array {
            /** @var array<string, mixed> $payload */
            $payload = $validated['payload'];
            $result = match ($validated['operation']) {
                'event.note.create' => $this->syncEventNote($payload, $user),
                'session.create' => $this->syncSession($payload, $workspace, $user),
                'vehicle.create' => $this->syncVehicle($payload),
                'maintenance.work_order.create' => $this->syncWorkOrder($payload, $user),
                'setup.create' => $this->syncSetup($payload, $user),
            };

            MobileSyncOperation::query()->create([
                'user_id' => $user->getKey(),
                'workspace_id' => $workspace->getKey(),
                'client_id' => $validated['client_id'],
                'operation' => $validated['operation'],
                'payload' => $payload,
                'result' => $result,
                'processed_at' => now(),
            ]);

            return $result;
        });

        return response()->json(['duplicate' => false, 'result' => $result], 201);
    }

    public function galleryIndex(Request $request): JsonResponse
    {
        $user = $this->mobileUser($request);

        if (! $this->hasCloudAccess($user)) {
            return response()->json(['message' => 'Database access is not enabled. Keep using local storage until it is enabled.'], 403);
        }

        $items = GalleryAsset::query()
            ->where('user_id', $user->getKey())
            ->latest('captured_at')
            ->latest('id')
            ->limit(200)
            ->get()
            ->map(fn (GalleryAsset $asset): array => $this->galleryPayload($asset));

        return response()->json(['data' => $items]);
    }

    public function galleryStore(Request $request): JsonResponse
    {
        $user = $this->mobileUser($request);

        if (! $this->hasCloudAccess($user)) {
            return response()->json(['message' => 'Database access is not enabled. Data remains on this device.'], 403);
        }

        $validated = $request->validate([
            'client_id' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'captured_at' => ['nullable', 'date'],
            'media_type' => ['nullable', 'in:image,video'],
            'media' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/heic,image/heif,video/mp4,video/quicktime,video/webm', 'max:102400'],
            'photo' => ['nullable', 'image', 'max:15360'],
        ]);

        $workspace = $this->workspaceContext->personal($user);
        $asset = GalleryAsset::query()->firstOrNew([
            'user_id' => $user->getKey(),
            'client_id' => $validated['client_id'],
        ]);

        $upload = $request->file('media');
        if (! $upload instanceof UploadedFile) {
            $upload = $request->file('photo');
        }

        $mediaType = $validated['media_type'] ?? ($upload instanceof UploadedFile && str_starts_with((string) $upload->getMimeType(), 'video/') ? 'video' : 'image');

        $asset->fill([
            'workspace_id' => $workspace->getKey(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'media_type' => $mediaType,
            'captured_at' => $validated['captured_at'] ?? now(),
        ]);

        if ($upload instanceof UploadedFile) {
            $storedPath = $upload->store('gallery/'.$user->getKey().'/'.$mediaType, 'public');

            if ($storedPath === false) {
                throw new RuntimeException('Unable to store gallery media.');
            }

            if ($asset->path !== null) {
                Storage::disk('public')->delete($asset->path);
            }

            $asset->path = $storedPath;
            $asset->mime_type = $upload->getMimeType();
            $asset->original_name = $upload->getClientOriginalName();
            $asset->size_bytes = $upload->getSize();
        }

        $asset->save();

        return response()->json($this->galleryPayload($asset), $asset->wasRecentlyCreated ? 201 : 200);
    }

    /** @param array<string, mixed> $payload */
    private function syncEventNote(array $payload, User $user): array
    {
        $validated = Validator::make($payload, [
            'event_id' => ['required', 'integer'],
            'event_entry_id' => ['nullable', 'integer'],
            'kind' => ['nullable', 'in:technical,driver,weather,strategy,incident,general'],
            'body' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['nullable', 'date'],
        ])->validate();

        RaceEvent::query()->whereKey($validated['event_id'])->firstOrFail();

        $note = EventNote::query()->create([
            'event_id' => $validated['event_id'],
            'event_entry_id' => $validated['event_entry_id'] ?? null,
            'kind' => $validated['kind'] ?? 'technical',
            'body' => $validated['body'],
            'occurred_at' => $validated['occurred_at'] ?? now(),
            'created_by' => $user->getKey(),
        ]);

        return ['entity' => 'event_note', 'id' => $note->getKey()];
    }

    /** @param array<string, mixed> $payload */
    private function syncSession(array $payload, Workspace $workspace, User $user): array
    {
        $validated = Validator::make($payload, [
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
            'notes' => ['nullable', 'string', 'max:4000'],
        ])->validate();

        $recorded = $this->recordSessionService->record($workspace, $user, $validated);

        return [
            'entity' => 'session',
            'id' => $recorded['session']->getKey(),
            'maintenance_attention' => $recorded['maintenanceAttention'],
        ];
    }

    /** @param array<string, mixed> $payload */
    private function syncVehicle(array $payload): array
    {
        $validated = Validator::make($payload, [
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', 'in:'.implode(',', Vehicle::CATEGORIES)],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2200'],
            'identifier' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ])->validate();

        $vehicle = Vehicle::query()->create([...$validated, 'status' => 'active']);

        return ['entity' => 'vehicle', 'id' => $vehicle->getKey()];
    }

    /** @param array<string, mixed> $payload */
    private function syncWorkOrder(array $payload, User $user): array
    {
        $validated = Validator::make($payload, [
            'maintenance_schedule_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'priority' => ['nullable', 'in:'.implode(',', MaintenanceWorkOrder::PRIORITIES)],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ])->validate();

        if (! empty($validated['maintenance_schedule_id'])) {
            MaintenanceSchedule::query()->whereKey($validated['maintenance_schedule_id'])->firstOrFail();
        }

        $order = MaintenanceWorkOrder::query()->create([
            'maintenance_schedule_id' => $validated['maintenance_schedule_id'] ?? null,
            'assigned_to' => $user->getKey(),
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? 'normal',
            'status' => 'todo',
            'due_at' => $validated['due_at'] ?? null,
            'created_by' => $user->getKey(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return ['entity' => 'maintenance_work_order', 'id' => $order->getKey()];
    }

    /** @param array<string, mixed> $payload */
    private function syncSetup(array $payload, User $user): array
    {
        $validated = Validator::make($payload, [
            'vehicle_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'values' => ['nullable', 'array'],
        ])->validate();

        Vehicle::query()->whereKey($validated['vehicle_id'])->firstOrFail();

        $setup = TechnicalSetup::query()->create([
            'vehicle_id' => $validated['vehicle_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'values' => $validated['values'] ?? [],
            'status' => 'active',
            'created_by' => $user->getKey(),
        ]);

        return ['entity' => 'technical_setup', 'id' => $setup->getKey()];
    }

    private function issueToken(User $user, ?string $deviceName, int $status = 200): JsonResponse
    {
        $plainToken = Str::random(80);

        MobileAccessToken::query()->create([
            'user_id' => $user->getKey(),
            'device_name' => $deviceName,
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return response()->json([
            'token' => $plainToken,
            'session' => $this->sessionPayload($user),
        ], $status);
    }

    /** @return array<string, mixed> */
    private function sessionPayload(User $user): array
    {
        $workspace = $user->workspaces()
            ->wherePivot('status', 'active')
            ->orderBy('workspaces.id')
            ->first();

        return [
            'authenticated' => true,
            'cloudEnabled' => $this->hasCloudAccess($user),
            'canWrite' => $workspace !== null && Gate::forUser($user)->allows('team-write'),
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
            ],
            'workspace' => $workspace === null ? null : [
                'id' => $workspace->getKey(),
                'name' => $workspace->name,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function galleryPayload(GalleryAsset $asset): array
    {
        return [
            'id' => $asset->getKey(),
            'client_id' => $asset->client_id,
            'title' => $asset->title,
            'description' => $asset->description,
            'media_type' => $asset->media_type,
            'mime_type' => $asset->mime_type,
            'original_name' => $asset->original_name,
            'size_bytes' => $asset->size_bytes,
            'captured_at' => $asset->captured_at?->toIso8601String(),
            'url' => $asset->path === null ? null : Storage::disk('public')->url($asset->path),
        ];
    }

    private function mobileUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function hasCloudAccess(User $user): bool
    {
        return Gate::forUser($user)->allows('manage-updates') || $user->hasDatabaseAccess();
    }
}
