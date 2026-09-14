<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'name', 'storage_unit', 'display_unit', 'kind', 'precision', 'is_system'])]
class UsageMetricType extends Model
{
    /** @return HasMany<ComponentTracker, $this> */
    public function trackers(): HasMany
    {
        return $this->hasMany(ComponentTracker::class);
    }

    protected function casts(): array
    {
        return [
            'precision' => 'integer',
            'is_system' => 'boolean',
        ];
    }
}
