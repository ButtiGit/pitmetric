<?php

namespace App\Services;

use App\Models\StudioMailMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

final class StudioMailConversations
{
    public function __construct(private ResendStudioMailbox $mailbox) {}

    /** @return array{cursors: array<string, string|null>, errors: list<string>} */
    public function sync(?string $direction = null, ?string $after = null, bool $refresh = false): array
    {
        $cursors = [];
        $errors = [];

        foreach ($direction === null ? ['received', 'sent'] : [$direction] as $kind) {
            try {
                $cacheKey = 'studio-mail:list:'.hash('sha256', (string) config('services.resend.studio_key').$kind.($after ?? 'latest'));
                if ($refresh) {
                    Cache::forget($cacheKey);
                }
                $page = Cache::remember($cacheKey, now()->addSeconds(30), fn (): array => $kind === 'sent' ? $this->mailbox->sent(100, $after) : $this->mailbox->received(100, $after));
                foreach ($page['data'] as $email) {
                    if ($kind === 'received' || $this->address((string) ($email['from'] ?? '')) === $this->address((string) config('services.resend.studio_from_address'))) {
                        $this->store($email, $kind);
                    }
                }
                $last = $page['data'] === [] ? null : $page['data'][array_key_last($page['data'])];
                $cursors[$kind] = ! empty($page['has_more']) && $last !== null ? (string) $last['id'] : null;
            } catch (Throwable $exception) {
                report($exception);
                $errors[] = $kind === 'sent' ? 'Impossibile aggiornare le email inviate da Resend. Lo storico già salvato resta disponibile.' : 'Impossibile aggiornare le email ricevute da Resend. Lo storico già salvato resta disponibile.';
            }
        }

        return ['cursors' => $cursors, 'errors' => $errors];
    }

    /** @param array<string, mixed> $email */
    public function store(array $email, string $direction): StudioMailMessage
    {
        $id = (string) ($email['id'] ?? '');
        if ($id === '') {
            throw new \RuntimeException('Missing provider email ID.');
        }
        $message = StudioMailMessage::query()->firstOrNew(['provider_id' => $id]);
        $headers = array_change_key_case(is_array($email['headers'] ?? null) ? $email['headers'] : [], CASE_LOWER);
        $messageId = trim((string) ($email['message_id'] ?? $headers['message-id'] ?? $message->message_id ?? ''));
        $sender = (string) ($email['from'] ?? $message->sender ?? '');
        $recipients = is_array($email['to'] ?? null) ? $email['to'] : ($message->recipients ?? []);
        $cc = is_array($email['cc'] ?? null) ? $email['cc'] : ($message->cc ?? []);
        $subject = (string) ($email['subject'] ?? $message->subject ?? '');
        $subjectKey = mb_strtolower(trim(preg_replace('/^(?:(?:re|r|aw|sv|fw|fwd|i)\s*:\s*)+/iu', '', trim($subject)) ?? $subject));
        $people = $direction === 'received' ? [$this->address($sender)] : array_map(fn (string $to): ?string => $this->address($to), $recipients);
        $people = array_values(array_filter($people));
        sort($people);
        $threadKey = $message->thread_key ?? hash('sha256', json_encode([$subjectKey !== '' ? $subjectKey : $id, $people], JSON_THROW_ON_ERROR));

        $message->fill([
            'direction' => $direction, 'thread_key' => $threadKey, 'sender' => $sender,
            'recipients' => $recipients, 'cc' => $cc, 'subject' => $subject,
            'message_id' => $messageId !== '' ? $messageId : null,
            'message_id_hash' => $messageId !== '' ? hash('sha256', trim($messageId, '<>')) : null,
            'sent_at' => $email['created_at'] ?? $message->sent_at ?? now(),
        ]);
        if (array_key_exists('text', $email) || array_key_exists('html', $email)) {
            $message->body = $this->plainBody($email);
        }
        if ($headers !== []) {
            $message->headers = $headers;
        }
        if (is_array($email['attachments'] ?? null)) {
            $message->attachments = array_map(fn (array $attachment): array => ['filename' => (string) ($attachment['filename'] ?? 'Allegato')], $email['attachments']);
        }
        $message->save();
        $this->linkReferences($message);

        return $message->refresh();
    }

    public function find(string $providerId, string $direction): StudioMailMessage
    {
        $message = StudioMailMessage::query()->where('provider_id', $providerId)->first();
        if ($message !== null && $message->body !== null) {
            return $message;
        }
        try {
            $email = $direction === 'sent' ? $this->mailbox->sentEmail($providerId) : $this->mailbox->receivedEmail($providerId);
        } catch (Throwable $exception) {
            if ($message === null) {
                throw $exception;
            }
            report($exception);

            return $message;
        }
        abort_unless(($email['id'] ?? null) === $providerId, 404);

        return $this->store($email, $direction);
    }

    /** @return Collection<int, StudioMailMessage> */
    public function messages(StudioMailMessage $selected, User $user): Collection
    {
        $messages = StudioMailMessage::query()->where('thread_key', $selected->thread_key)->orderBy('sent_at')->orderBy('id')->get();
        foreach ($messages as $message) {
            if ($message->body === null) {
                try {
                    $this->find($message->provider_id, $message->direction);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        }

        return StudioMailMessage::query()->where('thread_key', $selected->fresh()->thread_key)
            ->withExists(['readers as is_read' => fn (Builder $query) => $query->whereKey($user->id)])
            ->orderBy('sent_at')->orderBy('id')->get();
    }

    /** @param Collection<int, StudioMailMessage> $messages */
    public function markRead(Collection $messages, User $user, bool $read): void
    {
        $ids = $messages->where('direction', 'received')->modelKeys();
        if (! $read) {
            DB::table('studio_mail_reads')->where('user_id', $user->id)->whereIn('studio_mail_message_id', $ids)->delete();

            return;
        }
        $rows = array_map(fn ($id): array => ['studio_mail_message_id' => $id, 'user_id' => $user->id, 'read_at' => now()], $ids);
        if ($rows !== []) {
            DB::table('studio_mail_reads')->insertOrIgnore($rows);
        }
    }

    public function address(string $source): ?string
    {
        $address = preg_match('/<([^>]+)>/', $source, $matches) === 1 ? trim($matches[1]) : trim($source);

        return filter_var($address, FILTER_VALIDATE_EMAIL) !== false ? mb_strtolower($address) : null;
    }

    private function linkReferences(StudioMailMessage $message): void
    {
        $headers = $message->headers ?? [];
        $references = (string) ($headers['references'] ?? '').' '.(string) ($headers['in-reply-to'] ?? '');
        preg_match_all('/<([^>]+)>/', $references, $matches);
        $hashes = array_map(fn (string $id): string => hash('sha256', $id), $matches[1]);
        if ($hashes === []) {
            return;
        }
        $parent = StudioMailMessage::query()->whereIn('message_id_hash', $hashes)->whereKeyNot($message->id)->oldest('sent_at')->first();
        if ($parent !== null && $parent->thread_key !== $message->thread_key) {
            StudioMailMessage::query()->where('thread_key', $message->thread_key)->update(['thread_key' => $parent->thread_key]);
        }
    }

    /** @param array<string, mixed> $email */
    private function plainBody(array $email): string
    {
        $text = trim((string) ($email['text'] ?? ''));
        if ($text !== '') {
            return $text;
        }
        $html = (string) ($email['html'] ?? '');
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? '';
        $html = preg_replace('/<br\s*\/?>|<\/(?:p|div|tr|li)>/i', "\n", $html) ?? '';

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
