<?php

namespace App\Modules\ButtiDeskPro\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\StudioReplyMail;
use App\Models\User;
use App\Modules\ButtiDeskPro\Models\MailLog;
use App\Modules\ButtiDeskPro\Models\Ticket;
use App\Services\ResendStudioMailbox;
use App\Services\WorkspaceContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

final class MailController extends Controller
{
    public function index(Request $request, ResendStudioMailbox $mailbox): View
    {
        $received = [];
        $error = null;

        try {
            $received = $mailbox->received(60)['data'];
        } catch (Throwable $exception) {
            report($exception);
            $error = 'Posta in arrivo non disponibile: controlla RESEND_STUDIO_KEY e il dominio di ricezione.';
        }

        return view('buttidesk.mail', [
            'received' => $received,
            'error' => $error,
            'sent' => MailLog::query()->latest()->limit(30)->get(),
            'tickets' => Ticket::query()->orderByDesc('id')->limit(100)->get(['id', 'title']),
            'prefillEmail' => (string) $request->query('to', ''),
            'prefillTicket' => $request->integer('ticket_id'),
        ]);
    }

    public function send(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceId = (int) $context->personal($user)->id;
        $data = $request->validate([
            'to' => ['required', 'email:rfc', 'max:254'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:12000'],
            'ticket_id' => ['nullable', 'integer', Rule::exists('desk_tickets', 'id')->where('workspace_id', $workspaceId)],
        ]);

        Mail::to($data['to'])->send(new StudioReplyMail($data['subject'], $data['message']));

        MailLog::query()->create([
            'workspace_id' => $workspaceId,
            'ticket_id' => $data['ticket_id'] ?? null,
            'sent_by' => $user->id,
            'recipient' => $data['to'],
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        return to_route('buttidesk.mail.index')->with('status', 'Email inviata usando la casella PitMetric.');
    }

    public function show(string $id, ResendStudioMailbox $mailbox): View
    {
        $email = $mailbox->receivedEmail($id);
        $body = trim((string) ($email['text'] ?? ''));

        if ($body === '') {
            $html = (string) ($email['html'] ?? '');
            $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
            $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
            $body = trim(html_entity_decode(strip_tags($html)));
        }

        return view('buttidesk.message', [
            'email' => $email,
            'body' => $body,
            'replyAddress' => $this->address((string) ($email['from'] ?? '')),
            'tickets' => Ticket::query()->orderByDesc('id')->limit(100)->get(['id', 'title']),
        ]);
    }

    public function reply(Request $request, string $id, ResendStudioMailbox $mailbox, WorkspaceContext $context): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $workspaceId = (int) $context->personal($user)->id;
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:12000'],
            'ticket_id' => ['nullable', 'integer', Rule::exists('desk_tickets', 'id')->where('workspace_id', $workspaceId)],
        ]);

        $original = $mailbox->receivedEmail($id);
        $to = $this->address((string) ($original['from'] ?? ''));

        if ($to === null) {
            return back()->withErrors(['message' => 'Mittente email non valido.']);
        }

        $messageId = trim((string) ($original['message_id'] ?? ''));
        $headers = $messageId !== '' ? ['In-Reply-To' => $messageId, 'References' => $messageId] : [];
        Mail::to($to)->send(new StudioReplyMail($data['subject'], $data['message'], $headers));

        MailLog::query()->create([
            'workspace_id' => $workspaceId,
            'ticket_id' => $data['ticket_id'] ?? null,
            'sent_by' => $user->id,
            'recipient' => $to,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'in_reply_to' => $messageId !== '' ? $messageId : null,
        ]);

        return back()->with('status', 'Risposta inviata e registrata.');
    }

    private function address(string $source): ?string
    {
        $value = preg_match('/<([^>]+)>/', $source, $match) === 1 ? trim($match[1]) : trim($source);

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? $value : null;
    }
}
