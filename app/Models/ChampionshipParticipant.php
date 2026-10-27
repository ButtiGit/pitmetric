<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['championship_id', 'driver_id', 'entry_number', 'team_name', 'status'])]
class ChampionshipParticipant extends Model
{
    use BelongsToWorkspace;

    protected $attributes = ['status' => 'active'];

    /** @return BelongsTo<Championship, $this> */
    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    /** @return BelongsTo<Driver, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class)->withTrashed();
    }

    /** @return HasMany<ChampionshipResult, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(ChampionshipResult::class, 'participant_id');
    }
}
