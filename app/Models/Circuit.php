<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'country', 'notes', 'latitude', 'longitude'])]
class Circuit extends Model
{
    use BelongsToWorkspace;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    /** @return HasMany<CircuitLayout, $this> */
    public function layouts(): HasMany
    {
        return $this->hasMany(CircuitLayout::class);
    }
}
