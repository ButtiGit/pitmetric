<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $workspace_id
 * @property string $client_id
 * @property string $title
 * @property string|null $description
 * @property string|null $path
 * @property Carbon|null $captured_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Workspace|null $workspace
 */
#[Fillable(['user_id', 'workspace_id', 'client_id', 'title', 'description', 'path', 'captured_at'])]
class GalleryAsset extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['captured_at' => 'datetime'];
    }
}
