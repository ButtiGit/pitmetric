<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'session_id',
    'driver_id',
    'vehicle_id',
    'circuit_layout_id',
    'source_vendor',
    'source_format',
    'original_filename',
    'storage_path',
    'sha256',
    'channel_keys',
    'metadata',
    'sample_count',
    'lap_count',
    'duration_ms',
    'imported_by',
    'imported_at',
])]
class TelemetryImport extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<Session, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /** @return BelongsTo<Driver, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<CircuitLayout, $this> */
    public function circuitLayout(): BelongsTo
    {
        return $this->belongsTo(CircuitLayout::class);
    }

    /** @return HasMany<TimingLap, $this> */
    public function laps(): HasMany
    {
        return $this->hasMany(TimingLap::class);
    }

    /** @return HasMany<TelemetrySample, $this> */
    public function samples(): HasMany
    {
        return $this->hasMany(TelemetrySample::class);
    }

    protected function casts(): array
    {
        return [
            'channel_keys' => 'array',
            'metadata' => 'array',
            'sample_count' => 'integer',
            'lap_count' => 'integer',
            'duration_ms' => 'integer',
            'imported_at' => 'datetime',
        ];
    }
}
