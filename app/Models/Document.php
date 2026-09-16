<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uploaded_by',
    'attachable_type',
    'attachable_id',
    'name',
    'original_name',
    'disk',
    'path',
    'mime_type',
    'size_bytes',
    'notes',
])]
class Document extends Model
{
    use BelongsToWorkspace;

    protected function casts(): array
    {
        return [
            'attachable_id' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
