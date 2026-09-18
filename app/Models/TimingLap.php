<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'session_id',
    'driver_id',
    'vehicle_id',
    'circuit_layout_id',
    'telemetry_import_id',
    'lap_number',
    'lap_time_ms',
    'sector_times_ms',
    'started_offset_ms',
    'ended_offset_ms',
    'is_valid',
    'source',
])]
class TimingLap extends Model
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
        return $this->belongsTo(Driver::class)->withTrashed();
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    /** @return BelongsTo<CircuitLayout, $this> */
    public function circuitLayout(): BelongsTo
    {
        return $this->belongsTo(CircuitLayout::class);
    }

    /** @return BelongsTo<TelemetryImport, $this> */
    public function telemetryImport(): BelongsTo
    {
        return $this->belongsTo(TelemetryImport::class);
    }

    protected function casts(): array
    {
        return [
            'lap_number' => 'integer',
            'lap_time_ms' => 'integer',
            'sector_times_ms' => 'array',
            'started_offset_ms' => 'integer',
            'ended_offset_ms' => 'integer',
            'is_valid' => 'boolean',
        ];
    }
}
