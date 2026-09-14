<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['component_tracker_id', 'name', 'interval_value', 'warning_value', 'is_active', 'notes'])]
class MaintenanceSchedule extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<ComponentTracker, $this> */
    public function tracker(): BelongsTo
    {
        return $this->belongsTo(ComponentTracker::class, 'component_tracker_id');
    }

    /** @return HasMany<MaintenanceRecord, $this> */
    public function records(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    protected function casts(): array
    {
        return [
            'interval_value' => 'integer',
            'warning_value' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
