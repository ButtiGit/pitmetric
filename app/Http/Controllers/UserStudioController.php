<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

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
            'accessControlReady' => Schema::hasColumn('users', 'database_access_enabled'),
        ]);
    }

    public function updateAccess(Request $request, User $user): RedirectResponse
    {
        if (! Schema::hasColumn('users', 'database_access_enabled')) {
            return back()->with('studio_setup_error', __('users.setup_required'));
        }

        if (Gate::forUser($user)->allows('manage-updates')) {
            abort(403, 'Editor database access cannot be disabled from the user studio.');
        }

        $validated = $request->validate([
            'database_access_enabled' => ['required', 'boolean'],
        ]);

        $enabled = (bool) $validated['database_access_enabled'];

        $user->forceFill([
            'database_access_enabled' => $enabled,
        ])->save();

        return back()->with('status', $enabled
            ? __('users.access_enabled_message', ['name' => $user->name])
            : __('users.access_disabled_message', ['name' => $user->name]));
    }
}
