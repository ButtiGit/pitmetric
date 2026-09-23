<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $workspace_id
 * @property string $client_id
 * @property string $operation
 * @property array<string, mixed>|null $payload
 * @property array<string, mixed>|null $result
 */
#[Fillable(['user_id', 'workspace_id', 'client_id', 'operation', 'payload', 'result', 'processed_at'])]
class MobileSyncOperation extends Model
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
        return [
            'payload' => 'array',
            'result' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
