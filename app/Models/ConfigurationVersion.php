<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['configuration_id', 'version_number', 'created_by', 'notes', 'locked_at'])]
class ConfigurationVersion extends Model
{
    /** @return BelongsTo<Configuration, $this> */
    public function configuration(): BelongsTo
    {
        return $this->belongsTo(Configuration::class);
    }

    /** @return BelongsToMany<Component, $this> */
    public function components(): BelongsToMany
    {
        return $this->belongsToMany(Component::class, 'configuration_version_components')
            ->withPivot(['position_or_role', 'notes'])
            ->withTimestamps();
    }

    /** @return HasMany<Session, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'locked_at' => 'datetime',
        ];
    }
}
