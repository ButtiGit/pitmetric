<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\ContractParticipationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['season_contract_id', 'event_entry_id', 'event_id', 'driver_id', 'vehicle_id', 'championship_id', 'round_id', 'status', 'revision', 'notes', 'created_by', 'confirmed_by', 'confirmed_name', 'confirmed_at'])]
class ContractParticipation extends Model
{
    /** @use HasFactory<ContractParticipationFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $attributes = ['status' => 'planned', 'revision' => 1];

    /** @return BelongsTo<SeasonContract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(SeasonContract::class, 'season_contract_id');
    }

    /** @return BelongsTo<EventEntry, $this> */
    public function eventEntry(): BelongsTo
    {
        return $this->belongsTo(EventEntry::class, 'event_entry_id');
    }

    /** @return BelongsTo<RaceEvent, $this> */
    public function raceEvent(): BelongsTo
    {
        return $this->belongsTo(RaceEvent::class, 'event_id')->withTrashed();
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id')->withTrashed();
    }

    /** @return BelongsTo<ChampionshipRound, $this> */
    public function round(): BelongsTo
    {
        return $this->belongsTo(ChampionshipRound::class, 'round_id')->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'revision' => 'integer'];
    }
}
