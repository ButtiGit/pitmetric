<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id',
    'event_entry_id',
    'session_id',
    'label',
    'session_type',
    'starts_at',
    'duration_minutes',
    'status',
    'notes',
    'created_by',
])]
class EventScheduleItem extends Model
{
    use BelongsToWorkspace;

    protected $attributes = [
        'status' => 'planned',
    ];

    /** @return BelongsTo<RaceEvent, $this> */
    public function raceEvent(): BelongsTo
    {
        return $this->belongsTo(RaceEvent::class, 'event_id')->withTrashed();
    }

    /** @return BelongsTo<EventEntry, $this> */
    public function eventEntry(): BelongsTo
    {
        return $this->belongsTo(EventEntry::class);
    }

    /** @return BelongsTo<Session, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
        ];
    }
}
