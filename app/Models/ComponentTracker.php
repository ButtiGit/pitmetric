<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['component_id', 'usage_metric_type_id', 'warning_threshold', 'service_limit', 'is_active'])]
class ComponentTracker extends Model
{
    /** @return BelongsTo<Component, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /** @return BelongsTo<UsageMetricType, $this> */
    public function metric(): BelongsTo
    {
        return $this->belongsTo(UsageMetricType::class, 'usage_metric_type_id');
    }

    /** @return HasMany<ComponentUsageEntry, $this> */
    public function usageEntries(): HasMany
    {
        return $this->hasMany(ComponentUsageEntry::class);
    }

    /** @return HasMany<TrackerResetEvent, $this> */
    public function resetEvents(): HasMany
    {
        return $this->hasMany(TrackerResetEvent::class);
    }

    protected function casts(): array
    {
        return [
            'warning_threshold' => 'integer',
            'service_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
