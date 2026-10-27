<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\Driver;
use App\Models\RaceEvent;
use App\Services\ChampionshipRecords;
use App\Services\ChampionshipStandings;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ChampionshipController extends Controller
{
    public function __construct(private readonly ChampionshipRecords $records) {}

    public function index(Request $request, WorkspaceContext $context): View
    {
        if (! $context->isChampionshipsReady()) {
            return view('garage.unavailable');
        }
        Gate::authorize('viewAny', Championship::class);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::in(['planned', 'active', 'completed', 'archived'])]]);
        $query = Championship::query();
        if (($filters['status'] ?? '') === 'archived') {
            $query->onlyTrashed();
        } elseif (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return view('championships.index', ['championships' => $query->withCount(['rounds', 'participants'])->orderByDesc('season')->orderByDesc('id')->paginate(12)->withQueryString()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Championship::class);
        $championship = Championship::create($this->records->metadata($request->all()) + ['points' => Championship::DEFAULT_POINTS]);

        return to_route('championships.show', $championship)->with('status', __('championship.saved'));
    }

    public function show(Championship $championship, ChampionshipStandings $standings): View
    {
        Gate::authorize('view', $championship);
        $championship->load(['rounds' => fn ($query) => $query->orderBy('number'), 'rounds.event', 'rounds.results.participant.driver', 'participants.driver']);

        return view('championships.show', [
            'championship' => $championship, 'standings' => $standings->table($championship),
            'nextRound' => ((int) $championship->rounds()->withTrashed()->max('number')) + 1,
            'drivers' => Driver::query()->where('status', 'active')->orderBy('display_name')->get(),
            'events' => RaceEvent::query()->orderByDesc('start_date')->limit(200)->get(),
        ]);
    }

    public function update(Request $request, Championship $championship): RedirectResponse
    {
        Gate::authorize('update', $championship);
        $championship->update($this->records->metadata($request->all()));

        return back()->with('status', __('championship.saved'));
    }

    public function archive(Championship $championship): RedirectResponse
    {
        Gate::authorize('delete', $championship);
        $championship->delete();

        return to_route('championships.index')->with('status', __('championship.archived'));
    }

    public function restore(Championship $championship): RedirectResponse
    {
        Gate::authorize('restore', $championship);
        $championship->restore();

        return to_route('championships.show', $championship)->with('status', __('championship.saved'));
    }

    public function scoring(Request $request, Championship $championship): RedirectResponse
    {
        Gate::authorize('update', $championship);
        DB::transaction(function () use ($request, $championship): void {
            $this->records->lock($championship);
            $this->records->scoring($championship, $request->all());
        });

        return back()->with('status', __('championship.saved'));
    }

    public function round(Request $request, Championship $championship, ?int $round = null): RedirectResponse
    {
        Gate::authorize('update', $championship);
        $record = $round === null ? null : $championship->rounds()->findOrFail($round);
        $this->records->round($championship, $request->all(), $record);

        return back()->with('championship_tab', 'rounds')->with('status', __('championship.saved'));
    }

    public function archiveRound(Championship $championship, int $round): RedirectResponse
    {
        Gate::authorize('update', $championship);
        DB::transaction(function () use ($championship, $round): void {
            $this->records->lock($championship);
            $record = $championship->rounds()->findOrFail($round);
            if ($record->results()->withTrashed()->exists()) {
                throw ValidationException::withMessages(['round' => __('championship.round_has_results')]);
            }
            $record->delete();
        });

        return back()->with('championship_tab', 'rounds')->with('status', __('championship.saved'));
    }

    public function participant(Request $request, Championship $championship): RedirectResponse
    {
        Gate::authorize('update', $championship);
        $this->records->participant($championship, $request->all());

        return back()->with('championship_tab', 'participants')->with('status', __('championship.saved'));
    }

    public function updateParticipant(Request $request, Championship $championship, int $participant): RedirectResponse
    {
        Gate::authorize('update', $championship);
        DB::transaction(function () use ($request, $championship, $participant): void {
            $this->records->lock($championship);
            $record = $championship->participants()->findOrFail($participant);
            $data = $this->records->participantData($championship, $request->except('driver_id') + ['driver_id' => $record->driver_id]);
            if (filled($data['number'] ?? null) && $championship->participants()->whereKeyNot($record->id)->where('entry_number', $data['number'])->exists()) {
                throw ValidationException::withMessages(['number' => __('championship.duplicate_number')]);
            }
            $record->update(['entry_number' => $data['number'] ?? null, 'team_name' => $data['team_name'] ?? null, 'status' => $data['status']]);
        });

        return back()->with('championship_tab', 'participants')->with('status', __('championship.saved'));
    }

    public function result(Request $request, Championship $championship, int $round): RedirectResponse
    {
        Gate::authorize('update', $championship);
        $this->records->result($championship, $championship->rounds()->findOrFail($round), $request->all());

        return back()->with('championship_tab', 'rounds')->with('status', __('championship.saved'));
    }

    public function removeResult(Championship $championship, int $round, int $result): RedirectResponse
    {
        Gate::authorize('update', $championship);
        DB::transaction(function () use ($championship, $round, $result): void {
            $this->records->lock($championship);
            $championship->rounds()->findOrFail($round)->results()->findOrFail($result)->delete();
        });

        return back()->with('championship_tab', 'rounds')->with('status', __('championship.saved'));
    }
}
