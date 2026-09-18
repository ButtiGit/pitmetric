<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'event_id',
    'driver_id',
    'vehicle_id',
    'configuration_version_id',
    'entry_number',
    'notes',
])]
class EventEntry extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<RaceEvent, $this> */
    public function raceEvent(): BelongsTo
    {
        return $this->belongsTo(RaceEvent::class, 'event_id')->withTrashed();
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

    /** @return BelongsTo<ConfigurationVersion, $this> */
    public function configurationVersion(): BelongsTo
    {
        return $this->belongsTo(ConfigurationVersion::class);
    }

    /** @return HasMany<Session, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'event_entry_id');
    }

    /** @return HasMany<EventScheduleItem, $this> */
    public function scheduleItems(): HasMany
    {
        return $this->hasMany(EventScheduleItem::class);
    }

    /** @return HasMany<EventTask, $this> */
    public function tasks(): HasMany
    {
        return $this->hasMany(EventTask::class);
    }

    /** @return HasMany<EventNote, $this> */
    public function eventNotes(): HasMany
    {
        return $this->hasMany(EventNote::class);
    }
}
