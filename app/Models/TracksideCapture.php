<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TracksideCapture extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'workspace_id',
        'created_by',
        'capture_type',
        'captured_at',
        'lap_time_ms',
        'circuit_name',
        'driver_name',
        'vehicle_name',
        'notes',
        'circuit_layout_id',
        'driver_id',
        'vehicle_id',
        'session_id',
        'status',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'lap_time_ms' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<CircuitLayout, $this> */
    public function circuitLayout(): BelongsTo
    {
        return $this->belongsTo(CircuitLayout::class)->withTrashed();
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

    /** @return BelongsTo<Session, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function needsCircuitCompletion(): bool
    {
        return filled($this->circuit_name) && $this->circuit_layout_id === null;
    }
}
