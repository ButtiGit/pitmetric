<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'session_id',
    'technical_setup_id',
    'vehicle_id',
    'configuration_version_id',
    'name',
    'values',
    'captured_at',
    'created_by',
])]
class SetupSnapshot extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<Session, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /** @return BelongsTo<TechnicalSetup, $this> */
    public function technicalSetup(): BelongsTo
    {
        return $this->belongsTo(TechnicalSetup::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<ConfigurationVersion, $this> */
    public function configurationVersion(): BelongsTo
    {
        return $this->belongsTo(ConfigurationVersion::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Setup snapshots are immutable.');
        });

        static::deleting(function (): never {
            throw new LogicException('Setup snapshots are immutable.');
        });
    }

    protected function casts(): array
    {
        return [
            'values' => 'array',
            'captured_at' => 'datetime',
        ];
    }
}
