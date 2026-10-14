<?php

namespace App\Modules\ButtiDeskPro\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Ticket extends Model
{
    use BelongsToWorkspace;

    protected $table = 'desk_tickets';

    protected $fillable = ['workspace_id', 'client_id', 'supplier_id', 'created_by', 'title', 'description', 'category', 'status', 'priority', 'due_at', 'closed_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    /** @return BelongsTo<Contact, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'client_id');
    }

    /** @return BelongsTo<Contact, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    /** @return HasMany<Worklog, $this> */
    public function worklogs(): HasMany
    {
        return $this->hasMany(Worklog::class, 'ticket_id')->orderByDesc('performed_at');
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'ticket_id');
    }

    /** @return HasMany<MailLog, $this> */
    public function mailLogs(): HasMany
    {
        return $this->hasMany(MailLog::class, 'ticket_id')->latest();
    }
}
