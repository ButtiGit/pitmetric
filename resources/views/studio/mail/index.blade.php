<x-layouts::app title="Inbox | PitMetric">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 pb-32 sm:px-6 lg:px-8 lg:py-7 lg:pb-7">
        <div class="mx-auto w-full max-w-[1380px] space-y-5">
            <section class="pm-panel p-5 sm:p-7">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <p class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.15em] text-pm-accent"><span class="size-2 rounded-full bg-pm-accent"></span>Studio / Comunicazioni</p>
                        <h1 class="mt-2 text-3xl font-black tracking-tight text-pm-text sm:text-4xl">Inbox PitMetric</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-pm-text-secondary">Tutte le conversazioni in un unico posto: messaggi ricevuti, risposte inviate, stato di lettura e follow-up da pianificare.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('studio.mail.compose') }}" class="pm-race-button"><flux:icon.pencil-square class="mr-1 inline size-4" />Nuova mail</a>
                        <a href="{{ route('calendar.index') }}" class="pm-ghost-button"><flux:icon.calendar-days class="mr-1 inline size-4" />Calendario</a>
                        <a href="{{ route('studio.outreach.index') }}" class="pm-ghost-button">Outreach</a>
                    </div>
                </div>
                <div class="mt-6 grid gap-2 border-t border-pm-border pt-5 sm:grid-cols-3">
                    <div class="rounded-xl border border-pm-border bg-pm-subtle px-4 py-3"><p class="text-[11px] font-bold uppercase tracking-wider text-pm-muted">Conversazioni</p><p class="mt-1 text-2xl font-black text-pm-text">{{ $threads->total() }}</p><p class="text-xs text-pm-muted">{{ $search !== '' || $filter !== 'all' ? 'Con i filtri attuali' : 'Nell’archivio caricato' }}</p></div>
                    <div class="rounded-xl border border-pm-accent/20 bg-pm-accent/5 px-4 py-3"><p class="text-[11px] font-bold uppercase tracking-wider text-pm-muted">Da leggere</p><p class="mt-1 text-2xl font-black text-pm-accent">{{ $unreadCount }}</p><p class="text-xs text-pm-muted">Messaggi ricevuti non letti</p></div>
                    <div class="rounded-xl border border-pm-border bg-pm-subtle px-4 py-3"><p class="text-[11px] font-bold uppercase tracking-wider text-pm-muted">Gestione</p><p class="mt-2 text-sm font-bold text-pm-text">Follow-up dalle email</p><p class="mt-1 text-xs leading-5 text-pm-muted">Trasforma ogni messaggio in un evento, senza uscire dal flusso.</p></div>
                </div>
            </section>

            @if (session('status'))<div role="status" class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if ($mailboxError)<div role="status" class="rounded-xl border border-pm-danger/25 bg-pm-danger-subtle px-5 py-4 text-sm leading-6 text-pm-danger"><strong>Sincronizzazione email non disponibile</strong><p class="mt-1">{{ $mailboxError }}</p><p class="mt-2">Lo storico già salvato resta disponibile.</p></div>@endif

            <section class="pm-panel min-w-0 overflow-hidden" aria-label="Elenco conversazioni">
                <div class="grid gap-4 border-b border-pm-border px-4 py-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(240px,360px)] lg:items-center">
                    <nav class="flex flex-wrap items-center gap-2" aria-label="Filtra conversazioni">
                        <a href="{{ route('studio.mail.index', array_filter(['q' => $search])) }}" @class(['pm-ghost-button', 'border-pm-accent text-pm-accent' => $filter === 'all']) @if ($filter === 'all') aria-current="page" @endif>Tutte</a>
                        <a href="{{ route('studio.mail.index', array_filter(['filter' => 'unread', 'q' => $search])) }}" @class(['pm-ghost-button', 'border-pm-accent text-pm-accent' => $filter === 'unread']) @if ($filter === 'unread') aria-current="page" @endif>Da leggere · {{ $unreadCount }}</a>
                        <a href="{{ route('studio.mail.index', array_filter(['filter' => 'sent', 'q' => $search])) }}" @class(['pm-ghost-button', 'border-pm-accent text-pm-accent' => $filter === 'sent']) @if ($filter === 'sent') aria-current="page" @endif>Con inviate</a>
                    </nav>
                    <form method="GET" action="{{ route('studio.mail.index') }}" role="search" class="flex min-w-0 items-center gap-2">
                        @if ($filter !== 'all')<input type="hidden" name="filter" value="{{ $filter }}">@endif
                        <label for="mail-search" class="sr-only">Cerca per oggetto o contatto</label>
                        <input id="mail-search" name="q" type="search" maxlength="120" value="{{ $search }}" placeholder="Cerca oggetto o contatto..." class="min-w-0 flex-1 rounded-xl border border-pm-border bg-pm-subtle px-4 py-2.5 text-sm text-pm-text outline-none transition placeholder:text-pm-muted focus:border-pm-accent">
                        <button class="pm-ghost-button" type="submit" aria-label="Cerca email"><flux:icon.magnifying-glass class="size-4" /></button>
                    </form>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-pm-border bg-pm-subtle/50 px-4 py-3 text-xs text-pm-muted sm:px-6">
                    <span>Ordinate per ultima attività · {{ $threads->total() }} conversazioni</span>
                    <a href="{{ route('studio.mail.index', array_filter(['filter' => $filter === 'all' ? null : $filter, 'q' => $search, 'refresh' => 1])) }}" class="inline-flex items-center gap-1.5 font-semibold text-pm-text-secondary hover:text-pm-text"><flux:icon.arrow-path class="size-4" />Aggiorna posta</a>
                </div>

                <div class="divide-y divide-pm-border">
                    @forelse ($threads as $thread)
                        @php
                            $items = $threadMessages->get($thread->thread_key);
                            $last = $items->last();
                            $incoming = $items->where('direction', 'received');
                            $sentCount = $items->where('direction', 'sent')->count();
                            $unread = $incoming->where('is_read', false)->count();
                            $contact = $incoming->first()?->sender ?? implode(', ', $last->recipients);
                            $link = route('studio.mail.show', ['emailId' => $last->provider_id, 'direction' => $last->direction]);
                            $eventLink = route('calendar.index', ['mail' => $last->provider_id]).'#create-calendar-entry';
                            $preview = \Illuminate\Support\Str::limit(\Illuminate\Support\Str::squish($last->body ?? ''), 160);
                        @endphp
                        <article @class(['group flex min-w-0 flex-col gap-3 px-4 py-4 transition hover:bg-pm-subtle/60 sm:px-6 sm:py-5', 'bg-pm-accent/5' => $unread > 0]) data-mail-thread>
                            <div class="flex min-w-0 items-start gap-3 sm:gap-4">
                                <div @class(['grid size-10 shrink-0 place-items-center rounded-xl border text-sm font-black sm:size-12', 'border-pm-accent/30 bg-pm-accent/10 text-pm-accent' => $unread > 0, 'border-pm-border bg-pm-subtle text-pm-text-secondary' => $unread === 0]) aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($contact), 0, 1)) }}</div>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $link }}" aria-label="Apri conversazione: {{ $last->subject ?: '(senza oggetto)' }}" class="block min-w-0 rounded-lg focus-visible:outline-2 focus-visible:outline-pm-accent">
                                        <div class="flex min-w-0 flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                            <span @class(['min-w-0 break-words text-sm [overflow-wrap:anywhere]', 'font-black text-pm-text' => $unread > 0, 'font-semibold text-pm-text-secondary' => $unread === 0])>{{ $contact }}</span>
                                            <time datetime="{{ $last->sent_at->toIso8601String() }}" class="shrink-0 text-xs text-pm-muted">{{ $last->sent_at->format('d/m/Y H:i') }}</time>
                                        </div>
                                        <h2 class="mt-1 break-words text-base font-bold text-pm-text [overflow-wrap:anywhere]">{{ $last->subject ?: '(senza oggetto)' }}</h2>
                                        <p class="mt-1 break-words text-sm leading-6 text-pm-text-secondary [overflow-wrap:anywhere]">{{ $preview !== '' ? $preview : 'Apri per leggere tutti i messaggi della conversazione.' }}</p>
                                    </a>
                                    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 text-[11px] font-medium text-pm-muted">
                                        @if ($unread > 0)<span class="rounded-md border border-pm-accent/25 bg-pm-accent/10 px-2 py-1 font-bold text-pm-accent">{{ $unread }} da leggere</span>
                                        @else<span class="rounded-md border border-pm-border px-2 py-1 text-pm-text-secondary">{{ $incoming->isEmpty() ? 'Inviata' : 'Letta' }}</span>@endif
                                        <span>{{ $items->count() }} messaggi · {{ $sentCount }} {{ $sentCount === 1 ? 'inviato' : 'inviati' }}</span>
                                        <span>{{ $last->direction === 'sent' ? 'Ultima risposta: tu' : 'Ultimo messaggio: ricevuto' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-2 sm:pl-16">
                                <a href="{{ $eventLink }}" class="pm-ghost-button" aria-label="Crea evento dalla mail: {{ $last->subject }}"><flux:icon.calendar-days class="mr-1 inline size-4" />Crea evento</a>
                                @if ($incoming->isNotEmpty())
                                    <form method="POST" action="{{ route('studio.mail.read', $last->provider_id) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="read" value="{{ $unread > 0 ? '1' : '0' }}">
                                        <input type="hidden" name="through_id" value="{{ $items->max('id') }}">
                                        <input type="hidden" name="filter" value="{{ $filter }}">
                                        <input type="hidden" name="page" value="{{ $threads->currentPage() }}">
                                        <button type="submit" class="pm-ghost-button" aria-label="{{ $unread > 0 ? 'Segna come letta' : 'Segna come da leggere' }}: {{ $last->subject }}">{{ $unread > 0 ? 'Segna come letta' : 'Segna come da leggere' }}</button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-16 text-center">
                            <flux:icon.inbox class="mx-auto size-9 text-pm-muted" />
                            <h2 class="mt-3 text-lg font-black text-pm-text">{{ $search !== '' ? 'Nessun risultato' : ($filter === 'unread' ? 'Nessun messaggio da leggere' : 'Nessuna conversazione') }}</h2>
                            <p class="mt-2 text-sm text-pm-text-secondary">{{ $search !== '' ? 'Prova un altro oggetto o contatto.' : ($filter === 'unread' ? 'Hai letto tutte le email caricate.' : 'Le email ricevute e inviate compariranno qui.') }}</p>
                            @if ($search !== '')<a href="{{ route('studio.mail.index') }}" class="pm-ghost-button mt-4 inline-flex">Azzera ricerca</a>@endif
                        </div>
                    @endforelse
                </div>
                @if ($threads->hasPages())<div class="border-t border-pm-border px-5 py-4 sm:px-6">{{ $threads->links() }}</div>@endif
            </section>

            @if (collect($cursors)->filter()->isNotEmpty())
                <section class="pm-panel space-y-3 p-5">
                    <p class="text-sm leading-6 text-pm-text-secondary">Altre email sono disponibili nell’archivio Resend. Caricale per completare le conversazioni precedenti.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($cursors as $direction => $cursor)
                            @if ($cursor)<form method="POST" action="{{ route('studio.mail.older') }}">@csrf<input type="hidden" name="direction" value="{{ $direction }}"><button class="pm-ghost-button" type="submit">Carica {{ $direction === 'sent' ? 'inviate' : 'ricevute' }} precedenti</button></form>@endif
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-layouts::app>
