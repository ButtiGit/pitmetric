<section id="privacy" aria-labelledby="privacy-title" class="scroll-mt-6 space-y-4">
    <h2 id="privacy-title" class="text-xl font-black text-pm-text">Sicurezza e privacy</h2>
    <p class="pm-panel p-5 text-sm leading-6 text-pm-warning">Adeguamento in corso. Questi controlli misurano alcune configurazioni tecniche, non attestano conformità GDPR o certificazione ISO/IEC 27001. Le verifiche organizzative e dell'infrastruttura richiedono evidenze indipendenti.</p>
    <div class="grid gap-3 md:grid-cols-2">
        @foreach ($privacyChecks as $check)
            <article class="pm-panel min-w-0 p-4">
                <h3 class="text-sm font-bold text-pm-text">{{ $check['name'] }} · {{ $check['status'] }}</h3>
                <p class="mt-2 text-xs leading-5 text-pm-text-secondary">{{ $check['detail'] }}</p>
            </article>
        @endforeach
    </div>
    <details class="pm-panel p-5">
        <summary class="cursor-pointer text-sm font-bold text-pm-text">Adempimenti ed evidenze da completare</summary>
        <div class="mt-4 space-y-4">
            @foreach ($privacyOutstanding as $item)
                <article>
                    <h3 class="text-sm font-bold text-pm-text">Da verificare — {{ $item['name'] }}</h3>
                    <p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $item['detail'] }}</p>
                </article>
            @endforeach
            <p class="text-sm text-pm-muted">Riferimenti: <a class="underline" href="https://eur-lex.europa.eu/eli/reg/2016/679/oj/ita">Regolamento UE 2016/679</a> · <a class="underline" href="https://www.iso.org/standard/27001">ISO/IEC 27001:2022</a> · <a class="underline" href="https://www.garanteprivacy.it/home/docweb/-/docweb-display/docweb/9677876">Linee guida cookie del Garante</a>.</p>
        </div>
    </details>
    <div class="pm-panel space-y-4 p-5">
        <h3 class="text-lg font-black text-pm-text">Richieste degli interessati</h3>
        <p class="text-sm leading-6 text-pm-text-secondary">Le richieste sono disponibili nelle impostazioni di ogni account. I riscontri sono visibili al richiedente in quella pagina; questa funzione non invia email. Verificare giornalmente il registro e gestire separatamente richieste ricevute via altri canali, interessati senza account, eventuali proroghe motivate e attività sui dati. Chiudere una richiesta registra il riscontro, senza cancellare automaticamente dati.</p>
        @if ($privacyOverdue > 0)
            <p role="status" class="text-sm font-bold text-pm-warning">{{ $privacyOverdue }} richieste oltre il termine iniziale: intervenire.</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="text-sm text-pm-warning">{{ $errors->first() }}</div>
        @endif
        @if ($privacyReady)
            @forelse ($privacyRequests as $item)
                <details class="rounded-xl border border-pm-border p-4">
                    <summary class="cursor-pointer break-words text-sm font-bold text-pm-text">#{{ $item->id }} · {{ \App\Models\PrivacyRequest::TYPES[$item->type] }} · {{ \App\Models\PrivacyRequest::STATUSES[$item->status] }} · {{ $item->due_at->timezone('Europe/Rome')->format('d/m/Y') }}</summary>
                    <div class="mt-4 space-y-3 text-sm text-pm-text-secondary">
                        <p class="break-words">{{ $item->user?->name ?? 'Account eliminato' }} · {{ $item->user?->email }}</p>
                        <p class="whitespace-pre-wrap break-words">{{ $item->details }}</p>
                        @foreach ($item->history as $event)
                            <p class="whitespace-pre-wrap break-words">{{ \Illuminate\Support\Carbon::parse($event['at'])->timezone('Europe/Rome')->format('d/m/Y H:i') }} · Attore #{{ $event['actor_id'] }} · {{ $event['message'] }}</p>
                        @endforeach
                        @if (! $item->closed_at)
                            <form method="POST" action="{{ route('studio.privacy.respond', $item) }}" class="space-y-3">
                                @csrf
                                <input type="hidden" name="privacy_page" value="{{ $privacyRequests->currentPage() }}">
                                <flux:select name="status" label="Nuovo stato" required>
                                    @foreach (['processing', 'completed', 'rejected'] as $state)
                                        <option value="{{ $state }}">{{ \App\Models\PrivacyRequest::STATUSES[$state] }}</option>
                                    @endforeach
                                </flux:select>
                                <flux:textarea name="message" label="Riscontro visibile al richiedente (motivazione obbligatoria)" required minlength="10" maxlength="4000" rows="3" />
                                <flux:button type="submit">Registra riscontro</flux:button>
                            </form>
                        @endif
                    </div>
                </details>
            @empty
                <p class="text-sm text-pm-muted">Nessuna richiesta registrata.</p>
            @endforelse
            {{ $privacyRequests->fragment('privacy')->links() }}
        @else
            <p class="text-sm text-pm-warning">Applica le migrazioni per attivare il registro delle richieste privacy.</p>
        @endif
    </div>
</section>
