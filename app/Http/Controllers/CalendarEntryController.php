<?php

namespace App\Http\Controllers;

use App\Models\CalendarEntry;
use App\Models\StudioMailMessage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class CalendarEntryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->authorizedUser($request);
        $timezone = $this->timezone();
        $month = $request->query('month');
        if (! is_string($month) || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            $month = now($timezone)->format('Y-m');
        }

        $monthStart = Carbon::parse($month.'-01', $timezone)->startOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek();
        $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek();
        $day = $request->query('day');
        $selectedDay = is_string($day) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) === 1
            && Carbon::hasFormat($day, 'Y-m-d') && Str::startsWith($day, $month.'-') ? $day : null;

        $entries = CalendarEntry::query()->where('user_id', $user->id)
            ->whereBetween('starts_at', [$gridStart->copy()->utc(), $gridEnd->copy()->endOfDay()->utc()])
            ->with('sourceMail:id,provider_id,direction,subject')
            ->orderBy('starts_at')->get();
        $entriesByDay = $entries->groupBy(fn (CalendarEntry $entry): string => Carbon::parse($entry->starts_at, 'UTC')->timezone($timezone)->toDateString());
        $agenda = $entries->filter(function (CalendarEntry $entry) use ($selectedDay, $month, $timezone): bool {
            $date = Carbon::parse($entry->starts_at, 'UTC')->timezone($timezone);

            return $selectedDay !== null ? $date->toDateString() === $selectedDay : $date->format('Y-m') === $month;
        });

        $sourceMail = null;
        if ($request->filled('mail')) {
            abort_unless(Gate::forUser($user)->allows('manage-updates'), 403);
            $source = $request->validate(['mail' => ['required', 'string', 'max:255']]);
            $sourceMail = StudioMailMessage::query()->where('provider_id', $source['mail'])->firstOrFail();
        }

        return view('calendar.index', [
            'monthStart' => $monthStart,
            'gridStart' => $gridStart,
            'gridEnd' => $gridEnd,
            'month' => $month,
            'selectedDay' => $selectedDay,
            'entries' => $entries,
            'entriesByDay' => $entriesByDay,
            'agenda' => $agenda,
            'sourceMail' => $sourceMail,
            'timezone' => $timezone,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->authorizedUser($request);
        $data = $this->validatedEntry($request);
        $sourceMailId = $request->input('studio_mail_message_id');
        if ($sourceMailId !== null && $sourceMailId !== '' && ! Gate::forUser($user)->allows('manage-updates')) {
            throw ValidationException::withMessages(['studio_mail_message_id' => 'Non puoi collegare email dello Studio.']);
        }

        $data['studio_mail_message_id'] = $sourceMailId ?: null;
        $data['user_id'] = $user->id;
        CalendarEntry::query()->create($data);

        return to_route('calendar.index', ['month' => $data['starts_at']->copy()->timezone($this->timezone())->format('Y-m')])
            ->with('status', 'Evento aggiunto al calendario.');
    }

    public function update(Request $request, CalendarEntry $calendarEntry): RedirectResponse
    {
        $user = $this->authorizedUser($request);
        abort_unless($calendarEntry->user_id === $user->id, 404);
        $calendarEntry->update($this->validatedEntry($request));

        return back()->with('status', 'Evento aggiornato.');
    }

    public function destroy(Request $request, CalendarEntry $calendarEntry): RedirectResponse
    {
        $user = $this->authorizedUser($request);
        abort_unless($calendarEntry->user_id === $user->id, 404);
        $calendarEntry->delete();

        return back()->with('status', 'Evento eliminato.');
    }

    /** @return array<string, mixed> */
    private function validatedEntry(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'location' => ['nullable', 'string', 'max:200'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'studio_mail_message_id' => ['sometimes', 'nullable', 'integer', Rule::exists('studio_mail_messages', 'id')],
        ]);
        $data['starts_at'] = Carbon::parse($data['starts_at'], $this->timezone())->utc();
        $data['ends_at'] = Carbon::parse($data['ends_at'], $this->timezone())->utc();
        unset($data['studio_mail_message_id']);

        return $data;
    }

    private function authorizedUser(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($user->hasDatabaseAccess() || Gate::forUser($user)->allows('manage-updates'), 403);

        return $user;
    }

    private function timezone(): string
    {
        return (string) config('pitmetric.calendar_timezone', 'Europe/Rome');
    }
}
