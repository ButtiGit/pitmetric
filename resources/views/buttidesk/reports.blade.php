<x-buttidesk.shell title="ButtiDesk-Pro | Report attività">
    <div class="bd-hero"><div><p class="bd-eyebrow">ANALYTICS / ACTIVITY LEDGER</p><h1 class="bd-title">Report interventi</h1><p class="bd-desc">Tutte le attività registrate nei ticket: ore lavorate, date, operatori e lavoro svolto. Filtra per periodo e stampa la pagina o il singolo ticket.</p></div><button class="bd-btn bd-btn--ghost bd-no-print" onclick="window.print()">Stampa riepilogo </button></div>
    <section class="bd-panel">
        <div class="bd-pad bd-no-print"><form method="GET" class="bd-filter"><div class="bd-field"><label>Dal</label><input name="from" type="date" value="{{ $period['from'] }}" required></div><div class="bd-field"><label>Al</label><input name="to" type="date" value="{{ $period['to'] }}" required></div><button class="bd-btn" type="submit">Aggiorna report</button></form></div>
        <div class="bd-panel-heading"><div><h2>Interventi dal {{ \Illuminate\Support\Carbon::parse($period['from'])->format('d/m/Y') }} al {{ \Illuminate\Support\Carbon::parse($period['to'])->format('d/m/Y') }}</h2><p>{{ $logs->count() }} registrazioni · {{ number_format($totalMinutes/60, 2, ',', '.') }} ore complessive</p></div></div>
        <div class="bd-table-scroller"><table class="bd-table"><thead><tr><th>Data</th><th>Ticket / cliente</th><th>Attività</th><th>Operatore</th><th>Durata</th></tr></thead><tbody>
            @forelse($logs as $log)<tr><td class="bd-datestamp">{{ $log->performed_at->copy()->timezone(config('buttidesk.timezone'))->format('d/m/Y H:i') }}</td><td>@if($log->ticket)<a href="{{ route('buttidesk.tickets.show', $log->ticket) }}"><strong>#{{ $log->ticket->id }} {{ $log->ticket->title }}</strong></a><div class="bd-datestamp">{{ $log->ticket->client?->name }}</div>@else—@endif</td><td><strong>{{ $log->summary }}</strong>@if($log->details)<div class="bd-datestamp">{{ $log->details }}</div>@endif</td><td>{{ $log->author?->name }}</td><td class="bd-numeric">{{ $log->duration_minutes }} min</td></tr>
            @empty<tr><td colspan="5" class="bd-empty"><strong>Nessuna attività</strong>Nessun intervento registrato nel periodo selezionato.</td></tr>@endforelse
            <tr><td colspan="4"><strong>Totale periodo</strong></td><td><strong>{{ number_format($totalMinutes/60, 2, ',', '.') }} h</strong></td></tr>
        </tbody></table></div>
    </section>
</x-buttidesk.shell>
