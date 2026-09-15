<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class WorkspaceMembership extends Pivot
{
    public const ROLES = ['owner', 'manager', 'mechanic_engineer', 'driver', 'viewer'];

    public const INVITABLE_ROLES = ['manager', 'mechanic_engineer', 'driver', 'viewer'];

    public const WRITABLE_ROLES = ['owner', 'manager', 'mechanic_engineer'];

    public const MANAGER_ROLES = ['owner', 'manager'];

    public const STATUSES = ['active', 'suspended'];

    protected $table = 'workspace_user';

    public $incrementing = false;

    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
        'status',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }
}
