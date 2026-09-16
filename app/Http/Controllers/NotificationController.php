<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function markRead(Request $request, string $notification, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $this->user($request);
        $workspaceContext->personal($user);

        $item = $user->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();

        return back()->with('status', __('Notification marked as read.'));
    }

    public function markAllRead(Request $request, WorkspaceContext $workspaceContext): RedirectResponse
    {
        $user = $this->user($request);
        $workspaceContext->personal($user);
        $user->unreadNotifications()->get()->each(fn (DatabaseNotification $notification) => $notification->markAsRead());

        return back()->with('status', __('All notifications marked as read.'));
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }
}
