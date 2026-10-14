<?php

namespace App\Modules\ButtiDeskPro\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\ButtiDeskPro\Models\Appointment;
use App\Modules\ButtiDeskPro\Models\CalendarFeed;
use App\Modules\ButtiDeskPro\Models\Contact;
use App\Modules\ButtiDeskPro\Models\Ticket;
use App\Services\WorkspaceContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class CalendarController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $month = (string) $request->query('month', now((string) config('buttidesk.timezone'))->format('Y-m'));

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = now((string) config('buttidesk.timezone'))->format('Y-m');
        }

        $start = Carbon::parse($month.'-01', (string) config('buttidesk.timezone'))->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return view('buttidesk.calendar', [
            'month' => $month,
            'monthStart' => $start,
            'appointments' => Appointment::query()->with(['ticket', 'contact'])
                ->where('starts_at', '<=', $end->copy()->utc())->where('ends_at', '>=', $start->copy()->utc())
                ->orderBy('starts_at')->get(),
            'tickets' => Ticket::query()->whereNotIn('status', ['done', 'cancelled'])->orderBy('title')->get(['id', 'title']),
            'contacts' => Contact::query()->orderBy('name')->get(['id', 'name']),
            'feedEnabled' => CalendarFeed::query()->where('workspace_id', $context->personal($user)->id)->where('user_id', $user->id)->exists(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceId = (int) $context->personal($user)->id;
        $data = $this->validated($request, $workspaceId);
        $month = substr($data['starts_at'], 0, 7);
        $data['starts_at'] = Carbon::parse((string) $data['starts_at'], (string) config('buttidesk.timezone'))->utc();
        $data['ends_at'] = Carbon::parse((string) $data['ends_at'], (string) config('buttidesk.timezone'))->utc();
        Appointment::query()->create($data + ['created_by' => $user->id]);

        return to_route('buttidesk.calendar.index', ['month' => $month])
            ->with('status', 'Impegno inserito nel calendario.');
    }

    public function update(Request $request, Appointment $appointment, WorkspaceContext $context): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        $data = $this->validated($request, (int) $context->personal($user)->id);
        $data['starts_at'] = Carbon::parse((string) $data['starts_at'], (string) config('buttidesk.timezone'))->utc();
        $data['ends_at'] = Carbon::parse((string) $data['ends_at'], (string) config('buttidesk.timezone'))->utc();
        $appointment->update($data);

        return back()->with('status', 'Impegno aggiornato.');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();

        return back()->with('status', 'Impegno eliminato.');
    }

    public function download(): Response
    {
        $workspaceEvents = Appointment::query()->orderBy('starts_at')->get();

        return $this->icsResponse($workspaceEvents, true);
    }

    public function newFeed(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        $token = Str::random(48);
        CalendarFeed::query()->updateOrCreate([
            'workspace_id' => $context->personal($user)->id,
            'user_id' => $user->id,
        ], ['token_hash' => hash('sha256', $token)]);

        return back()->with('status', 'Link privato generato. Copialo ora: non sarà più mostrato.')
            ->with('desk_calendar_url', route('buttidesk.calendar.feed', ['token' => $token]));
    }

    public function removeFeed(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(401);
        }

        CalendarFeed::query()->where('workspace_id', $context->personal($user)->id)
            ->where('user_id', $user->id)->delete();

        return back()->with('status', 'Il link di sincronizzazione è stato revocato.');
    }

    public function feed(string $token): Response
    {
        $feed = CalendarFeed::query()->where('token_hash', hash('sha256', $token))->firstOrFail();

        abort_unless(DB::table('workspace_user')
            ->where('workspace_id', $feed->workspace_id)
            ->where('user_id', $feed->user_id)
            ->where('status', 'active')
            ->exists(), 404);

        $events = Appointment::query()->withoutGlobalScopes()
            ->where('workspace_id', $feed->workspace_id)->orderBy('starts_at')->get();

        return $this->icsResponse($events, false);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, int $workspaceId): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'location' => ['nullable', 'string', 'max:200'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'ticket_id' => ['nullable', 'integer', Rule::exists('desk_tickets', 'id')->where('workspace_id', $workspaceId)],
            'contact_id' => ['nullable', 'integer', Rule::exists('desk_contacts', 'id')->where('workspace_id', $workspaceId)],
        ]);
    }

    /** @param iterable<Appointment> $events */
    private function icsResponse(iterable $events, bool $attachment): Response
    {
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//PitMetric//ButtiDesk-Pro//IT', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'X-WR-CALNAME:ButtiDesk-Pro'];

        foreach ($events as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:buttidesk-'.$event->id.'@'.parse_url((string) config('app.url'), PHP_URL_HOST);
            $lines[] = 'DTSTAMP:'.$event->updated_at->copy()->utc()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART:'.Carbon::parse((string) $event->starts_at, 'UTC')->format('Ymd\THis\Z');
            $lines[] = 'DTEND:'.Carbon::parse((string) $event->ends_at, 'UTC')->format('Ymd\THis\Z');
            $lines[] = 'SUMMARY:'.$this->escapeIcs($event->title);
            $lines[] = 'DESCRIPTION:'.$this->escapeIcs((string) $event->description);
            $lines[] = 'LOCATION:'.$this->escapeIcs((string) $event->location);
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        $headers = ['Content-Type' => 'text/calendar; charset=utf-8', 'Cache-Control' => 'private, no-store'];
        if ($attachment) {
            $headers['Content-Disposition'] = 'attachment; filename="buttidesk-calendar.ics"';
        }

        return response(implode("\r\n", $lines)."\r\n", 200, $headers);
    }

    private function escapeIcs(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n", "\r"], ['\\\\', '\;', '\,', '\n', '\n', '\n'], $value);
    }
}
