<?php

namespace App\Services;

use App\Models\ChampionshipRound;
use App\Models\ContractParticipation;
use App\Models\RaceEvent;
use App\Models\SeasonContract;
use Illuminate\Support\Carbon;

class SeasonContractPageService
{
    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function index(array $filters): array
    {
        $query = SeasonContract::query()->with(['driver', 'vehicle', 'championship'])
            ->withCount(['participations as consumed' => fn ($q) => $q->where('status', 'confirmed')])->withSum('extensions as extra_races', 'added_races');
        foreach (['status', 'driver_id', 'vehicle_id'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }

        return ['contracts' => $query->orderByDesc('starts_on')->orderByDesc('id')->paginate(15)->withQueryString(), 'filters' => $filters];
    }

    /** @return array<string, mixed> */
    public function detail(SeasonContract $contract): array
    {
        $contract->load(['driver', 'vehicle', 'championship', 'extensions.creator', 'changes.creator', 'changes.participation.raceEvent',
            'participations.raceEvent', 'participations.vehicle', 'participations.round', 'participations.confirmer']);
        $consumed = $contract->participations->where('status', 'confirmed')->count();
        $total = $contract->initial_races + (int) $contract->extensions->sum('added_races');

        return ['contract' => $contract, 'consumed' => $consumed, 'total' => $total, 'remaining' => max(0, $total - $consumed)];
    }

    /** @return array<string, mixed> */
    public function event(RaceEvent $event): array
    {
        $event->loadMissing('entries.driver', 'entries.vehicle');
        $contracts = SeasonContract::query()->with(['driver', 'vehicle', 'championship'])
            ->withCount(['participations as consumed' => fn ($q) => $q->where('status', 'confirmed')])->withSum('extensions as extra_races', 'added_races')
            ->where('status', 'active')
            ->whereDate('starts_on', '<=', Carbon::parse($event->start_date)->toDateString())->whereDate('ends_on', '>=', Carbon::parse($event->end_date)->toDateString())->get();
        $participations = ContractParticipation::query()->with(['contract' => fn ($q) => $q->with(['vehicle', 'driver', 'championship'])->withCount(['participations as consumed' => fn ($p) => $p->where('status', 'confirmed')])->withSum('extensions as extra_races', 'added_races'), 'round', 'vehicle', 'confirmer'])
            ->where('event_id', $event->id)->get()->keyBy('event_entry_id');

        return ['contractsForEvent' => $contracts, 'contractParticipations' => $participations, 'contractRounds' => ChampionshipRound::query()->with('championship')->where('event_id', $event->id)->get()];
    }
}
