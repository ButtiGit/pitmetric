<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['display_name', 'racing_number', 'licence_reference', 'status', 'notes'])]
class Driver extends Model
{
    use BelongsToWorkspace, SoftDeletes;

    protected $attributes = [
        'status' => 'active',
    ];

    /** @return HasMany<EventEntry, $this> */
    public function eventEntries(): HasMany
    {
        return $this->hasMany(EventEntry::class);
    }
}
