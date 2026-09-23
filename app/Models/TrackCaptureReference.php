<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'track_capture_id',
    'kind',
    'raw_name',
    'normalized_name',
    'reference_id',
    'status',
    'resolved_at',
])]
class TrackCaptureReference extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<TrackCapture, $this> */
    public function capture(): BelongsTo
    {
        return $this->belongsTo(TrackCapture::class, 'track_capture_id');
    }

    protected function casts(): array
    {
        return [
            'reference_id' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }
}
