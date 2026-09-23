<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kind',
    'circuit_name',
    'circuit_id',
    'circuit_layout_id',
    'lap_time_ms',
    'payload',
    'occurred_at',
    'status',
    'resolved_at',
    'created_by',
    'notes',
])]
class TrackCapture extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<Circuit, $this> */
    public function circuit(): BelongsTo
    {
        return $this->belongsTo(Circuit::class)->withTrashed();
    }

    /** @return BelongsTo<CircuitLayout, $this> */
    public function circuitLayout(): BelongsTo
    {
        return $this->belongsTo(CircuitLayout::class)->withTrashed();
    }

    /** @return HasMany<TrackCaptureReference, $this> */
    public function references(): HasMany
    {
        return $this->hasMany(TrackCaptureReference::class);
    }

    protected function casts(): array
    {
        return [
            'lap_time_ms' => 'integer',
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function formattedLapTime(): ?string
    {
        if ($this->lap_time_ms === null) {
            return null;
        }

        $minutes = intdiv($this->lap_time_ms, 60000);
        $seconds = intdiv($this->lap_time_ms % 60000, 1000);
        $milliseconds = $this->lap_time_ms % 1000;

        return sprintf('%d:%02d.%03d', $minutes, $seconds, $milliseconds);
    }
}
