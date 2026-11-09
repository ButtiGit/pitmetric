<x-layouts::app title="Conversazione | PitMetric Mail">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 pb-32 sm:px-6 lg:px-8 lg:py-7 lg:pb-7">
        <div class="mx-auto w-full max-w-[1120px] space-y-5">
            @if (session('status'))<div role="status" class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif

            <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Navigazione email">
                <a href="{{ route('studio.mail.index') }}" class="pm-ghost-button"><flux:icon.arrow-left class="mr-1 inline size-4" />Indietro all'Inbox</a>
                <a href="{{ route('calendar.index') }}" class="pm-ghost-button"><flux:icon.calendar-days class="mr-1 inline size-4" />Calendario</a>
            </nav>

            @if ($mailboxError)<div role="status" class="rounded-xl border border-pm-danger/25 bg-pm-danger-subtle p-4 text-sm text-pm-danger">{{ $mailboxError }}<p class="mt-1">Lo storico già salvato resta disponibile.</p></div>@endif
            <section class="pm-panel p-5 sm:p-7">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs font-bold uppercase tracking-[0.14em] text-pm-accent">Studio / Conversazione</p>
                    <span class="rounded-lg border border-pm-border bg-pm-subtle px-3 py-1.5 text-xs font-bold text-pm-text-secondary">{{ $messages->count() }} messaggi</span>
                </div>
                <h1 class="mt-3 break-words text-2xl font-black leading-tight tracking-tight text-pm-text sm:text-3xl [overflow-wrap:anywhere]">{{ $selected->subject ?: '(senza oggetto)' }}</h1>
                <div class="mt-4 flex flex-col gap-3 border-t border-pm-border pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-xl text-xs leading-5 text-pm-muted">I messaggi ricevuti con testo disponibile sono segnati come letti quando apri la conversazione. Le nuove risposte rimangono da leggere.</p>
                    @if ($reply)
                        <form method="POST" action="{{ route('studio.mail.read', $reply->provider_id) }}" class="shrink-0">
                            @csrf @method('PATCH')
                            <input type="hidden" name="read" value="0">
                            <button type="submit" class="pm-ghost-button">Segna conversazione come da leggere</button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="space-y-4" aria-label="Messaggi della conversazione">
                @foreach ($messages as $item)
                    <article @class(['pm-panel min-w-0 overflow-hidden', 'border-pm-accent/35 sm:ml-8' => $item->direction === 'sent', 'sm:mr-8' => $item->direction === 'received']) data-mail-message="{{ $item->provider_id }}">
                        <header class="border-b border-pm-border bg-pm-subtle/40 px-4 py-4 sm:px-6 sm:py-5">
                            <div class="flex min-w-0 items-start gap-3">
                                <div @class(['grid size-10 shrink-0 place-items-center rounded-xl border text-sm font-black', 'border-pm-accent/30 bg-pm-accent/10 text-pm-accent' => $item->direction === 'sent', 'border-pm-success/30 bg-pm-success/10 text-pm-success' => $item->direction === 'received']) aria-hidden="true">{{ $item->direction === 'sent' ? 'TU' : \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($item->sender), 0, 1)) }}</div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex min-w-0 flex-wrap items-center justify-between gap-2">
                                        <span @class(['text-xs font-black uppercase tracking-[0.08em]', 'text-pm-accent' => $item->direction === 'sent', 'text-pm-success' => $item->direction === 'received'])>{{ $item->direction === 'sent' ? 'Tu · Inviata' : 'Ricevuta' }}</span>
                                        <time class="text-xs text-pm-muted" datetime="{{ $item->sent_at->toIso8601String() }}">{{ $item->sent_at->format('d/m/Y H:i') }}</time>
                                    </div>
                                    <p class="mt-1 break-words text-sm font-bold text-pm-text [overflow-wrap:anywhere]">{{ $item->sender }}</p>
                                    <p class="mt-1 break-words text-xs font-medium text-pm-text-secondary [overflow-wrap:anywhere]">{{ $item->subject ?: '(senza oggetto)' }}</p>
                                    @if ($item->direction === 'received')<span class="mt-2 inline-block rounded-md border border-pm-border px-2 py-1 text-[11px] font-bold text-pm-muted">{{ $item->body === null ? 'Da leggere · testo non disponibile' : ($item->is_read ? 'Letta' : 'Nuovo messaggio · ora letto') }}</span>@endif
                                </div>
                            </div>
                            <div class="mt-3 flex min-w-0 flex-wrap items-center justify-between gap-2 sm:pl-13">
                                <details class="min-w-0 text-xs text-pm-text-secondary">
                                    <summary class="cursor-pointer font-semibold text-pm-muted transition hover:text-pm-text">Destinatari e dettagli</summary>
                                    <div class="mt-2 space-y-1 break-words leading-5 [overflow-wrap:anywhere]">
                                        <p><strong>Da:</strong> {{ $item->sender }}</p>
                                        <p><strong>A:</strong> {{ implode(', ', $item->recipients) }}</p>
                                        @if ($item->cc)<p><strong>CC:</strong> {{ implode(', ', $item->cc) }}</p>@endif
                                    </div>
                                </details>
                                <a href="{{ route('calendar.index', ['mail' => $item->provider_id]) }}#create-calendar-entry" class="pm-ghost-button" aria-label="Crea evento dalla mail: {{ $item->subject }}"><flux:icon.calendar-days class="mr-1 inline size-4" />Crea evento</a>
                            </div>
                        </header>
                        <div class="min-w-0 px-4 py-5 sm:px-6 sm:py-6">
                            <div class="max-w-none whitespace-pre-wrap break-words text-[15px] leading-7 text-pm-text-secondary [overflow-wrap:anywhere]">{{ $item->body ?? 'Impossibile caricare il testo. Riprova aggiornando la conversazione.' }}</div>
                            @if ($item->attachments)
                                <div class="mt-6 border-t border-pm-border pt-4">
                                    <p class="mb-3 text-xs font-bold uppercase tracking-wider text-pm-muted">Allegati · {{ count($item->attachments) }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($item->attachments as $attachment)<span class="min-w-0 break-all rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 text-xs text-pm-text-secondary"><flux:icon.paper-clip class="mr-1 inline size-3.5" />{{ $attachment['filename'] ?? 'Allegato' }}</span>@endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>

            @if ($reply)
                <section class="pm-panel p-5 sm:p-7" id="rispondi">
                    <div class="flex flex-col gap-3 border-b border-pm-border pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-pm-accent">Risposta diretta</p>
                            <h2 class="mt-2 text-2xl font-black text-pm-text">Rispondi da PitMetric</h2>
                            <p class="mt-2 text-sm leading-6 text-pm-text-secondary">Le risposte mantengono lo stile ufficiale e restano collegate a questa conversazione.</p>
                        </div>
                        <p class="min-w-0 break-all text-xs text-pm-muted">A: <span class="font-semibold text-pm-text-secondary">{{ $replyAddress ?? 'indirizzo non disponibile' }}</span></p>
                    </div>

                    <form method="POST" action="{{ route('studio.mail.reply', $reply->provider_id) }}" class="mt-5 space-y-5">
                        @csrf
                        <div>
                            <label for="subject" class="text-sm font-bold text-pm-text">Oggetto</label>
                            @php $replySubject = str_starts_with(strtolower($reply->subject), 're:') ? $reply->subject : 'Re: '.$reply->subject; @endphp
                            <input id="subject" name="subject" required maxlength="180" value="{{ old('subject', $replySubject) }}" class="mt-2 w-full min-w-0 rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text outline-none transition focus:border-pm-accent">
                            @error('subject')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="message" class="text-sm font-bold text-pm-text">Messaggio</label>
                            <textarea id="message" name="message" rows="8" required maxlength="12000" class="mt-2 w-full min-w-0 rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm leading-7 text-pm-text outline-none transition focus:border-pm-accent" placeholder="Scrivi la tua risposta...">{{ old('message') }}</textarea>
                            @error('message')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-pm-border pt-5">
                            <p class="text-xs text-pm-muted">Verrà inviata una vera email al destinatario.</p>
                            <button type="submit" class="pm-race-button" @disabled($replyAddress === null)><flux:icon.paper-airplane class="mr-1 inline size-4" />Invia risposta</button>
                        </div>
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-layouts::app>
