<?php

namespace App\Modules\ButtiDeskPro\Models;

use App\Models\Concerns\BelongsToWorkspace;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Worklog extends Model
{
    use BelongsToWorkspace;

    protected $table = 'desk_worklogs';

    protected $fillable = ['workspace_id', 'ticket_id', 'user_id', 'summary', 'details', 'duration_minutes', 'performed_at'];

    protected function casts(): array
    {
        return ['performed_at' => 'datetime', 'duration_minutes' => 'integer'];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
