<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'vehicle_id',
    'configuration_version_id',
    'circuit_layout_id',
    'session_type',
    'started_at',
    'completed_laps',
    'duration_seconds',
    'distance_override_meters',
    'status',
    'finalized_at',
    'created_by',
    'notes',
])]
class Session extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<ConfigurationVersion, $this> */
    public function configurationVersion(): BelongsTo
    {
        return $this->belongsTo(ConfigurationVersion::class);
    }

    /** @return BelongsTo<CircuitLayout, $this> */
    public function circuitLayout(): BelongsTo
    {
        return $this->belongsTo(CircuitLayout::class);
    }

    /** @return HasMany<SessionUsageValue, $this> */
    public function usageValues(): HasMany
    {
        return $this->hasMany(SessionUsageValue::class);
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_laps' => 'integer',
            'duration_seconds' => 'integer',
            'distance_override_meters' => 'integer',
            'finalized_at' => 'datetime',
        ];
    }
}
