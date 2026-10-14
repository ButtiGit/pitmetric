<?php

namespace App\Modules\ButtiDeskPro\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

final class MailLog extends Model
{
    use BelongsToWorkspace;

    protected $table = 'desk_mail_logs';

    protected $fillable = ['workspace_id', 'ticket_id', 'sent_by', 'recipient', 'subject', 'message', 'in_reply_to'];
}
