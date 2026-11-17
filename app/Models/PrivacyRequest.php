<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PrivacyRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $type
 * @property string $status
 * @property string $details
 * @property list<array{at: string, actor_id: int, status: string, message: string}> $history
 * @property CarbonImmutable $due_at
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable $created_at
 * @property-read User|null $user
 */
#[Fillable(['user_id', 'type', 'status', 'details', 'history', 'due_at', 'closed_at'])]
#[Hidden(['details', 'history'])]
final class PrivacyRequest extends Model
{
    /** @use HasFactory<PrivacyRequestFactory> */
    use HasFactory;

    public const TYPES = [
        'access' => 'Accesso ai dati',
        'portability' => 'Portabilità',
        'rectification' => 'Rettifica',
        'erasure' => 'Cancellazione',
        'restriction' => 'Limitazione',
        'objection' => 'Opposizione',
    ];

    public const STATUSES = [
        'received' => 'Ricevuta',
        'processing' => 'In lavorazione',
        'completed' => 'Riscontro fornito',
        'rejected' => 'Non accolta (motivata)',
    ];

    protected $attributes = ['status' => 'received'];

    protected function casts(): array
    {
        return [
            'details' => 'encrypted',
            'history' => 'encrypted:array',
            'due_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
