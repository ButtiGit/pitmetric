<?php

namespace App\Services;

use App\Models\Championship;
use App\Models\ChampionshipRound;
use App\Models\ContractChange;
use App\Models\ContractExtension;
use App\Models\ContractParticipation;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\SeasonContract;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeasonContractService
{
    /** @param array<string, mixed> $data */
    public function create(Workspace $workspace, User $user, array $data): SeasonContract
    {
        return DB::transaction(function () use ($workspace, $user, $data): SeasonContract {
            $this->lockWorkspace((int) $workspace->id);
            Driver::query()->where('status', 'active')->where('workspace_id', $workspace->id)->findOrFail((int) $data['driver_id']);
            Vehicle::query()->where('status', 'active')->where('category', 'kart')->where('workspace_id', $workspace->id)->findOrFail((int) $data['vehicle_id']);
            if (! empty($data['championship_id'])) {
                Championship::query()->where('workspace_id', $workspace->id)->findOrFail((int) $data['championship_id']);
            }
            $this->noOverlap((int) $workspace->id, (int) $data['driver_id'], (int) $data['vehicle_id'], $data['starts_on'], $data['ends_on']);
            $contract = SeasonContract::create(['workspace_id' => $workspace->id, 'driver_id' => $data['driver_id'], 'vehicle_id' => $data['vehicle_id'],
                'championship_id' => $data['championship_id'] ?? null, 'season' => $data['season'], 'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'],
                'initial_races' => $data['initial_races'], 'notes' => $data['notes'] ?? null, 'created_by' => $user->id]);
            $this->log($contract, $user, 'created', __('contracts.created'), [], $contract->getAttributes());

            return $contract;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function update(SeasonContract $contract, User $user, array $data): void
    {
        DB::transaction(function () use ($contract, $user, $data): void {
            $locked = $this->lockContract($contract);
            $this->revision($locked->revision, (int) $data['revision']);
            $before = $locked->getAttributes();
            if ($data['status'] !== 'completed') {
                $this->noOverlap((int) $locked->workspace_id, (int) $locked->driver_id, (int) $locked->vehicle_id, $data['starts_on'], $data['ends_on'], (int) $locked->id);
            }
            $outside = $locked->participations()->whereHas('raceEvent', fn ($q) => $q->whereDate('start_date', '<', $data['starts_on'])->orWhereDate('end_date', '>', $data['ends_on']))->exists();
            if ($outside) {
                $this->fail('period');
            }
            $locked->update(['starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'], 'status' => $data['status'], 'notes' => $data['notes'] ?? null, 'revision' => $locked->revision + 1]);
            $this->log($locked, $user, 'updated', $data['reason'], $before, $locked->getAttributes());
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function extend(SeasonContract $contract, User $user, array $data): void
    {
        DB::transaction(function () use ($contract, $user, $data): void {
            $locked = $this->lockContract($contract);
            $this->revision($locked->revision, (int) $data['revision']);
            if ($locked->status === 'completed' || $this->total($locked) + (int) $data['added_races'] > 100000) {
                $this->fail('closed');
            }
            $end = $data['ends_on'] ?? Carbon::parse($locked->ends_on)->toDateString();
            if ($end < Carbon::parse($locked->ends_on)->toDateString()) {
                $this->fail('period');
            }
            $this->noOverlap((int) $locked->workspace_id, (int) $locked->driver_id, (int) $locked->vehicle_id, Carbon::parse($locked->starts_on)->toDateString(), $end, (int) $locked->id);
            ContractExtension::create(['season_contract_id' => $locked->id, 'added_races' => $data['added_races'], 'ends_on' => $end, 'reason' => $data['reason'], 'actor_name' => $user->name, 'created_by' => $user->id]);
            $before = $locked->getAttributes();
            $locked->update(['ends_on' => $end, 'revision' => $locked->revision + 1]);
            $this->log($locked, $user, 'extended', $data['reason'], $before, ['added_races' => (int) $data['added_races'], 'ends_on' => $end]);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function attach(EventEntry $entry, User $user, array $data): ContractParticipation
    {
        return DB::transaction(function () use ($entry, $user, $data): ContractParticipation {
            $this->lockWorkspace((int) $entry->workspace_id);
            $entry = EventEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();
            $contract = SeasonContract::query()->where('workspace_id', $entry->workspace_id)->lockForUpdate()->findOrFail((int) $data['season_contract_id']);
            $round = empty($data['round_id']) ? null : ChampionshipRound::query()->where('workspace_id', $entry->workspace_id)->findOrFail((int) $data['round_id']);
            $this->context($contract, $entry, $round);
            $notes = $data['notes'] ?? null;
            if ((int) $entry->vehicle_id !== (int) $contract->vehicle_id && trim((string) $notes) === '') {
                $this->fail('substitution');
            }
            $existing = ContractParticipation::query()->where('event_entry_id', $entry->id)->lockForUpdate()->first();
            if ($existing === null && (int) ($data['revision'] ?? 0) > 0) {
                $this->fail('conflict');
            }
            if ($existing !== null && (int) ($data['revision'] ?? 0) === 0) {
                if ((int) $existing->season_contract_id === (int) $contract->id && (int) $existing->round_id === (int) ($round->id ?? 0)) {
                    return $existing;
                }
                $this->fail('conflict');
            }
            if ($existing !== null) {
                $this->revision($existing->revision, (int) ($data['revision'] ?? 0));
                if ($existing->status === 'confirmed') {
                    $this->fail('confirmed_edit');
                }
            }
            $duplicate = $contract->participations()->where(fn ($q) => $q->where('event_id', $entry->event_id)->when($round !== null, fn ($match) => $match->orWhere('round_id', $round->id)))
                ->when($existing !== null, fn ($q) => $q->whereKeyNot($existing->id))->exists();
            if ($duplicate) {
                $this->fail('duplicate');
            }
            $before = $existing?->getAttributes() ?? [];
            $old = $existing !== null && (int) $existing->season_contract_id !== (int) $contract->id ? SeasonContract::query()->lockForUpdate()->findOrFail($existing->season_contract_id) : null;
            $values = ['season_contract_id' => $contract->id, 'event_entry_id' => $entry->id, 'event_id' => $entry->event_id, 'driver_id' => $entry->driver_id,
                'vehicle_id' => $entry->vehicle_id, 'championship_id' => $round?->championship_id, 'round_id' => $round?->id,
                'status' => 'planned', 'notes' => $notes, 'revision' => ($existing->revision ?? 0) + 1];
            if ($existing !== null) {
                $existing->update($values);
                $participation = $existing;
            } else {
                $participation = ContractParticipation::create([...$values, 'created_by' => $user->id]);
            }
            if ($old !== null) {
                $old->increment('revision');
                $this->log($old, $user, 'transferred', $data['reason'] ?? __('contracts.associated'), $before, $participation->getAttributes(), $participation);
            }
            $contract->increment('revision');
            $this->log($contract, $user, 'associated', $data['reason'] ?? __('contracts.associated'), $before, $participation->getAttributes(), $participation);

            return $participation;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function change(ContractParticipation $participation, User $user, array $data): void
    {
        DB::transaction(function () use ($participation, $user, $data): void {
            $this->lockWorkspace((int) $participation->workspace_id);
            $row = ContractParticipation::query()->lockForUpdate()->findOrFail($participation->id);
            $contract = SeasonContract::query()->lockForUpdate()->findOrFail($row->season_contract_id);
            $this->revision($row->revision, (int) $data['revision']);
            $before = $row->getAttributes();
            if ($data['status'] === 'confirmed') {
                $entry = EventEntry::query()->lockForUpdate()->findOrFail($row->event_entry_id);
                $round = $row->round_id === null ? null : ChampionshipRound::query()->findOrFail($row->round_id);
                $this->context($contract, $entry, $round);
                if ($row->status !== 'confirmed' && $contract->participations()->where('status', 'confirmed')->count() >= $this->total($contract)) {
                    $this->fail('exhausted');
                }
            }
            $delta = (int) ($data['status'] === 'confirmed') - (int) ($row->status === 'confirmed');
            $row->update(['status' => $data['status'], 'revision' => $row->revision + 1,
                'confirmed_at' => $data['status'] === 'confirmed' ? ($row->confirmed_at ?? now()) : null,
                'confirmed_name' => $data['status'] === 'confirmed' ? ($row->confirmed_name ?? $user->name) : null,
                'confirmed_by' => $data['status'] === 'confirmed' ? ($row->confirmed_by ?? $user->id) : null]);
            $contract->increment('revision');
            $this->log($contract, $user, 'participation', $data['reason'], $before, $row->getAttributes(), $row, $delta);
        }, 3);
    }

    public function total(SeasonContract $contract): int
    {
        return $contract->initial_races + (int) $contract->extensions()->sum('added_races');
    }

    private function lockWorkspace(int $id): void
    {
        Workspace::query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    private function lockContract(SeasonContract $contract): SeasonContract
    {
        $this->lockWorkspace((int) $contract->workspace_id);

        return SeasonContract::query()->lockForUpdate()->findOrFail($contract->id);
    }

    private function revision(int $actual, int $expected): void
    {
        if ($actual !== $expected) {
            $this->fail('conflict');
        }
    }

    private function context(SeasonContract $contract, EventEntry $entry, ?ChampionshipRound $round): void
    {
        $entry->load('raceEvent');
        if ($contract->status !== 'active' || (int) $contract->driver_id !== (int) $entry->driver_id) {
            $this->fail('closed');
        }
        if ($entry->raceEvent === null || $entry->raceEvent->status === 'cancelled'
            || Carbon::parse($entry->raceEvent->start_date)->toDateString() < Carbon::parse($contract->starts_on)->toDateString()
            || Carbon::parse($entry->raceEvent->end_date)->toDateString() > Carbon::parse($contract->ends_on)->toDateString()) {
            $this->fail('period');
        }
        if (($contract->championship_id !== null && $round === null)
            || ($round !== null && ((int) $round->championship_id !== (int) $contract->championship_id || (int) $round->event_id !== (int) $entry->event_id || $round->status === 'cancelled'))) {
            $this->fail('round');
        }
    }

    private function noOverlap(int $workspace, int $driver, int $vehicle, string $start, string $end, ?int $except = null): void
    {
        if (SeasonContract::query()->where('workspace_id', $workspace)->whereIn('status', ['active', 'suspended'])
            ->whereDate('starts_on', '<=', $end)->whereDate('ends_on', '>=', $start)
            ->where(fn ($q) => $q->where('driver_id', $driver)->orWhere('vehicle_id', $vehicle))
            ->when($except !== null, fn ($q) => $q->whereKeyNot($except))->exists()) {
            $this->fail('overlap');
        }
    }

    /** @param array<string, mixed> $before
     * @param  array<string, mixed>  $after
     */
    private function log(SeasonContract $contract, User $user, string $kind, string $reason, array $before, array $after, ?ContractParticipation $participation = null, int $delta = 0): void
    {
        ContractChange::create(['workspace_id' => $contract->workspace_id, 'season_contract_id' => $contract->id, 'contract_participation_id' => $participation?->id,
            'kind' => $kind, 'delta' => $delta, 'reason' => $reason, 'actor_name' => $user->name, 'before_values' => $before, 'after_values' => $after, 'created_by' => $user->id]);
    }

    private function fail(string $key): never
    {
        throw ValidationException::withMessages(['contract' => __('contracts.errors.'.$key)]);
    }
}
