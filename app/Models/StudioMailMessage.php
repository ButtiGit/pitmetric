<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class StudioMailMessage extends Model
{
    protected $fillable = [
        'provider_id', 'direction', 'thread_key', 'message_id', 'message_id_hash',
        'sender', 'recipients', 'cc', 'subject', 'body', 'headers', 'attachments', 'sent_at',
    ];

    protected $casts = [
        'recipients' => 'array',
        'cc' => 'array',
        'headers' => 'array',
        'attachments' => 'array',
        'sent_at' => 'datetime',
    ];

    /** @return BelongsToMany<User, $this> */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'studio_mail_reads')->withPivot('read_at');
    }
}
