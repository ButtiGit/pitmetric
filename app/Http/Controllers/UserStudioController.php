<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserStudioController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        $users = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->latest('created_at')
            ->paginate(24)
            ->withQueryString();

        return view('studio.users.index', [
            'users' => $users,
            'search' => $search,
            'accessControlReady' => true,
        ]);
    }

    public function updateAccess(Request $request, User $user, WorkspaceContext $workspaceContext): RedirectResponse
    {
        if (Gate::forUser($user)->allows('manage-updates')) {
            abort(403, 'Editor database access cannot be disabled from the user studio.');
        }

        $validated = $request->validate([
            'database_access_enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $validated['database_access_enabled'];

        DB::transaction(function () use ($user, $workspaceContext, $enabled): void {
            $user->forceFill([
                'database_access_enabled' => $enabled,
            ])->save();

            if ($enabled) {
                $workspaceContext->personal($user);
            }
        });

        return back()->with('status', $enabled
            ? __('users.access_enabled_message', ['name' => $user->name])
            : __('users.access_disabled_message', ['name' => $user->name]));
    }
}
