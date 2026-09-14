<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'component_type_id',
    'name',
    'manufacturer',
    'model',
    'serial_number',
    'purchase_date',
    'purchase_cost_cents',
    'currency',
    'status',
    'notes',
])]
class Component extends Model
{
    use BelongsToWorkspace, SoftDeletes;

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<ComponentType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ComponentType::class, 'component_type_id');
    }

    /** @return HasMany<ComponentTracker, $this> */
    public function trackers(): HasMany
    {
        return $this->hasMany(ComponentTracker::class);
    }

    /** @return HasMany<ComponentInstallation, $this> */
    public function installations(): HasMany
    {
        return $this->hasMany(ComponentInstallation::class);
    }

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'purchase_cost_cents' => 'integer',
        ];
    }
}
