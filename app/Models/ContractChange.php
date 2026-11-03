<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['season_contract_id', 'contract_participation_id', 'kind', 'delta', 'reason', 'actor_name', 'before_values', 'after_values', 'created_by'])]
class ContractChange extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<ContractParticipation, $this> */
    public function participation(): BelongsTo
    {
        return $this->belongsTo(ContractParticipation::class, 'contract_participation_id');
    }

    protected function casts(): array
    {
        return ['delta' => 'integer', 'before_values' => 'array', 'after_values' => 'array'];
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
