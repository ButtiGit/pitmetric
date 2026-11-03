<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'event_id',
    'component_id',
    'maintenance_schedule_id',
    'performed_at',
    'description',
    'cost_cents',
    'created_by',
    'notes',
])]
class MaintenanceRecord extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<MaintenanceWorkOrder, $this> */
    public function workOrders(): HasMany
    {
        return $this->hasMany(MaintenanceWorkOrder::class);
    }

    /** @return HasMany<TrackerResetEvent, $this> */
    public function resetEvents(): HasMany
    {
        return $this->hasMany(TrackerResetEvent::class);
    }

    /** @return BelongsTo<RaceEvent, $this> */
    public function raceEvent(): BelongsTo
    {
        return $this->belongsTo(RaceEvent::class, 'event_id')->withTrashed();
    }

    /** @return BelongsTo<Component, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class)->withTrashed();
    }

    /** @return BelongsTo<MaintenanceSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'cost_cents' => 'integer',
        ];
    }
}
