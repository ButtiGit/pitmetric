<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'event_id',
    'event_entry_id',
    'title',
    'description',
    'priority',
    'status',
    'due_at',
    'completed_at',
    'created_by',
])]
class EventTask extends Model
{
    use BelongsToWorkspace, SoftDeletes;

    protected $attributes = [
        'priority' => 'normal',
        'status' => 'todo',
    ];

    /** @return BelongsTo<RaceEvent, $this> */
    public function raceEvent(): BelongsTo
    {
        return $this->belongsTo(RaceEvent::class, 'event_id');
    }

    /** @return BelongsTo<EventEntry, $this> */
    public function eventEntry(): BelongsTo
    {
        return $this->belongsTo(EventEntry::class);
    }

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
