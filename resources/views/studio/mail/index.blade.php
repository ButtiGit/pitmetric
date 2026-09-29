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
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-pm-text-secondary">Tutte le email ricevute tramite <span class="font-mono text-pm-text">@reply.pitmetric.it</span>, lette direttamente da Resend. Apri un messaggio per leggerlo e rispondere senza uscire da PitMetric.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('studio.outreach.index') }}" class="pm-race-button">Nuova pubblicità</a>
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
                <div class="flex items-center justify-between gap-4 border-b border-pm-border px-5 py-4 sm:px-6">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-muted">Ricevute</div>
                        <div class="mt-1 text-sm text-pm-text-secondary">{{ count($emails) }} messaggi caricati</div>
                    </div>
                    <div class="rounded-full border border-pm-border bg-pm-subtle px-3 py-1.5 font-mono text-[11px] text-pm-muted">reply.pitmetric.it</div>
                </div>

                <div class="divide-y divide-pm-border">
                    @forelse ($emails as $email)
                        @php
                            $emailId = (string) ($email['id'] ?? '');
                            $from = (string) ($email['from'] ?? 'Mittente sconosciuto');
                            $subject = trim((string) ($email['subject'] ?? '')) ?: '(senza oggetto)';
                            $createdAt = (string) ($email['created_at'] ?? '');
                        @endphp
                        @if ($emailId !== '')
                            <a href="{{ route('studio.mail.show', $emailId) }}" class="group grid gap-3 px-5 py-5 transition hover:bg-white/[0.025] sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-6">
                                <div class="min-w-0">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="grid size-9 shrink-0 place-items-center rounded-full border border-pm-border bg-pm-subtle text-sm font-black text-pm-accent">{{ strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $from) ?: 'M', 0, 1)) }}</div>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-bold text-pm-text">{{ $from }}</div>
                                            <div class="mt-1 truncate text-sm text-pm-text-secondary">{{ $subject }}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 pl-12 sm:pl-0">
                                    <span class="text-xs text-pm-muted">{{ $createdAt !== '' ? \Illuminate\Support\Carbon::parse($createdAt)->format('d/m/Y H:i') : '' }}</span>
                                    <span class="text-pm-muted transition group-hover:translate-x-0.5 group-hover:text-pm-text">&gt;</span>
                                </div>
                            </a>
                        @endif
                    @empty
                        <div class="px-6 py-16 text-center">
                            <div class="text-lg font-black text-pm-text">Nessuna email ricevuta</div>
                            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-pm-text-secondary">Quando qualcuno risponderà a una mail PitMetric, il messaggio comparirà qui.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
