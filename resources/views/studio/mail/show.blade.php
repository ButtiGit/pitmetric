<x-layouts::app title="PitMetric Mail">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1080px] space-y-5">
            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('studio.mail.index') }}" class="pm-ghost-button">Indietro all'Inbox</a>
                <a href="{{ route('studio.outreach.index') }}" class="pm-ghost-button">Nuova pubblicità</a>
            </div>

            @if ($mailboxError)
                <div role="status" class="rounded-xl border border-pm-danger/25 bg-pm-danger-subtle p-4 text-sm text-pm-danger">{{ $mailboxError }}</div>
            @endif
            <section class="pm-panel space-y-4 p-6 sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-accent">Conversazione · {{ $messages->count() }} messaggi</p>
                <h1 class="break-words text-2xl font-black text-pm-text [overflow-wrap:anywhere]">{{ $selected->subject ?: '(senza oggetto)' }}</h1>
                <p class="text-sm text-pm-text-secondary">Le email ricevute mostrate qui sono segnate come lette per il tuo account. I nuovi messaggi resteranno da leggere.</p>
                @if ($reply)
                    <form method="POST" action="{{ route('studio.mail.read', $reply->provider_id) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="read" value="0">
                        <button type="submit" class="pm-ghost-button">Segna conversazione come da leggere</button>
                    </form>
                @endif
            </section>
            <div class="space-y-4" aria-label="Messaggi della conversazione">
                @foreach ($messages as $item)
                    <article @class(['pm-panel overflow-hidden', 'border-pm-accent/30 sm:ml-8' => $item->direction === 'sent', 'sm:mr-8' => $item->direction === 'received']) data-mail-message="{{ $item->provider_id }}">
                        <header class="space-y-3 border-b border-pm-border px-5 py-4 sm:px-6">
                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                                <span class="font-bold {{ $item->direction === 'sent' ? 'text-pm-accent' : 'text-pm-success' }}">{{ $item->direction === 'sent' ? 'Tu · Inviata' : 'Ricevuta' }}</span>
                                <span class="text-pm-muted">{{ $item->sent_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="break-words text-sm text-pm-text [overflow-wrap:anywhere]"><strong>Da:</strong> {{ $item->sender }}</p>
                            <p class="break-words text-xs text-pm-text-secondary [overflow-wrap:anywhere]"><strong>A:</strong> {{ implode(', ', $item->recipients) }}</p>
                            @if ($item->cc)
                                <p class="break-words text-xs text-pm-text-secondary [overflow-wrap:anywhere]"><strong>CC:</strong> {{ implode(', ', $item->cc) }}</p>
                            @endif
                            <p class="break-words text-sm font-semibold text-pm-text [overflow-wrap:anywhere]">{{ $item->subject ?: '(senza oggetto)' }}</p>
                            @if ($item->direction === 'received')
                                <span class="inline-block text-xs font-bold text-pm-muted">{{ $item->body === null ? 'Da leggere · testo non disponibile' : ($item->is_read ? 'Letta' : 'Nuovo messaggio · ora letto') }}</span>
                            @endif
                        </header>
                        <div class="px-5 py-5 sm:px-6">
                            <div class="whitespace-pre-wrap break-words text-[15px] leading-7 text-pm-text-secondary [overflow-wrap:anywhere]">{{ $item->body ?? 'Impossibile caricare il testo. Riprova aggiornando la conversazione.' }}</div>
                            @if ($item->attachments)
                                <div class="mt-5 flex flex-wrap gap-2 border-t border-pm-border pt-4">
                                    @foreach ($item->attachments as $attachment)
                                        <span class="break-all rounded-lg border border-pm-border px-3 py-2 text-xs text-pm-text-secondary">{{ $attachment['filename'] ?? 'Allegato' }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($reply)
            <section class="pm-panel p-6 sm:p-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-success">Risposta diretta</div>
                        <h2 class="mt-2 text-2xl font-black text-pm-text">Rispondi da PitMetric</h2>
                        <p class="mt-2 text-sm text-pm-text-secondary">La risposta sarà brandizzata e le successive risposte torneranno nell'Inbox Studio.</p>
                    </div>
                    <div class="text-xs text-pm-muted">A: <span class="font-semibold text-pm-text-secondary">{{ $replyAddress ?? 'indirizzo non disponibile' }}</span></div>
                </div>

                <form method="POST" action="{{ route('studio.mail.reply', $reply->provider_id) }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label for="subject" class="text-sm font-bold text-pm-text">Oggetto</label>
                        @php $replySubject = str_starts_with(strtolower($reply->subject), 're:') ? $reply->subject : 'Re: '.($reply->subject); @endphp
                        <input id="subject" name="subject" required maxlength="180" value="{{ old('subject', $replySubject) }}" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text outline-none transition focus:border-pm-accent">
                        @error('subject')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="message" class="text-sm font-bold text-pm-text">Messaggio</label>
                        <textarea id="message" name="message" rows="8" required maxlength="12000" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm leading-6 text-pm-text outline-none transition focus:border-pm-accent" placeholder="Scrivi la risposta...">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end border-t border-pm-border pt-5">
                        <button type="submit" class="pm-race-button" @disabled($replyAddress === null)>Invia risposta</button>
                    </div>
                </form>
            </section>
            @endif
        </div>
    </div>
</x-layouts::app>
