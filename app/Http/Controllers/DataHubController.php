<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Component;
use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use App\Models\WorkspaceAttachment;
use App\Services\WorkspaceContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataHubController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const ATTACHABLES = [
        'event' => RaceEvent::class,
        'expense' => Expense::class,
        'session' => Session::class,
        'maintenance_record' => MaintenanceRecord::class,
    ];

    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $this->user($request);
        $workspace = $workspaceContext->personal($user);

        return view('data-hub.index', [
            'workspace' => $workspace,
            'readiness' => $this->readiness($workspace),
            'auditLogs' => AuditLog::query()->with('actor')->latest()->limit(100)->get(),
            'attachments' => WorkspaceAttachment::query()->with('uploader')->latest()->limit(50)->get(),
            'attachmentTargets' => $this->attachmentTargets(),
            'notifications' => $user->notifications()->latest()->limit(25)->get(),
            'unreadNotifications' => $user->unreadNotifications()->count(),
        ]);
    }

    public function import(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $this->user($request);
        $workspaceContext->personal($user);

        $validated = $request->validate([
            'dataset' => ['required', 'in:vehicles,expenses'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel', 'max:5120'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $rows = $this->csvRows($file);

        if (count($rows) > 1000) {
            throw ValidationException::withMessages(['file' => __('Imports are limited to 1,000 rows per file.')]);
        }

        $created = DB::transaction(fn (): int => $validated['dataset'] === 'vehicles'
            ? $this->importVehicles($rows)
            : $this->importExpenses($rows, $user));

        return to_route('data-hub.index')->with('status', __('Imported :count rows successfully.', ['count' => $created]));
    }

    public function export(Request $request, string $dataset, WorkspaceContext $workspaceContext): StreamedResponse
    {
        $workspaceContext->personal($this->user($request));
        abort_unless(in_array($dataset, ['vehicles', 'expenses', 'sessions', 'maintenance'], true), 404);

        return response()->streamDownload(function () use ($dataset): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            match ($dataset) {
                'vehicles' => $this->writeVehiclesCsv($stream),
                'expenses' => $this->writeExpensesCsv($stream),
                'sessions' => $this->writeSessionsCsv($stream),
                'maintenance' => $this->writeMaintenanceCsv($stream),
            };

            fclose($stream);
        }, 'pitmetric-'.$dataset.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function backup(Request $request, WorkspaceContext $workspaceContext): StreamedResponse
    {
        $workspace = $workspaceContext->personal($this->user($request));
        $workspaceId = (int) $workspace->getKey();
        $payload = [
            'schema' => 'pitmetric-workspace-backup-v1',
            'exported_at' => now()->toIso8601String(),
            'workspace' => ['id' => $workspaceId, 'name' => $workspace->name],
            'vehicles' => Vehicle::query()->withTrashed()->get()->toArray(),
            'components' => Component::query()->get()->toArray(),
            'events' => RaceEvent::query()->get()->toArray(),
            'sessions' => Session::query()->get()->toArray(),
            'technical_setups' => TechnicalSetup::query()->get()->toArray(),
            'maintenance_records' => MaintenanceRecord::query()->get()->toArray(),
            'expenses' => Expense::query()->get()->toArray(),
            'audit_logs' => AuditLog::query()->get()->toArray(),
        ];

        return response()->streamDownload(
            static function () use ($payload): void {
                echo json_encode(
                    $payload,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
                );
            },
            'pitmetric-workspace-backup-'.$workspaceId.'-'.now()->format('Y-m-d-His').'.json',
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public function storeAttachment(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $this->user($request);
        $workspace = $workspaceContext->personal($user);
        $validated = $request->validate([
            'attachable_type' => ['required', 'in:'.implode(',', array_keys(self::ATTACHABLES))],
            'attachable_id' => ['required', 'integer'],
            'label' => ['nullable', 'string', 'max:180'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,csv,txt', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp,text/plain,text/csv,application/csv,application/vnd.ms-excel'],
        ]);

        $this->findAttachable((string) $validated['attachable_type'], (int) $validated['attachable_id']);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $extension = strtolower($file->extension() ?: 'bin');
        $path = 'pitmetric/'.$workspace->getKey().'/attachments/'.Str::uuid().'.'.$extension;
        $stored = Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));

        if ($stored === false) {
            throw ValidationException::withMessages(['file' => __('The file could not be stored.')]);
        }

        WorkspaceAttachment::create([
            'uploaded_by' => $user->getKey(),
            'attachable_type' => $validated['attachable_type'],
            'attachable_id' => (int) $validated['attachable_id'],
            'label' => $validated['label'] ?? null,
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 255, ''),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'size_bytes' => (int) $file->getSize(),
        ]);

        return to_route('data-hub.index')->with('status', __('Private document uploaded.'));
    }

    public function downloadAttachment(Request $request, WorkspaceAttachment $workspaceAttachment, WorkspaceContext $workspaceContext): StreamedResponse
    {
        $workspaceContext->personal($this->user($request));
        Gate::authorize('view', $workspaceAttachment);
        abort_unless(Storage::disk($workspaceAttachment->disk)->exists($workspaceAttachment->path), 404);

        return Storage::disk($workspaceAttachment->disk)->download(
            $workspaceAttachment->path,
            $workspaceAttachment->original_name,
            ['Content-Type' => $workspaceAttachment->mime_type],
        );
    }

    public function destroyAttachment(Request $request, WorkspaceAttachment $workspaceAttachment, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $workspaceContext->personal($this->user($request));
        Gate::authorize('delete', $workspaceAttachment);
        Storage::disk($workspaceAttachment->disk)->delete($workspaceAttachment->path);
        $workspaceAttachment->delete();

        return to_route('data-hub.index')->with('status', __('Document deleted.'));
    }

    /** @return list<array<string, string|null>> */
    private function csvRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => __('The CSV file could not be read.')]);
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => __('The CSV file needs a header row.')]);
        }

        $keys = array_map(static fn ($value): string => Str::snake(trim((string) $value)), $header);
        $rows = [];

        while (($values = fgetcsv($handle)) !== false) {
            if ($values === [null]) {
                continue;
            }

            $row = [];
            foreach ($keys as $index => $key) {
                $value = $values[$index] ?? null;
                $row[$key] = $value === null ? null : trim((string) $value);
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /** @param list<array<string, string|null>> $rows */
    private function importVehicles(array $rows): int
    {
        foreach ($rows as $offset => $row) {
            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'max:120'],
                'category' => ['required', 'in:'.implode(',', Vehicle::CATEGORIES)],
                'manufacturer' => ['nullable', 'string', 'max:120'],
                'model' => ['nullable', 'string', 'max:120'],
                'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
                'identifier' => ['nullable', 'string', 'max:120'],
                'status' => ['nullable', 'in:'.implode(',', Vehicle::STATUSES)],
                'notes' => ['nullable', 'string', 'max:2000'],
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages(['file' => __('Row :row: :message', ['row' => $offset + 2, 'message' => $validator->errors()->first()])]);
            }

            $data = $validator->validated();
            Vehicle::create([
                ...$data,
                'status' => $data['status'] ?? 'active',
                'year' => ($data['year'] ?? '') === '' ? null : (int) $data['year'],
            ]);
        }

        return count($rows);
    }

    /** @param list<array<string, string|null>> $rows */
    private function importExpenses(array $rows, User $user): int
    {
        foreach ($rows as $offset => $row) {
            $validator = Validator::make($row, [
                'amount' => ['required', 'numeric', 'gt:0', 'max:1000000'],
                'currency' => ['nullable', 'string', 'size:3'],
                'category' => ['required', 'string', 'max:80'],
                'description' => ['required', 'string', 'max:180'],
                'occurred_at' => ['required', 'date'],
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages(['file' => __('Row :row: :message', ['row' => $offset + 2, 'message' => $validator->errors()->first()])]);
            }

            $data = $validator->validated();
            Expense::create([
                'amount_cents' => (int) round(((float) $data['amount']) * 100),
                'currency' => strtoupper($data['currency'] ?? 'EUR'),
                'category' => $data['category'],
                'description' => $data['description'],
                'occurred_at' => Carbon::parse($data['occurred_at']),
                'created_by' => $user->getKey(),
            ]);
        }

        return count($rows);
    }

    /** @return array<string, array<int, array{id: int, label: string}>> */
    private function attachmentTargets(): array
    {
        return [
            'event' => RaceEvent::query()->latest('start_date')->limit(50)->get()->map(fn (RaceEvent $event): array => ['id' => (int) $event->getKey(), 'label' => $event->name])->values()->all(),
            'expense' => Expense::query()->latest('occurred_at')->limit(50)->get()->map(fn (Expense $expense): array => ['id' => (int) $expense->getKey(), 'label' => $expense->description])->values()->all(),
            'session' => Session::query()->latest('started_at')->limit(50)->get()->map(fn (Session $session): array => ['id' => (int) $session->getKey(), 'label' => ucfirst($session->session_type).' · '.Carbon::parse((string) $session->started_at)->format('d/m/Y H:i')])->values()->all(),
            'maintenance_record' => MaintenanceRecord::query()->latest('performed_at')->limit(50)->get()->map(fn (MaintenanceRecord $record): array => ['id' => (int) $record->getKey(), 'label' => $record->description])->values()->all(),
        ];
    }

    private function findAttachable(string $type, int $id): Model
    {
        $model = self::ATTACHABLES[$type] ?? null;
        abort_unless($model !== null, 404);

        return $model::query()->findOrFail($id);
    }

    /** @return array{completed: int, total: int, items: list<array{label: string, done: bool, url: string}>} */
    private function readiness(Workspace $workspace): array
    {
        $items = [
            ['label' => __('Add the first vehicle'), 'done' => Vehicle::query()->exists(), 'url' => route('garage.index')],
            ['label' => __('Register components'), 'done' => Component::query()->exists(), 'url' => route('components.index')],
            ['label' => __('Create a race weekend'), 'done' => RaceEvent::query()->exists(), 'url' => route('events.index')],
            ['label' => __('Finalize a track session'), 'done' => Session::query()->where('status', 'finalized')->exists(), 'url' => route('sessions.index')],
            ['label' => __('Record operational costs'), 'done' => Expense::query()->exists(), 'url' => route('expenses.index')],
            ['label' => __('Invite a teammate'), 'done' => $workspace->users()->wherePivot('status', 'active')->count() > 1, 'url' => route('team.index')],
        ];

        return ['completed' => collect($items)->where('done', true)->count(), 'total' => count($items), 'items' => $items];
    }

    /** @param resource $stream */
    private function writeVehiclesCsv($stream): void
    {
        fputcsv($stream, ['name', 'category', 'manufacturer', 'model', 'year', 'identifier', 'status', 'notes']);
        Vehicle::query()->orderBy('name')->each(fn (Vehicle $vehicle) => fputcsv($stream, [$vehicle->name, $vehicle->category, $vehicle->manufacturer, $vehicle->model, $vehicle->year, $vehicle->identifier, $vehicle->status, $vehicle->notes]));
    }

    /** @param resource $stream */
    private function writeExpensesCsv($stream): void
    {
        fputcsv($stream, ['amount', 'currency', 'category', 'description', 'occurred_at']);
        Expense::query()->orderBy('occurred_at')->each(fn (Expense $expense) => fputcsv($stream, [number_format($expense->amount_cents / 100, 2, '.', ''), $expense->currency, $expense->category, $expense->description, Carbon::parse((string) $expense->occurred_at)->toIso8601String()]));
    }

    /** @param resource $stream */
    private function writeSessionsCsv($stream): void
    {
        fputcsv($stream, ['id', 'event_id', 'vehicle_id', 'session_type', 'started_at', 'completed_laps', 'duration_seconds', 'distance_override_meters', 'status']);
        Session::query()->orderBy('started_at')->each(fn (Session $session) => fputcsv($stream, [$session->id, $session->event_id, $session->vehicle_id, $session->session_type, Carbon::parse((string) $session->started_at)->toIso8601String(), $session->completed_laps, $session->duration_seconds, $session->distance_override_meters, $session->status]));
    }

    /** @param resource $stream */
    private function writeMaintenanceCsv($stream): void
    {
        fputcsv($stream, ['id', 'component_id', 'performed_at', 'description', 'cost_cents', 'notes']);
        MaintenanceRecord::query()->orderBy('performed_at')->each(fn (MaintenanceRecord $record) => fputcsv($stream, [$record->id, $record->component_id, Carbon::parse((string) $record->performed_at)->toIso8601String(), $record->description, $record->cost_cents, $record->notes]));
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
