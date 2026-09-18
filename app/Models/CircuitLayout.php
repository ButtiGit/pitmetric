<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['circuit_id', 'name', 'length_meters', 'is_active', 'notes'])]
class CircuitLayout extends Model
{
    use SoftDeletes;

    /** @return BelongsTo<Circuit, $this> */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(Circuit::class)->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'length_meters' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
