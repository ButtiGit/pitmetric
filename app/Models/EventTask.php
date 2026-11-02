<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    'assigned_to',
    'completed_by',
])]
class EventTask extends Model
{
    use BelongsToWorkspace;

    protected $attributes = [
        'priority' => 'normal',
        'status' => 'todo',
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

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<User, $this> */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'assigned_to' => 'integer',
            'completed_by' => 'integer',
        ];
    }
}
