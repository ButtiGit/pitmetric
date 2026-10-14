<x-buttidesk.shell title="ButtiDesk-Pro | Panoramica">
    <div class="bd-hero">
        <div>
            <p class="bd-eyebrow">CONTROL ROOM / DAILY OVERVIEW</p>
            <h1 class="bd-title">Il lavoro, sotto controllo.</h1>
            <p class="bd-desc">Ticket, interventi, clienti e appuntamenti in un'unica scrivania. Le attività restano separate da PitMetric, ma condividono il tuo accesso e il team attivo.</p>
        </div>
        <div class="bd-actions"><a class="bd-btn" href="{{ route('buttidesk.tickets.index') }}">Apri ticket »</a><a class="bd-btn bd-btn--ghost" href="{{ route('buttidesk.calendar.index') }}">Calendario</a></div>
    </div>
    <div class="bd-grid">
        <div class="bd-stat"><div class="bd-stat-label">Ticket attivi</div><div class="bd-stat-number">{{ $openTickets }}</div><div class="bd-stat-note">Aperti, in corso o in attesa</div></div>
        <div class="bd-stat"><div class="bd-stat-label">Priorità urgente</div><div class="bd-stat-number">{{ $urgentTickets }}</div><div class="bd-stat-note">Richiedono attenzione</div></div>
        <div class="bd-stat"><div class="bd-stat-label">Contatti</div><div class="bd-stat-number">{{ $contactsCount }}</div><div class="bd-stat-note">Clienti e fornitori</div></div>
        <div class="bd-stat"><div class="bd-stat-label">Tempo registrato</div><div class="bd-stat-number">{{ number_format($minutesThisMonth / 60, 1, ',', '.') }}<span style="font-size:15px;color:#aebbdc"> h</span></div><div class="bd-stat-note">Interventi questo mese</div></div>
    </div>
    <div class="bd-columns">
        <section class="bd-panel">
            <div class="bd-panel-heading"><div><h2>Ultimi ticket</h2><p>Attività recenti del workspace</p></div><a href="{{ route('buttidesk.tickets.index') }}">Vedi tutti »</a></div>
            <div class="bd-table-scroller"><table class="bd-table"><thead><tr><th>Ticket</th><th>Cliente</th><th>Stato</th><th>Priorità</th></tr></thead><tbody>
                @forelse ($latestTickets as $ticket)
                    <tr><td><a href="{{ route('buttidesk.tickets.show', $ticket) }}"><strong>#{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }} · {{ $ticket->title }}</strong></a></td><td>{{ $ticket->client?->name ?? '—' }}</td><td><span class="bd-badge bd-badge--{{ $ticket->status }}">{{ ['open'=>'Aperto','progress'=>'In corso','waiting'=>'In attesa','done'=>'Completato','cancelled'=>'Annullato'][$ticket->status] ?? $ticket->status }}</span></td><td>{{ ucfirst($ticket->priority) }}</td></tr>
                @empty <tr><td colspan="4" class="bd-empty"><strong>Ancora nessun ticket</strong>Registra la prima richiesta per iniziare.</td></tr> @endforelse
            </tbody></table></div>
        </section>
        <section class="bd-panel">
            <div class="bd-panel-heading"><div><h2>Prossimi impegni</h2><p>Agenda collegata ai ticket</p></div><a href="{{ route('buttidesk.calendar.index') }}">Apri »</a></div>
            @forelse ($upcoming as $appointment)
                <a class="bd-item" href="{{ route('buttidesk.calendar.index', ['month' => $appointment->starts_at->copy()->timezone(config('buttidesk.timezone'))->format('Y-m')]) }}">
                    <div class="bd-item-mark">{{ $appointment->starts_at->copy()->timezone(config('buttidesk.timezone'))->format('d') }}</div>
                    <div class="bd-item-body"><strong>{{ $appointment->title }}</strong><small>{{ $appointment->starts_at->copy()->timezone(config('buttidesk.timezone'))->format('d/m H:i') }} · {{ $appointment->ticket ? '#'.$appointment->ticket->id.' '.$appointment->ticket->title : 'Impegno libero' }}</small></div>
                </a>
            @empty <div class="bd-empty"><strong>Agenda libera</strong>Nessun impegno imminente.</div> @endforelse
        </section>
    </div>
</x-buttidesk.shell>
