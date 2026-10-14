<?php

namespace App\Modules\ButtiDeskPro\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Contact extends Model
{
    use BelongsToWorkspace;

    protected $table = 'desk_contacts';

    protected $fillable = ['workspace_id', 'type', 'name', 'company', 'email', 'phone', 'vat_number', 'notes'];

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'client_id');
    }
}
