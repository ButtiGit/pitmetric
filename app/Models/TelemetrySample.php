<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'telemetry_import_id',
    'sequence',
    'lap_number',
    'elapsed_ms',
    'distance_meters',
    'latitude',
    'longitude',
    'speed_kmh',
    'channels',
])]
class TelemetrySample extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<TelemetryImport, $this> */
    public function telemetryImport(): BelongsTo
    {
        return $this->belongsTo(TelemetryImport::class);
    }

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'lap_number' => 'integer',
            'elapsed_ms' => 'integer',
            'distance_meters' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
            'speed_kmh' => 'float',
            'channels' => 'array',
        ];
    }
}
