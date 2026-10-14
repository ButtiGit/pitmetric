<?php

namespace App\Modules\ButtiDeskPro\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\ButtiDeskPro\Models\Contact;
use App\Modules\ButtiDeskPro\Models\Ticket;
use App\Modules\ButtiDeskPro\Models\Worklog;
use App\Services\WorkspaceContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

final class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));

        $tickets = Ticket::query()->with(['client', 'supplier'])
            ->when(in_array($status, ['open', 'progress', 'waiting', 'done', 'cancelled'], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', '%'.$search.'%')->orWhere('description', 'like', '%'.$search.'%');
            }))
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
            ->latest('id')->paginate(20)->withQueryString();

        return view('buttidesk.tickets', [
            'tickets' => $tickets,
            'statusFilter' => $status,
            'search' => $search,
            'clients' => Contact::query()->whereIn('type', ['client', 'both'])->orderBy('name')->get(),
            'suppliers' => Contact::query()->whereIn('type', ['supplier', 'both'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceId = (int) $context->personal($user)->getKey();
        $data = $this->validated($request, $workspaceId);
        if (! empty($data['due_at'])) {
            $data['due_at'] = Carbon::parse((string) $data['due_at'], (string) config('buttidesk.timezone'))->utc();
        }

        $ticket = Ticket::query()->create($data + ['created_by' => $user->getKey(), 'closed_at' => $data['status'] === 'done' ? now() : null]);

        return to_route('buttidesk.tickets.show', $ticket)->with('status', 'Ticket creato.');
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['client', 'supplier', 'worklogs.author', 'appointments', 'mailLogs']);

        return view('buttidesk.ticket', [
            'ticket' => $ticket,
            'clients' => Contact::query()->whereIn('type', ['client', 'both'])->orderBy('name')->get(),
            'suppliers' => Contact::query()->whereIn('type', ['supplier', 'both'])->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Ticket $ticket, WorkspaceContext $context): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceId = (int) $context->personal($user)->getKey();
        $data = $this->validated($request, $workspaceId);
        if (! empty($data['due_at'])) {
            $data['due_at'] = Carbon::parse((string) $data['due_at'], (string) config('buttidesk.timezone'))->utc();
        }

        $data['closed_at'] = $data['status'] === 'done' ? ($ticket->closed_at ?? now()) : null;
        $ticket->update($data);

        return to_route('buttidesk.tickets.show', $ticket)->with('status', 'Ticket aggiornato.');
    }

    public function log(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $data = $request->validate([
            'summary' => ['required', 'string', 'max:220'],
            'details' => ['nullable', 'string', 'max:15000'],
            'duration_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'performed_at' => ['required', 'date', 'before_or_equal:tomorrow'],
        ]);

        $data['performed_at'] = Carbon::parse((string) $data['performed_at'], (string) config('buttidesk.timezone'))->utc();

        $ticket->worklogs()->create($data + [
            'workspace_id' => $ticket->workspace_id,
            'user_id' => $user->getKey(),
        ]);

        return to_route('buttidesk.tickets.show', $ticket)->with('status', 'Intervento registrato nel report.');
    }

    public function report(Ticket $ticket): View
    {
        $ticket->load(['client', 'supplier', 'worklogs.author', 'appointments']);

        return view('buttidesk.report', [
            'ticket' => $ticket,
            'totalMinutes' => $ticket->worklogs->sum('duration_minutes'),
        ]);
    }

    public function reports(Request $request): View
    {
        $today = Carbon::now((string) config('buttidesk.timezone'));
        $start = (string) $request->query('from', $today->copy()->startOfMonth()->toDateString());
        $end = (string) $request->query('to', $today->toDateString());
        $request->merge(['from' => $start, 'to' => $end]);
        $period = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $startUtc = Carbon::parse($period['from'].' 00:00:00', (string) config('buttidesk.timezone'))->utc();
        $endUtc = Carbon::parse($period['to'].' 23:59:59', (string) config('buttidesk.timezone'))->utc();

        $logs = Worklog::query()
            ->with(['author', 'ticket.client'])
            ->whereBetween('performed_at', [$startUtc, $endUtc])
            ->orderByDesc('performed_at')->get();

        return view('buttidesk.reports', [
            'logs' => $logs,
            'totalMinutes' => $logs->sum('duration_minutes'),
            'period' => $period,
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, int $workspaceId): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:15000'],
            'category' => ['required', 'string', 'max:60'],
            'status' => ['required', Rule::in(['open', 'progress', 'waiting', 'done', 'cancelled'])],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'client_id' => ['nullable', 'integer', Rule::exists('desk_contacts', 'id')->where('workspace_id', $workspaceId)->whereIn('type', ['client', 'both'])],
            'supplier_id' => ['nullable', 'integer', Rule::exists('desk_contacts', 'id')->where('workspace_id', $workspaceId)->whereIn('type', ['supplier', 'both'])],
            'due_at' => ['nullable', 'date'],
        ]);
    }
}
