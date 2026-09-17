<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'circuit_layout_id',
    'name',
    'start_date',
    'end_date',
    'status',
    'championship',
    'round_label',
    'notes',
    'created_by',
])]
class RaceEvent extends Model
{
    use BelongsToWorkspace, SoftDeletes;

    protected $table = 'events';

    protected $attributes = [
        'status' => 'planned',
    ];

    /** @return BelongsTo<CircuitLayout, $this> */
    public function circuitLayout(): BelongsTo
    {
        return $this->belongsTo(CircuitLayout::class);
    }

    /** @return HasMany<EventEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(EventEntry::class, 'event_id')->where('status', 'active');
    }

    /** @return HasMany<Session, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'event_id');
    }

    /** @return HasMany<EventScheduleItem, $this> */
    public function scheduleItems(): HasMany
    {
        return $this->hasMany(EventScheduleItem::class, 'event_id');
    }

    /** @return HasMany<EventTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(EventTask::class, 'event_id');
    }

    /** @return HasMany<EventNote, $this> */
    public function eventNotes(): HasMany
    {
        return $this->hasMany(EventNote::class, 'event_id');
    }

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'event_id');
    }

    /** @return HasMany<MaintenanceRecord, $this> */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'event_id');
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
