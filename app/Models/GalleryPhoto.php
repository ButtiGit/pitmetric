<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'client_uuid',
    'title',
    'description',
    'path',
    'mime_type',
    'original_filename',
    'size_bytes',
    'taken_at',
])]
class GalleryPhoto extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'taken_at' => 'datetime',
        ];
    }
}
