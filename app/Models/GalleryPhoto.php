<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int|null $user_id
 * @property string $client_uuid
 * @property string $title
 * @property string|null $description
 * @property string $path
 * @property string $mime_type
 * @property string|null $original_filename
 * @property int $size_bytes
 * @property Carbon|null $taken_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
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
