<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['session_id', 'usage_metric_type_id', 'value', 'source'])]
class SessionUsageValue extends Model
{
    /** @return BelongsTo<Session, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /** @return BelongsTo<UsageMetricType, $this> */
    public function metric(): BelongsTo
    {
        return $this->belongsTo(UsageMetricType::class, 'usage_metric_type_id');
    }

    protected function casts(): array
    {
        return ['value' => 'integer'];
    }
}
