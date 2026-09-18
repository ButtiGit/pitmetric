<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Component;
use App\Models\Document;
use App\Models\MaintenanceRecord;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AuditTrailService;
use App\Services\DataHubService;
use App\Services\OperationalNotificationService;
use App\Services\ReadinessService;
use App\Services\WorkspaceContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JsonException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ControlCenterController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const ATTACHABLE_TYPES = [
        'vehicle' => Vehicle::class,
        'component' => Component::class,
        'event' => RaceEvent::class,
        'maintenance_record' => MaintenanceRecord::class,
        'session' => Session::class,
    ];

    public function index(
        Request $request,
        WorkspaceContext $workspaceContext,
        OperationalNotificationService $notifications,
        ReadinessService $readiness,
    ): View {
        $user = $this->user($request);
        Gate::forUser($user)->authorize('team-manage');
        $workspace = $workspaceContext->personal($user);
        $notifications->generate($workspace);

        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:40'],
            'actor' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $activity = AuditLog::query()
            ->with('user')
            ->where('workspace_id', $workspace->getKey())
            ->when($filters['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->when($filters['actor'] ?? null, fn ($query, mixed $actor) => $query->where('user_id', (int) $actor))
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('subject_label', 'like', '%'.$search.'%')
                        ->orWhere('subject_type', 'like', '%'.$search.'%')
                        ->orWhere('action', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->where('created_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->where('created_at', '<=', $to.' 23:59:59'))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $workspaceNotifications = $user->notifications()
            ->latest()
            ->limit(100)
            ->get()
            ->filter(fn (DatabaseNotification $notification): bool => (int) ($notification->data['workspace_id'] ?? 0) === (int) $workspace->getKey())
            ->values();

        return view('control-center.index', [
            'workspace' => $workspace,
            'readiness' => $readiness->forWorkspace($workspace),
            'activity' => $activity,
            'activityFilters' => $filters,
            'actors' => $workspace->users()->wherePivot('status', 'active')->orderBy('name')->get(),
            'documents' => Document::query()->with('uploader')->latest('id')->limit(100)->get(),
            'notifications' => $workspaceNotifications,
            'unreadNotificationCount' => $workspaceNotifications->whereNull('read_at')->count(),
            'vehicles' => Vehicle::query()->orderBy('name')->limit(100)->get(),
            'components' => Component::query()->orderBy('name')->limit(100)->get(),
            'events' => RaceEvent::query()->latest('start_date')->limit(100)->get(),
            'maintenanceRecords' => MaintenanceRecord::query()->latest('performed_at')->limit(100)->get(),
            'sessions' => Session::query()->latest('started_at')->limit(100)->get(),
        ]);
    }

    public function export(
        Request $request,
        WorkspaceContext $workspaceContext,
        DataHubService $dataHub,
        AuditTrailService $audit,
    ): StreamedResponse {
        $user = $this->user($request);
        Gate::forUser($user)->authorize('team-manage');
        $workspace = $workspaceContext->personal($user);
        $payload = $dataHub->export($workspace);

        $audit->custom(
            (int) $workspace->getKey(),
            'data_exported',
            subjectType: 'workspace',
            subjectId: $workspace->getKey(),
            subjectLabel: $workspace->name,
            after: ['version' => DataHubService::VERSION],
        );

        $filename = 'pitmetric-'.Str::slug($workspace->name).'-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public function import(
        Request $request,
        WorkspaceContext $workspaceContext,
        DataHubService $dataHub,
        AuditTrailService $audit,
    ): RedirectResponse {
        $user = $this->user($request);
        Gate::forUser($user)->authorize('team-own');
        $workspace = $workspaceContext->personal($user);

        $validated = $request->validate([
            'backup' => ['required', 'file', 'max:51200', 'mimes:json,txt'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['backup'];
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw ValidationException::withMessages(['backup' => 'The backup could not be read.']);
        }

        try {
            $backup = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['backup' => 'The selected file is not valid JSON.']);
        }

        if (! is_array($backup)) {
            throw ValidationException::withMessages(['backup' => 'The selected backup has an invalid root payload.']);
        }

        $counts = $dataHub->import($workspace, $user, $backup);

        $audit->custom(
            (int) $workspace->getKey(),
            'data_imported',
            subjectType: 'workspace',
            subjectId: $workspace->getKey(),
            subjectLabel: $workspace->name,
            after: ['rows' => array_sum($counts), 'tables' => $counts],
        );

        return to_route('control-center.index')->with('status', 'Workspace backup imported successfully.');
    }

    public function storeDocument(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $this->user($request);
        Gate::forUser($user)->authorize('team-write');
        $workspace = $workspaceContext->personal($user);

        $validated = $request->validate([
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,csv,txt,xls,xlsx,doc,docx'],
            'name' => ['nullable', 'string', 'max:180'],
            'attachable' => ['nullable', 'string', 'regex:/^(vehicle|component|event|maintenance_record|session):[0-9]+$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $attachableType = null;
        $attachableId = null;

        if (isset($validated['attachable']) && $validated['attachable'] !== '') {
            [$attachableType, $rawAttachableId] = explode(':', $validated['attachable'], 2);
            $attachableId = (int) $rawAttachableId;
            $this->assertAttachableExists($attachableType, $attachableId);
        }

        /** @var UploadedFile $file */
        $file = $validated['document'];
        $path = $file->store('workspaces/'.$workspace->getKey().'/documents', 'local');

        if (! is_string($path)) {
            throw new \RuntimeException('Unable to store the uploaded document.');
        }

        try {
            Document::query()->create([
                'uploaded_by' => $user->getKey(),
                'attachable_type' => $attachableType,
                'attachable_id' => $attachableId,
                'name' => trim((string) ($validated['name'] ?? '')) ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_name' => $file->getClientOriginalName(),
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'notes' => $validated['notes'] ?? null,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return to_route('control-center.index')->with('status', 'Private document uploaded.');
    }

    public function updateDocument(Request $request, Document $document, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $workspaceContext->personal($this->user($request));
        Gate::authorize('team-write');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $document->update($validated);

        return to_route('control-center.index')->with('status', __('Document updated.'));
    }

    public function downloadDocument(
        Request $request,
        Document $document,
        WorkspaceContext $workspaceContext,
    ): StreamedResponse {
        $user = $this->user($request);
        $workspaceContext->personal($user);
        Gate::forUser($user)->authorize('team-view');

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function destroyDocument(
        Request $request,
        Document $document,
        WorkspaceContext $workspaceContext,
    ): RedirectResponse {
        $user = $this->user($request);
        $workspaceContext->personal($user);
        Gate::forUser($user)->authorize('team-write');

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return to_route('control-center.index')->with('status', 'Private document removed.');
    }

    public function readNotification(
        Request $request,
        string $notification,
        WorkspaceContext $workspaceContext,
    ): RedirectResponse {
        $user = $this->user($request);
        $workspace = $workspaceContext->personal($user);
        $record = $user->notifications()->findOrFail($notification);

        abort_unless((int) ($record->data['workspace_id'] ?? 0) === (int) $workspace->getKey(), 404);
        $record->markAsRead();

        return to_route('control-center.index');
    }

    public function readAllNotifications(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $this->user($request);
        $workspace = $workspaceContext->personal($user);

        $user->unreadNotifications()
            ->get()
            ->filter(fn (DatabaseNotification $notification): bool => (int) ($notification->data['workspace_id'] ?? 0) === (int) $workspace->getKey())
            ->each(fn (DatabaseNotification $notification) => $notification->markAsRead());

        return to_route('control-center.index')->with('status', 'Operational alerts marked as read.');
    }

    private function assertAttachableExists(string $type, int $id): void
    {
        $modelClass = self::ATTACHABLE_TYPES[$type] ?? null;

        if ($modelClass === null || ! $modelClass::query()->whereKey($id)->exists()) {
            throw ValidationException::withMessages([
                'attachable_id' => 'The selected PitMetric record is not available in this workspace.',
            ]);
        }
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
