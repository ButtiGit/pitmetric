<?php

namespace App\Http\Controllers;

use App\Models\FollowUpTask;
use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceContext->personal($user);

        return view('follow-ups.index', [
            'tasks' => FollowUpTask::query()
                ->where('status', 'open')
                ->latest('created_at')
                ->get(),
        ]);
    }

    public function complete(FollowUpTask $followUpTask): RedirectResponse
    {
        $followUpTask->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('status', __('Alert completed.'));
    }
}
