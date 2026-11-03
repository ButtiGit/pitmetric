<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\ContractExtensionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['season_contract_id', 'added_races', 'ends_on', 'reason', 'actor_name', 'created_by'])]
class ContractExtension extends Model
{
    /** @use HasFactory<ContractExtensionFactory> */
    use BelongsToWorkspace, HasFactory;

    /** @return BelongsTo<SeasonContract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(SeasonContract::class, 'season_contract_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return ['added_races' => 'integer', 'ends_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new \LogicException('Contract history is immutable.');
        });
        static::deleting(function (): never {
            throw new \LogicException('Contract history is immutable.');
        });
    }
}
