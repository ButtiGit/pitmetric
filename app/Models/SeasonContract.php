<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\SeasonContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['driver_id', 'vehicle_id', 'championship_id', 'season', 'starts_on', 'ends_on', 'initial_races', 'status', 'revision', 'notes', 'created_by'])]
class SeasonContract extends Model
{
    /** @use HasFactory<SeasonContractFactory> */
    use BelongsToWorkspace, HasFactory;

    protected $attributes = ['status' => 'active', 'revision' => 1];

    /** @return BelongsTo<Driver, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id')->withTrashed();
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id')->withTrashed();
    }

    /** @return BelongsTo<Championship, $this> */
    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class, 'championship_id')->withTrashed();
    }

    /** @return HasMany<ContractParticipation, $this> */
    public function participations(): HasMany
    {
        return $this->hasMany(ContractParticipation::class, 'season_contract_id');
    }

    /** @return HasMany<ContractExtension, $this> */
    public function extensions(): HasMany
    {
        return $this->hasMany(ContractExtension::class, 'season_contract_id');
    }

    /** @return HasMany<ContractChange, $this> */
    public function changes(): HasMany
    {
        return $this->hasMany(ContractChange::class, 'season_contract_id');
    }

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'initial_races' => 'integer', 'revision' => 'integer'];
    }
}
