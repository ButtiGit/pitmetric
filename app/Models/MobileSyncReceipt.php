<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'client_uuid', 'operation_type', 'resource_type', 'resource_id'])]
class MobileSyncReceipt extends Model
{
    use BelongsToWorkspace;
}
