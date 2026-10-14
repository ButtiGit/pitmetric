<?php

namespace App\Modules\ButtiDeskPro\Models;

use Illuminate\Database\Eloquent\Model;

final class CalendarFeed extends Model
{
    protected $table = 'desk_calendar_feeds';

    protected $fillable = ['workspace_id', 'user_id', 'token_hash'];
}
