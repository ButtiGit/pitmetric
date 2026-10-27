<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['championship_id', 'event_id', 'number', 'name', 'starts_on', 'ends_on', 'venue', 'status', 'notes'])]
class ChampionshipRound extends Model
{
    use BelongsToWorkspace, SoftDeletes;

    protected $attributes = ['status' => 'planned'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date'];
    }

    /** @return BelongsTo<Championship, $this> */
    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class)->withTrashed();
    }

    /** @return BelongsTo<RaceEvent, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(RaceEvent::class, 'event_id')->withTrashed();
    }

    /** @return HasMany<ChampionshipResult, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(ChampionshipResult::class, 'round_id');
    }
}
