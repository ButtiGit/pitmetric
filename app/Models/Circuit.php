<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'country', 'is_active', 'notes'])]
class Circuit extends Model
{
    use BelongsToWorkspace;

    /** @return HasMany<CircuitLayout, $this> */
    public function layouts(): HasMany
    {
        return $this->hasMany(CircuitLayout::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
