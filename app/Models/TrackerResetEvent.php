<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['component_tracker_id', 'maintenance_record_id', 'reset_at', 'reason', 'created_by'])]
class TrackerResetEvent extends Model
{
    /** @return BelongsTo<ComponentTracker, $this> */
    public function tracker(): BelongsTo
    {
        return $this->belongsTo(ComponentTracker::class, 'component_tracker_id');
    }

    /** @return BelongsTo<MaintenanceRecord, $this> */
    public function maintenanceRecord(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class);
    }

    protected function casts(): array
    {
        return ['reset_at' => 'datetime'];
    }
}
