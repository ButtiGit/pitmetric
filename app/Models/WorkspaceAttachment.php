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
 * @property int $uploaded_by
 * @property string $attachable_type
 * @property int $attachable_id
 * @property string|null $label
 * @property string $original_name
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property Carbon|null $created_at
 */
#[Fillable(['uploaded_by', 'attachable_type', 'attachable_id', 'label', 'original_name', 'disk', 'path', 'mime_type', 'size_bytes'])]
class WorkspaceAttachment extends Model
{
    use BelongsToWorkspace;

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
