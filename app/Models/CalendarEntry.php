<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CalendarEntry extends Model
{
    protected $fillable = [
        'user_id', 'studio_mail_message_id', 'title', 'description', 'location',
        'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<StudioMailMessage, $this> */
    public function sourceMail(): BelongsTo
    {
        return $this->belongsTo(StudioMailMessage::class, 'studio_mail_message_id');
    }
}
