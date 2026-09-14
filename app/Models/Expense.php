<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['event_id', 'amount_cents', 'currency', 'category', 'description', 'occurred_at', 'related_type', 'related_id', 'created_by'])]
class Expense extends Model
{
    use BelongsToWorkspace, SoftDeletes;

    /** @return BelongsTo<RaceEvent, $this> */
    public function raceEvent(): BelongsTo
    {
        return $this->belongsTo(RaceEvent::class, 'event_id');
    }

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'occurred_at' => 'datetime',
            'related_id' => 'integer',
        ];
    }
}
