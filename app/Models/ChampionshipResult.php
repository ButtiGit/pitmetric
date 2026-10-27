<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['championship_id', 'round_id', 'participant_id', 'position', 'status', 'pole', 'fastest_lap', 'adjustment', 'notes'])]
class ChampionshipResult extends Model
{
    use BelongsToWorkspace, SoftDeletes;

    protected $attributes = ['status' => 'classified', 'pole' => false, 'fastest_lap' => false, 'adjustment' => 0];

    protected function casts(): array
    {
        return ['position' => 'integer', 'pole' => 'boolean', 'fastest_lap' => 'boolean', 'adjustment' => 'integer'];
    }

    /** @return BelongsTo<ChampionshipRound, $this> */
    public function round(): BelongsTo
    {
        return $this->belongsTo(ChampionshipRound::class, 'round_id');
    }

    /** @return BelongsTo<ChampionshipParticipant, $this> */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(ChampionshipParticipant::class, 'participant_id');
    }
}
