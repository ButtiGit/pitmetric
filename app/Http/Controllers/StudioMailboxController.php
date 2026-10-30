<?php

namespace App\Http\Controllers;

use App\Mail\StudioReplyMail;
use App\Models\StudioMailMessage;
use App\Models\User;
use App\Services\ResendStudioMailbox;
use App\Services\StudioMailConversations;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

final class StudioMailboxController extends Controller
{
    public function index(Request $request, StudioMailConversations $conversations): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $sync = $conversations->sync(refresh: $request->boolean('refresh'));
        foreach ($sync['cursors'] as $direction => $cursor) {
            if (! array_key_exists($direction, $request->session()->get('studio_mail_cursors', []))) {
                $request->session()->put('studio_mail_cursors.'.$direction, $cursor);
            }
        }
        $unread = StudioMailMessage::query()->where('direction', 'received')
            ->whereDoesntHave('readers', fn (Builder $query) => $query->whereKey($user->id));
        $threads = StudioMailMessage::query()->select('thread_key')->selectRaw('MAX(sent_at) as last_at')
            ->when($request->query('filter') === 'unread', fn (Builder $query) => $query->whereIn('thread_key', (clone $unread)->select('thread_key')))
            ->groupBy('thread_key')->orderByDesc('last_at')->orderBy('thread_key')->paginate(30)->withQueryString();
        $messages = StudioMailMessage::query()->whereIn('thread_key', $threads->pluck('thread_key'))
            ->select(['id', 'provider_id', 'direction', 'thread_key', 'sender', 'recipients', 'subject', 'sent_at'])
            ->withExists(['readers as is_read' => fn (Builder $query) => $query->whereKey($user->id)])
            ->orderBy('sent_at')->orderBy('id')->get()->groupBy('thread_key');

        return view('studio.mail.index', [
            'threads' => $threads, 'threadMessages' => $messages,
            'unreadCount' => $unread->count(), 'filter' => $request->query('filter', 'all'),
            'cursors' => $request->session()->get('studio_mail_cursors', []),
            'mailboxError' => implode(' ', $sync['errors']),
        ]);
    }

    public function older(Request $request, StudioMailConversations $conversations): RedirectResponse
    {
        $data = $request->validate(['direction' => ['required', 'in:received,sent']]);
        $direction = $data['direction'];
        $cursor = $request->session()->get('studio_mail_cursors.'.$direction);
        if (is_string($cursor) && $cursor !== '') {
            $sync = $conversations->sync($direction, $cursor);
            if (array_key_exists($direction, $sync['cursors'])) {
                $request->session()->put('studio_mail_cursors.'.$direction, $sync['cursors'][$direction]);
            }

            return to_route('studio.mail.index')->with('status', $sync['errors'] === [] ? 'Messaggi precedenti caricati.' : implode(' ', $sync['errors']));
        }

        return to_route('studio.mail.index');
    }

    public function read(Request $request, string $emailId, StudioMailConversations $conversations): RedirectResponse
    {
        $data = $request->validate([
            'read' => ['required', 'boolean'],
            'through_id' => ['required_if:read,1', 'nullable', 'integer', 'min:1'],
        ]);
        $selected = StudioMailMessage::query()->where('provider_id', $emailId)->firstOrFail();
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $messages = StudioMailMessage::query()->where('thread_key', $selected->thread_key)
            ->when((bool) $data['read'], fn (Builder $query) => $query->where('id', '<=', $data['through_id']))
            ->get(['id', 'direction']);
        $conversations->markRead($messages, $user, (bool) $data['read']);

        return to_route('studio.mail.index', $request->only('filter', 'page'))->with('status', $data['read'] ? 'Conversazione segnata come letta.' : 'Conversazione segnata come da leggere.');
    }

    public function compose(): View
    {
        return view('studio.mail.compose');
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', 'email:rfc', 'max:254'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:12000'],
        ]);

        Mail::to((string) $data['to'])->send(new StudioReplyMail(
            (string) $data['subject'],
            (string) $data['message'],
        ));

        return redirect()->route('studio.mail.index')->with('status', 'Email inviata a '.$data['to'].'.');
    }

    public function show(Request $request, string $emailId, StudioMailConversations $conversations): View
    {
        $request->validate(['direction' => ['sometimes', 'in:received,sent']]);
        $selected = $conversations->find($emailId, (string) $request->query('direction', 'received'));
        $sync = $conversations->sync();
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $messages = $conversations->messages($selected->refresh(), $user);
        $conversations->markRead($messages->filter(fn (StudioMailMessage $message): bool => $message->body !== null), $user, true);
        $reply = $messages->where('direction', 'received')->last();

        return view('studio.mail.show', [
            'selected' => $selected, 'messages' => $messages, 'reply' => $reply,
            'replyAddress' => $reply !== null ? $conversations->address($reply->sender) : null,
            'mailboxError' => implode(' ', $sync['errors']),
        ]);
    }

    public function reply(Request $request, string $emailId, ResendStudioMailbox $mailbox): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:12000'],
        ]);

        $original = $mailbox->receivedEmail($emailId);
        $to = $this->extractAddress((string) ($original['from'] ?? ''));

        if ($to === null) {
            return back()->withErrors(['message' => 'Non riesco a determinare l’indirizzo del mittente.']);
        }

        $headers = [];
        $messageId = trim((string) ($original['message_id'] ?? ''));

        if ($messageId !== '') {
            $headers['In-Reply-To'] = $messageId;
            $originalHeaders = array_change_key_case(is_array($original['headers'] ?? null) ? $original['headers'] : [], CASE_LOWER);
            $references = trim((string) ($originalHeaders['references'] ?? ''));
            $headers['References'] = trim($references.' '.$messageId);
        }

        Mail::to($to)->send(new StudioReplyMail(
            (string) $data['subject'],
            (string) $data['message'],
            $headers,
        ));

        return back()->with('status', 'Risposta inviata a '.$to.'.');
    }

    private function extractAddress(string $from): ?string
    {
        if (preg_match('/<([^>]+)>/', $from, $matches) === 1) {
            $candidate = trim($matches[1]);

            return filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false ? $candidate : null;
        }

        $candidate = trim($from);

        return filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false ? $candidate : null;
    }
}
