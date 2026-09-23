<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'kind',
    'circuit_name',
    'circuit_id',
    'circuit_layout_id',
    'lap_time_ms',
    'occurred_at',
    'status',
    'resolved_at',
    'created_by',
    'notes',
])]
class TrackCapture extends Model
{
    use BelongsToWorkspace;

    public function circuit(): BelongsTo
    {
        return $this->belongsTo(Circuit::class)->withTrashed();
    }

    public function circuitLayout(): BelongsTo
    {
        return $this->belongsTo(CircuitLayout::class)->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'lap_time_ms' => 'integer',
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
