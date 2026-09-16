<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class ReadinessService
{
    /**
     * @return array{items: list<array{key:string,label:string,detail:string,complete:bool,required:bool}>, completed:int, required:int, percentage:int}
     */
    public function forWorkspace(Workspace $workspace): array
    {
        $workspaceId = (int) $workspace->getKey();
        $activeMembers = DB::table('workspace_user')
            ->where('workspace_id', $workspaceId)
            ->where('status', 'active')
            ->count();

        $checks = [
            [
                'key' => 'team',
                'label' => 'Team access',
                'detail' => 'At least two active team members can operate the workspace.',
                'complete' => $activeMembers >= 2,
                'required' => true,
            ],
            [
                'key' => 'vehicle',
                'label' => 'Vehicle',
                'detail' => 'At least one active vehicle is configured.',
                'complete' => DB::table('vehicles')->where('workspace_id', $workspaceId)->where('status', 'active')->exists(),
                'required' => true,
            ],
            [
                'key' => 'components',
                'label' => 'Component tracking',
                'detail' => 'The team has at least one active component.',
                'complete' => DB::table('components')->where('workspace_id', $workspaceId)->where('status', 'active')->exists(),
                'required' => true,
            ],
            [
                'key' => 'maintenance',
                'label' => 'Maintenance schedule',
                'detail' => 'At least one active maintenance interval is configured.',
                'complete' => DB::table('maintenance_schedules')->where('workspace_id', $workspaceId)->where('is_active', true)->exists(),
                'required' => true,
            ],
            [
                'key' => 'setup',
                'label' => 'Technical setup',
                'detail' => 'At least one reusable technical setup exists.',
                'complete' => DB::table('technical_setups')->where('workspace_id', $workspaceId)->where('status', 'active')->exists(),
                'required' => true,
            ],
            [
                'key' => 'event',
                'label' => 'Race weekend',
                'detail' => 'At least one race weekend has been created.',
                'complete' => DB::table('events')->where('workspace_id', $workspaceId)->exists(),
                'required' => true,
            ],
            [
                'key' => 'session',
                'label' => 'Recorded session',
                'detail' => 'At least one finalized track session proves the core workflow.',
                'complete' => DB::table('track_sessions')->where('workspace_id', $workspaceId)->where('status', 'finalized')->exists(),
                'required' => true,
            ],
            [
                'key' => 'document',
                'label' => 'Private document',
                'detail' => 'A private document has been uploaded and can be downloaded securely.',
                'complete' => DB::table('documents')->where('workspace_id', $workspaceId)->exists(),
                'required' => false,
            ],
            [
                'key' => 'backup',
                'label' => 'Portable backup',
                'detail' => 'A Data Hub backup has been exported at least once.',
                'complete' => DB::table('audit_logs')->where('workspace_id', $workspaceId)->where('action', 'data_exported')->exists(),
                'required' => false,
            ],
        ];

        $required = collect($checks)->where('required', true);
        $completed = $required->where('complete', true)->count();
        $requiredCount = $required->count();

        return [
            'items' => $checks,
            'completed' => $completed,
            'required' => $requiredCount,
            'percentage' => $requiredCount === 0 ? 100 : (int) round(($completed / $requiredCount) * 100),
        ];
    }
}
