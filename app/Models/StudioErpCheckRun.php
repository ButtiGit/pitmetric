<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StudioErpCheckRun extends Model
{
    protected $fillable = ['user_id', 'status', 'passed', 'warnings', 'failed', 'results'];

    protected function casts(): array
    {
        return ['results' => 'array', 'passed' => 'integer', 'warnings' => 'integer', 'failed' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
