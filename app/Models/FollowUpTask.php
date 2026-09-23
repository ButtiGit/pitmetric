<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'kind',
    'subject_key',
    'title',
    'description',
    'target_route',
    'context',
    'status',
    'completed_at',
    'created_by',
])]
class FollowUpTask extends Model
{
    use BelongsToWorkspace;

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'completed_at' => 'datetime',
        ];
    }
}
