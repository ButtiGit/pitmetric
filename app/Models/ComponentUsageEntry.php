<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['component_tracker_id', 'usage_batch_id', 'value', 'occurred_at', 'notes', 'created_by'])]
class ComponentUsageEntry extends Model
{
    /** @return BelongsTo<ComponentTracker, $this> */
    public function tracker(): BelongsTo
    {
        return $this->belongsTo(ComponentTracker::class, 'component_tracker_id');
    }

    /** @return BelongsTo<UsageBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(UsageBatch::class, 'usage_batch_id');
    }

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }
}
