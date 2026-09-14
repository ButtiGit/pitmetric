<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['source_type', 'source_id', 'usage_metric_type_id', 'value', 'occurred_at', 'created_by'])]
class UsageBatch extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<UsageMetricType, $this> */
    public function metric(): BelongsTo
    {
        return $this->belongsTo(UsageMetricType::class, 'usage_metric_type_id');
    }

    /** @return HasMany<ComponentUsageEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(ComponentUsageEntry::class);
    }

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'value' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }
}
