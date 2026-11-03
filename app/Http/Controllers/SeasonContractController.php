<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\ChampionshipRound;
use App\Models\ContractParticipation;
use App\Models\Document;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\SeasonContract;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\SeasonContractPageService;
use App\Services\SeasonContractService;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class SeasonContractController extends Controller
{
    public function index(Request $request, WorkspaceContext $context, SeasonContractPageService $pages): Response
    {
        Gate::authorize('viewAny', SeasonContract::class);
        abort_unless($context->isContractsReady(), 503, __('contracts.unavailable'));
        $filters = $request->validate(['status' => ['nullable', 'in:active,suspended,completed'], 'driver_id' => ['nullable', 'integer', 'min:1'], 'vehicle_id' => ['nullable', 'integer', 'min:1']]);
        if (! empty($filters['driver_id'])) {
            Driver::withTrashed()->findOrFail($request->integer('driver_id'));
        }
        if (! empty($filters['vehicle_id'])) {
            Vehicle::withTrashed()->findOrFail($request->integer('vehicle_id'));
        }

        return response()->view('contracts.index', [...$pages->index($filters),
            'drivers' => Driver::withTrashed()->orderBy('display_name')->get(),
            'vehicles' => Vehicle::withTrashed()->where('category', 'kart')->orderBy('name')->get(),
            'championships' => Championship::query()->orderByDesc('season')->orderBy('name')->get()])->header('Cache-Control', 'private, no-store');
    }

    public function show(SeasonContract $seasonContract, SeasonContractPageService $pages): Response
    {
        Gate::authorize('view', $seasonContract);

        return response()->view('contracts.show', [...$pages->detail($seasonContract),
            'eligibleEntries' => EventEntry::query()->with(['raceEvent', 'vehicle'])->where('driver_id', $seasonContract->driver_id)->whereHas('raceEvent', fn ($q) => $q->whereDate('start_date', '>=', Carbon::parse($seasonContract->starts_on)->toDateString())->whereDate('end_date', '<=', Carbon::parse($seasonContract->ends_on)->toDateString()))->get(),
            'rounds' => ChampionshipRound::query()->where('championship_id', $seasonContract->championship_id)->whereNotNull('event_id')->get(),
            'documents' => Document::query()->with('uploader')->where('attachable_type', 'season_contract')->where('attachable_id', $seasonContract->id)->orderByDesc('id')->get(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, WorkspaceContext $context, SeasonContractService $contracts): RedirectResponse
    {
        Gate::authorize('create', SeasonContract::class);
        abort_unless($context->isContractsReady(), 503);
        $data = $request->validate(['driver_id' => ['required', 'integer', 'min:1'], 'vehicle_id' => ['required', 'integer', 'min:1'],
            'championship_id' => ['nullable', 'integer', 'min:1'], 'season' => ['required', 'string', 'max:40'],
            'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'initial_races' => ['required', 'integer', 'min:1', 'max:10000'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $user = $this->user($request);
        $contract = $contracts->create($context->personal($user), $user, $data);

        return to_route('contracts.show', $contract)->with('status', __('contracts.saved'));
    }

    public function update(Request $request, SeasonContract $seasonContract, SeasonContractService $contracts): RedirectResponse
    {
        Gate::authorize('update', $seasonContract);
        $data = $request->validate(['revision' => ['required', 'integer', 'min:1'], 'status' => ['required', 'in:active,suspended,completed'],
            'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string', 'max:2000'], 'reason' => ['required', 'string', 'max:1000']]);
        $contracts->update($seasonContract, $this->user($request), $data);

        return back()->with('status', __('contracts.saved'));
    }

    public function extend(Request $request, SeasonContract $seasonContract, SeasonContractService $contracts): RedirectResponse
    {
        Gate::authorize('update', $seasonContract);
        $data = $request->validate(['revision' => ['required', 'integer', 'min:1'], 'added_races' => ['required', 'integer', 'min:1', 'max:10000'],
            'ends_on' => ['nullable', 'date_format:Y-m-d'], 'reason' => ['required', 'string', 'max:1000']]);
        $contracts->extend($seasonContract, $this->user($request), $data);

        return back()->with('status', __('contracts.saved'));
    }

    public function attachFromContract(Request $request, SeasonContract $seasonContract, SeasonContractService $contracts): RedirectResponse
    {
        Gate::authorize('update', $seasonContract);
        $data = $request->validate(['event_entry_id' => ['required', 'integer', 'min:1'], 'round_id' => ['nullable', 'integer', 'min:1'], 'notes' => ['nullable', 'string', 'max:2000'], 'reason' => ['required', 'string', 'max:1000']]);
        $entry = EventEntry::query()->findOrFail($request->integer('event_entry_id'));
        $contracts->attach($entry, $this->user($request), [...$data, 'season_contract_id' => $seasonContract->id, 'revision' => 0]);

        return back()->with('status', __('contracts.saved'));
    }

    public function attach(Request $request, EventEntry $eventEntry, SeasonContractService $contracts): RedirectResponse
    {
        Gate::authorize('update', $eventEntry);
        abort_unless(app(WorkspaceContext::class)->isContractsReady(), 503);
        $data = $request->validate(['season_contract_id' => ['required', 'integer', 'min:1'], 'round_id' => ['nullable', 'integer', 'min:1'],
            'revision' => ['required', 'integer', 'min:0'], 'notes' => ['nullable', 'string', 'max:2000'], 'reason' => ['required', 'string', 'max:1000']]);
        $contracts->attach($eventEntry, $this->user($request), $data);

        return back()->with('status', __('contracts.saved'));
    }

    public function change(Request $request, ContractParticipation $contractParticipation, SeasonContractService $contracts): RedirectResponse
    {
        Gate::authorize('update', $contractParticipation);
        $data = $request->validate(['revision' => ['required', 'integer', 'min:1'], 'status' => ['required', 'in:planned,confirmed,absent,cancelled'], 'reason' => ['required', 'string', 'max:1000']]);
        $contracts->change($contractParticipation, $this->user($request), $data);

        return back()->with('status', __('contracts.saved'));
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
