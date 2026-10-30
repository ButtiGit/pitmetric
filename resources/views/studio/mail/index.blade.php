<x-layouts::app title="PitMetric Mail">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1180px] space-y-5">
            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
            @endif

            <section class="pm-panel p-6 sm:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-pm-success">
                            <span class="size-2 rounded-full bg-pm-success"></span>
                            Studio / Mail
                        </div>
                        <h1 class="mt-3 text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">Inbox PitMetric</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-pm-text-secondary">Messaggi ricevuti e risposte inviate, riuniti in conversazioni. Apri una conversazione per leggere lo scambio completo e rispondere. Lo stato di lettura è personale.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('studio.mail.compose') }}" class="pm-race-button">Nuova mail</a>
                        <a href="{{ route('studio.outreach.index') }}" class="pm-ghost-button">Nuova pubblicità</a>
                        <a href="{{ route('studio.updates.index') }}" class="pm-ghost-button">Pubblicazione</a>
                    </div>
                </div>
            </section>

            @if ($mailboxError)
                <section class="rounded-xl border border-pm-danger/25 bg-pm-danger-subtle px-5 py-4 text-sm leading-6 text-pm-danger">
                    <div class="font-bold">Inbox non disponibile</div>
                    <div class="mt-1">{{ $mailboxError }}</div>
                </section>
            @endif

            <section class="pm-panel overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-pm-border px-5 py-4 sm:px-6">
                    <div class="flex flex-wrap gap-2" aria-label="Filtra conversazioni">
                        <a href="{{ route('studio.mail.index') }}" @class(['pm-ghost-button', 'border-pm-accent text-pm-accent' => $filter !== 'unread']) @if ($filter !== 'unread') aria-current="page" @endif>Tutte</a>
                        <a href="{{ route('studio.mail.index', ['filter' => 'unread']) }}" @class(['pm-ghost-button', 'border-pm-accent text-pm-accent' => $filter === 'unread']) @if ($filter === 'unread') aria-current="page" @endif>Da leggere · {{ $unreadCount }}</a>
                    </div>
                    <a href="{{ route('studio.mail.index', ['filter' => $filter, 'refresh' => 1]) }}" class="pm-ghost-button">Aggiorna posta</a>
                </div>
                <div class="divide-y divide-pm-border">
                    @forelse ($threads as $thread)
                        @php
                            $items = $threadMessages->get($thread->thread_key);
                            $last = $items->last();
                            $incoming = $items->where('direction', 'received');
                            $unread = $incoming->where('is_read', false)->count();
                            $contact = $incoming->first()?->sender ?? implode(', ', $last->recipients);
                            $link = route('studio.mail.show', ['emailId' => $last->provider_id, 'direction' => $last->direction]);
                        @endphp
                        <div @class(['flex flex-col gap-3 px-5 py-5 sm:px-6', 'bg-pm-accent/5' => $unread > 0]) data-mail-thread>
                            <a href="{{ $link }}" class="min-w-0 space-y-2 rounded-lg focus-visible:outline-2 focus-visible:outline-pm-accent">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="min-w-0 break-words text-sm font-bold text-pm-text [overflow-wrap:anywhere]">{{ $contact }}</span>
                                    <span class="text-xs text-pm-muted">{{ $last->sent_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="break-words text-base font-semibold text-pm-text [overflow-wrap:anywhere]">{{ $last->subject ?: '(senza oggetto)' }}</div>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-pm-text-secondary">
                                    @if ($unread > 0)
                                        <span class="rounded-full border border-pm-accent/30 bg-pm-accent/10 px-2.5 py-1 font-bold text-pm-accent">{{ $unread }} da leggere</span>
                                    @else
                                        <span>{{ $incoming->isEmpty() ? 'Inviata' : 'Letta' }}</span>
                                    @endif
                                    <span>{{ $items->count() }} messaggi · {{ $items->where('direction', 'sent')->count() }} {{ $items->where('direction', 'sent')->count() === 1 ? 'inviato' : 'inviati' }}</span>
                                    <span>{{ $last->direction === 'sent' ? 'Ultimo messaggio: tu' : 'Ultimo messaggio ricevuto' }}</span>
                                </div>
                            </a>
                            @if ($incoming->isNotEmpty())
                                <form method="POST" action="{{ route('studio.mail.read', $last->provider_id) }}" class="self-start">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="read" value="{{ $unread > 0 ? '1' : '0' }}">
                                    <input type="hidden" name="through_id" value="{{ $items->max('id') }}">
                                    <input type="hidden" name="filter" value="{{ $filter }}">
                                    <input type="hidden" name="page" value="{{ $threads->currentPage() }}">
                                    <button type="submit" class="text-xs font-semibold text-pm-text-secondary underline underline-offset-4 hover:text-pm-text" aria-label="{{ $unread > 0 ? 'Segna come letta' : 'Segna come da leggere' }}: {{ $last->subject }}">{{ $unread > 0 ? 'Segna come letta' : 'Segna come da leggere' }}</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="px-6 py-16 text-center">
                            <div class="text-lg font-black text-pm-text">{{ $filter === 'unread' ? 'Nessun messaggio da leggere' : 'Nessuna conversazione' }}</div>
                            <p class="mt-2 text-sm text-pm-text-secondary">{{ $filter === 'unread' ? 'Hai letto tutte le email caricate.' : 'Le email ricevute e inviate compariranno qui.' }}</p>
                        </div>
                    @endforelse
                </div>
                <div class="border-t border-pm-border px-5 py-4 sm:px-6">{{ $threads->links() }}</div>
            </section>
            @if (collect($cursors)->filter()->isNotEmpty())
                <section class="pm-panel space-y-3 p-5">
                    <p class="text-sm text-pm-text-secondary">Sono disponibili altre email nell’archivio Resend. Caricale per completare le conversazioni più vecchie.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($cursors as $direction => $cursor)
                            @if ($cursor)
                                <form method="POST" action="{{ route('studio.mail.older') }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="{{ $direction }}">
                                    <button class="pm-ghost-button" type="submit">Carica {{ $direction === 'sent' ? 'inviate' : 'ricevute' }} precedenti</button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-layouts::app>
