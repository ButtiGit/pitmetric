<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['session_id', 'values', 'is_reset', 'measured_at', 'notes', 'created_by'])]
class SessionWeatherObservation extends Model
{
    use BelongsToWorkspace;

    protected $attributes = [
        'is_reset' => false,
    ];

    /** @return BelongsTo<Session, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'values' => 'array',
            'is_reset' => 'boolean',
            'measured_at' => 'datetime',
        ];
    }
}
