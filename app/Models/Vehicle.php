<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string $category
 * @property string|null $manufacturer
 * @property string|null $model
 * @property int|null $year
 * @property string|null $identifier
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'category', 'manufacturer', 'model', 'year', 'identifier', 'status', 'notes'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    /** @var list<string> */
    public const CATEGORIES = ['kart', 'car', 'motorcycle', 'prototype', 'other'];

    /** @var list<string> */
    public const STATUSES = ['active', 'inactive'];

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return HasMany<ComponentInstallation, $this> */
    public function componentInstallations(): HasMany
    {
        return $this->hasMany(ComponentInstallation::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
        ];
    }
}
