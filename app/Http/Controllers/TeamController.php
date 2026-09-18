<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request, WorkspaceContext $workspaceContext): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        abort_unless($workspaceContext->isTeamReady(), 503, 'Team foundation database migration is not available yet.');

        $workspace = $workspaceContext->personal($user);
        Gate::authorize('team-view');

        $teams = $user->workspaces()
            ->wherePivot('status', 'active')
            ->orderBy('workspaces.name')
            ->get();

        $members = $workspace->users()
            ->orderBy('users.name')
            ->get();

        $invitations = $workspace->invitations()
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        return view('team.index', [
            'workspace' => $workspace,
            'teams' => $teams,
            'members' => $members,
            'invitations' => $invitations,
            'currentRole' => $workspaceContext->role($user),
            'canManage' => Gate::allows('team-manage'),
            'isOwner' => Gate::allows('team-own'),
            'inviteLinks' => $invitations->mapWithKeys(fn (TeamInvitation $invitation): array => [
                $invitation->getKey() => URL::temporarySignedRoute(
                    'team.invitations.accept',
                    $invitation->expires_at,
                    ['teamInvitation' => $invitation],
                ),
            ]),
        ]);
    }

    public function store(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $workspace = DB::transaction(function () use ($user, $validated, $workspaceContext): Workspace {
            $workspace = Workspace::create(['name' => trim($validated['name'])]);
            $user->workspaces()->attach($workspace, $workspaceContext->ownerPivot());

            return $workspace;
        });

        $workspaceContext->select($user, $workspace);

        return to_route('team.index')->with('status', __('Team created and selected.'));
    }

    public function update(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $workspace = $workspaceContext->personal($user);
        Gate::authorize('team-manage');
        $workspace->update($request->validate(['name' => ['required', 'string', 'max:120']]));

        return to_route('team.index')->with('status', __('Team updated.'));
    }

    public function switch(Request $request, Workspace $workspace, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceContext->select($user, $workspace);

        return back()->with('status', __('Team switched.'));
    }

    public function invite(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);
        Gate::authorize('team-manage');

        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'role' => ['required', Rule::in(WorkspaceMembership::INVITABLE_ROLES)],
        ]);

        $email = strtolower(trim($validated['email']));

        if ($workspace->users()->whereRaw('LOWER(users.email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages([
                'email' => __('This person is already a member of the current team.'),
            ]);
        }

        if ($workspace->invitations()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->exists()) {
            throw ValidationException::withMessages([
                'email' => __('A valid pending invitation already exists for this email.'),
            ]);
        }

        $invitation = TeamInvitation::create([
            'workspace_id' => $workspace->getKey(),
            'invited_by' => $user->getKey(),
            'email' => $email,
            'role' => $validated['role'],
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $inviteUrl = URL::temporarySignedRoute(
            'team.invitations.accept',
            $invitation->expires_at,
            ['teamInvitation' => $invitation],
        );

        return to_route('team.index')
            ->with('status', __('Invitation created.'))
            ->with('invite_url', $inviteUrl);
    }

    public function accept(
        Request $request,
        TeamInvitation $teamInvitation,
        WorkspaceContext $workspaceContext,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        if ($teamInvitation->status !== 'pending') {
            throw ValidationException::withMessages([
                'invitation' => __('This invitation is no longer pending.'),
            ]);
        }

        if ($teamInvitation->expires_at->isPast()) {
            $teamInvitation->update(['status' => 'expired']);

            throw ValidationException::withMessages([
                'invitation' => __('This invitation has expired.'),
            ]);
        }

        if (strtolower($user->email) !== strtolower($teamInvitation->email)) {
            abort(403, 'This invitation belongs to a different email address.');
        }

        DB::transaction(function () use ($user, $teamInvitation): void {
            DB::table('workspace_user')->updateOrInsert(
                [
                    'workspace_id' => $teamInvitation->workspace_id,
                    'user_id' => $user->getKey(),
                ],
                [
                    'role' => $teamInvitation->role,
                    'status' => 'active',
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $teamInvitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            if (! $user->hasDatabaseAccess()) {
                $user->forceFill(['database_access_enabled' => true])->save();
            }
        });

        $workspaceContext->select($user, $teamInvitation->workspace);

        return to_route('team.index')->with('status', __('Invitation accepted. Welcome to the team.'));
    }

    public function updateMember(
        Request $request,
        User $member,
        WorkspaceContext $workspaceContext,
    ): RedirectResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($actor);
        Gate::authorize('team-own');

        $membership = DB::table('workspace_user')
            ->where('workspace_id', $workspace->getKey())
            ->where('user_id', $member->getKey())
            ->first();

        abort_if($membership === null, 404);

        if ((int) $member->getKey() === (int) $actor->getKey() || $membership->role === 'owner') {
            throw ValidationException::withMessages([
                'member' => __('Owner memberships cannot be changed here.'),
            ]);
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(WorkspaceMembership::INVITABLE_ROLES)],
            'status' => ['required', Rule::in(WorkspaceMembership::STATUSES)],
        ]);

        DB::table('workspace_user')
            ->where('workspace_id', $workspace->getKey())
            ->where('user_id', $member->getKey())
            ->update([
                'role' => $validated['role'],
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

        return to_route('team.index')->with('status', __('Team member updated.'));
    }

    public function revokeInvitation(
        TeamInvitation $teamInvitation,
        WorkspaceContext $workspaceContext,
        Request $request,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspace = $workspaceContext->personal($user);
        Gate::authorize('team-manage');

        abort_unless((int) $teamInvitation->workspace_id === (int) $workspace->getKey(), 404);

        if ($teamInvitation->status === 'pending') {
            $teamInvitation->update(['status' => 'revoked']);
        }

        return to_route('team.index')->with('status', __('Invitation revoked.'));
    }
}
