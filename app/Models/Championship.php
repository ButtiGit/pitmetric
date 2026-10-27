<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\ChampionshipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'season', 'category', 'status', 'notes', 'points', 'pole_bonus', 'fastest_lap_bonus'])]
class Championship extends Model
{
    public const DEFAULT_POINTS = [1 => 2500, 2 => 1800, 3 => 1500, 4 => 1200, 5 => 1000, 6 => 800, 7 => 600, 8 => 400, 9 => 200, 10 => 100];

    use BelongsToWorkspace, SoftDeletes;

    /** @use HasFactory<ChampionshipFactory> */
    use HasFactory;

    protected $attributes = ['status' => 'planned', 'pole_bonus' => 0, 'fastest_lap_bonus' => 0];

    protected function casts(): array
    {
        return ['points' => 'array', 'pole_bonus' => 'integer', 'fastest_lap_bonus' => 'integer'];
    }

    /** @return HasMany<ChampionshipRound, $this> */
    public function rounds(): HasMany
    {
        return $this->hasMany(ChampionshipRound::class);
    }

    /** @return HasMany<ChampionshipParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(ChampionshipParticipant::class);
    }
}
