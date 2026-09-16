<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'maintenance_schedule_id',
    'assigned_to',
    'maintenance_record_id',
    'title',
    'priority',
    'status',
    'due_at',
    'started_at',
    'completed_at',
    'created_by',
    'notes',
])]
class MaintenanceWorkOrder extends Model
{
    use BelongsToWorkspace;

    public const PRIORITIES = ['low', 'normal', 'high', 'critical'];

    public const OPEN_STATUSES = ['todo', 'in_progress', 'blocked'];

    public const STATUSES = ['todo', 'in_progress', 'blocked', 'completed', 'cancelled'];

    protected $attributes = [
        'priority' => 'normal',
        'status' => 'todo',
    ];

    /** @return BelongsTo<MaintenanceSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<MaintenanceRecord, $this> */
    public function maintenanceRecord(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
