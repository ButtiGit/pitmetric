<?php

namespace App\Http\Controllers;

use App\Models\ConfigurationVersion;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventNote;
use App\Models\EventScheduleItem;
use App\Models\GalleryAsset;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\MobileAccessToken;
use App\Models\MobileSyncOperation;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\TimingLap;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Services\RecordSessionService;
use App\Services\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
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
            ->map(fn (RaceEvent $event): array => $this->eventPayload($event))
            ->values();

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
            ->map(fn (Session $session): array => $this->sessionItemPayload($session))
            ->values();

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
            ->map(fn (MaintenanceWorkOrder $order): array => $this->workOrderPayload($order))
            ->values();

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
                'drivers' => Driver::query()->where('status', 'active')->orderBy('display_name')->get(['id', 'display_name']),
                'timing_laps' => TimingLap::query()->whereIn('session_id', $sessions->pluck('id'))
                    ->where('is_valid', true)->latest('id')->limit(500)
                    ->get(['id', 'session_id', 'lap_number', 'lap_time_ms']),
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
            'operation' => ['required', 'in:event.note.create,session.create,timing.lap.create,session.tyres.create,vehicle.create,maintenance.work_order.create,setup.create'],
            'payload' => ['required', 'array'],
        ]);

        $outcome = DB::transaction(function () use ($validated, $workspace, $user): array {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $existing = MobileSyncOperation::query()->where('user_id', $user->getKey())
                ->where('client_id', $validated['client_id'])->first();

            if ($existing instanceof MobileSyncOperation) {
                abort_unless((int) $existing->workspace_id === (int) $workspace->getKey()
                    && $existing->operation === $validated['operation'], 409);

                return ['duplicate' => true, 'result' => $existing->result];
            }

            /** @var array<string, mixed> $payload */
            $payload = $validated['payload'];
            $operation = (string) $validated['operation'];
            $result = match ($operation) {
                'event.note.create' => $this->syncEventNote($payload, $user),
                'session.create' => $this->syncSession($payload, $workspace, $user),
                'timing.lap.create' => $this->syncLap($payload, $workspace, $user),
                'session.tyres.create' => $this->syncTyres($payload, $workspace, $user),
                'vehicle.create' => $this->syncVehicle($payload),
                'maintenance.work_order.create' => $this->syncWorkOrder($payload, $user),
                'setup.create' => $this->syncSetup($payload, $user),
                default => throw new RuntimeException('Unsupported mobile sync operation.'),
            };

            MobileSyncOperation::query()->create([
                'user_id' => $user->getKey(),
                'workspace_id' => $workspace->getKey(),
                'client_id' => $validated['client_id'],
                'operation' => $operation,
                'payload' => $payload,
                'result' => $result,
                'processed_at' => now(),
            ]);

            return ['duplicate' => false, 'result' => $result];
        });

        return response()->json($outcome, $outcome['duplicate'] ? 200 : 201);
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

    /** @return array<string, mixed> */
    private function eventPayload(RaceEvent $event): array
    {
        return [
            'id' => $event->getKey(),
            'name' => $event->name,
            'championship' => $event->championship,
            'round_label' => $event->round_label,
            'start_date' => $this->dateValue($event->start_date, true),
            'end_date' => $this->dateValue($event->end_date, true),
            'status' => $event->status,
            'notes' => $event->notes,
            'entries' => $event->entries
                ->map(fn (EventEntry $entry): array => $this->eventEntryPayload($entry))
                ->values()
                ->all(),
            'schedule' => $event->scheduleItems
                ->map(fn (EventScheduleItem $item): array => $this->scheduleItemPayload($item))
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function eventEntryPayload(EventEntry $entry): array
    {
        return [
            'id' => $entry->getKey(),
            'entry_number' => $entry->entry_number,
            'vehicle_id' => $entry->vehicle_id,
            'vehicle' => $entry->vehicle?->name,
            'driver' => $entry->driver?->display_name,
            'configuration_version_id' => $entry->configuration_version_id,
        ];
    }

    /** @return array<string, mixed> */
    private function scheduleItemPayload(EventScheduleItem $item): array
    {
        return [
            'id' => $item->getKey(),
            'event_entry_id' => $item->event_entry_id,
            'session_id' => $item->session_id,
            'label' => $item->label,
            'session_type' => $item->session_type,
            'starts_at' => $this->dateValue($item->starts_at),
            'duration_minutes' => $item->duration_minutes,
            'status' => $item->status,
            'notes' => $item->notes,
        ];
    }

    /** @return array<string, mixed> */
    private function sessionItemPayload(Session $session): array
    {
        return [
            'id' => $session->getKey(),
            'event_id' => $session->event_id,
            'event_entry_id' => $session->event_entry_id,
            'vehicle_id' => $session->vehicle_id,
            'vehicle' => $session->vehicle?->name,
            'configuration_version_id' => $session->configuration_version_id,
            'session_type' => $session->session_type,
            'started_at' => $this->dateValue($session->started_at),
            'completed_laps' => $session->completed_laps,
            'duration_seconds' => $session->duration_seconds,
            'status' => $session->status,
            'notes' => $session->notes,
        ];
    }

    /** @return array<string, mixed> */
    private function workOrderPayload(MaintenanceWorkOrder $order): array
    {
        return [
            'id' => $order->getKey(),
            'maintenance_schedule_id' => $order->maintenance_schedule_id,
            'schedule' => $order->schedule?->name,
            'title' => $order->title,
            'priority' => $order->priority,
            'status' => $order->status,
            'due_at' => $this->dateValue($order->due_at),
            'notes' => $order->notes,
        ];
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
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
        if (isset($validated['event_entry_id'])) {
            EventEntry::query()->where('event_id', $validated['event_id'])->findOrFail($validated['event_entry_id']);
        }

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

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
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
            'best_lap_ms' => ['nullable', 'integer', 'between:1,3600000'],
        ])->validate();

        $recorded = $this->recordSessionService->record($workspace, $user, $validated);

        if (isset($validated['best_lap_ms'])) {
            $this->syncLap(['session_id' => $recorded['session']->getKey(), 'lap_time_ms' => $validated['best_lap_ms']], $workspace, $user);
        }

        return [
            'entity' => 'session',
            'id' => $recorded['session']->getKey(),
            'maintenance_attention' => $recorded['maintenanceAttention'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function syncLap(array $payload, Workspace $workspace, User $user): array
    {
        $validated = Validator::make($payload, [
            'session_id' => ['nullable', 'required_without:session_client_id', 'prohibits:session_client_id', 'integer'],
            'session_client_id' => ['nullable', 'required_without:session_id', 'uuid'],
            'lap_time_ms' => ['required', 'integer', 'between:1,3600000'],
        ])->validate();

        $session = $this->syncTargetSession($validated, $workspace, $user);
        $lap = TimingLap::query()->create([
            'session_id' => $session->getKey(),
            'driver_id' => $session->eventEntry?->driver_id,
            'vehicle_id' => $session->vehicle_id,
            'circuit_layout_id' => $session->circuit_layout_id,
            'lap_number' => (int) TimingLap::query()->where('session_id', $session->getKey())->max('lap_number') + 1,
            'lap_time_ms' => $validated['lap_time_ms'],
            'is_valid' => true,
            'source' => 'manual',
        ]);

        return ['entity' => 'timing_lap', 'id' => $lap->getKey()];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function syncTyres(array $payload, Workspace $workspace, User $user): array
    {
        $validated = Validator::make($payload, [
            'session_id' => ['nullable', 'required_without:session_client_id', 'prohibits:session_client_id', 'integer'],
            'session_client_id' => ['nullable', 'required_without:session_id', 'uuid'],
            'phase' => ['required', 'in:cold,hot'],
            'pressure_fl' => ['nullable', 'numeric', 'between:0.1,10'],
            'pressure_fr' => ['nullable', 'numeric', 'between:0.1,10'],
            'pressure_rl' => ['nullable', 'numeric', 'between:0.1,10'],
            'pressure_rr' => ['nullable', 'numeric', 'between:0.1,10'],
            'measured_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->validate();
        $session = $this->syncTargetSession($validated, $workspace, $user);
        abort_unless($session->vehicle?->category === 'kart', 422);
        $pressures = [];
        foreach (['fl', 'fr', 'rl', 'rr'] as $corner) {
            if (isset($validated['pressure_'.$corner])) {
                $pressures[$corner] = round((float) $validated['pressure_'.$corner], 2);
            }
        }
        if ($pressures === []) {
            throw ValidationException::withMessages(['pressures' => 'Inserisci almeno una pressione.']);
        }
        $reading = $session->tyreReadings()->create([
            'phase' => $validated['phase'], 'pressures' => $pressures,
            'measured_at' => $validated['measured_at'], 'notes' => $validated['notes'] ?? null,
            'created_by' => $user->getKey(),
        ]);

        return ['entity' => 'session_tyre_reading', 'id' => $reading->getKey()];
    }

    /** @param array<string, mixed> $payload */
    private function syncTargetSession(array $payload, Workspace $workspace, User $user): Session
    {
        $sessionId = $payload['session_id'] ?? null;
        if (isset($payload['session_client_id'])) {
            $operation = MobileSyncOperation::query()->where('user_id', $user->getKey())
                ->where('workspace_id', $workspace->getKey())->where('operation', 'session.create')
                ->where('client_id', $payload['session_client_id'])->first();
            $sessionId = $operation?->result['id'] ?? null;
            if ($sessionId === null) {
                throw ValidationException::withMessages(['session_client_id' => 'Invia prima la sessione a cui appartiene il giro.']);
            }
        }

        $session = Session::query()->with('eventEntry')->where('workspace_id', $workspace->getKey())
            ->lockForUpdate()->findOrFail((int) $sessionId);
        Gate::forUser($user)->authorize('update', $session);

        return $session;
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
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

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
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

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function syncSetup(array $payload, User $user): array
    {
        $validated = Validator::make($payload, [
            'vehicle_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'values' => ['nullable', 'array'],
        ])->validate();

        Vehicle::query()->whereKey($validated['vehicle_id'])->firstOrFail();

        $valueRules = TechnicalSetup::kartGeometryRules();
        foreach (array_keys(TechnicalSetup::FIELD_DEFINITIONS) as $field) {
            if (! isset($valueRules[$field])) {
                $valueRules[$field] = ['nullable', 'numeric', 'between:0,500'];
            }
        }
        foreach (['fl', 'fr', 'rl', 'rr'] as $corner) {
            $valueRules['tyre_pressure_'.$corner] = ['nullable', 'numeric', 'between:0,10'];
        }
        $valueRules['camber_rear_deg'] = ['nullable', 'numeric', 'between:-15,15'];
        $valueRules['toe_front_mm'] = $valueRules['toe_rear_mm'] = ['nullable', 'numeric', 'between:-20,20'];
        $valueRules['pinion_teeth'] = ['nullable', 'required_with:crown_teeth', 'integer', 'between:1,200'];
        $valueRules['crown_teeth'] = ['nullable', 'required_with:pinion_teeth', 'integer', 'between:1,200'];
        $valueRules['final_drive'] = ['nullable', 'string', 'max:30'];
        $values = Validator::make($validated['values'] ?? [], $valueRules)->validate();
        $values = [...array_diff_key($values, TechnicalSetup::kartGeometryRules()), ...TechnicalSetup::selectedKartGeometry($values)];
        if (isset($values['pinion_teeth'], $values['crown_teeth'])) {
            $values['final_drive'] = $values['pinion_teeth'].'/'.$values['crown_teeth'];
            $values['gear_ratio'] = round((int) $values['crown_teeth'] / (int) $values['pinion_teeth'], 3);
        }

        $setup = TechnicalSetup::query()->create([
            'vehicle_id' => $validated['vehicle_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'values' => $values,
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
        $workspace = $this->hasCloudAccess($user)
            ? $this->workspaceContext->personal($user)
            : $this->workspaceContext->current($user);

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

    private function dateValue(mixed $value, bool $dateOnly = false): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = Carbon::parse((string) $value);

        return $dateOnly ? $date->toDateString() : $date->toIso8601String();
    }
}
