<x-buttidesk.shell title="ButtiDesk-Pro | Ticket">
    <div class="bd-hero"><div><p class="bd-eyebrow">SERVICE DESK / REQUESTS</p><h1 class="bd-title">Gestione ticket</h1><p class="bd-desc">Ogni richiesta ha una priorità, uno stato, un cliente e uno storico delle operazioni realmente eseguite.</p></div><a class="bd-btn bd-btn--ghost" href="{{ route('buttidesk.tickets.reports') }}">Visualizza report →</a></div>
    <div class="bd-columns">
        <section class="bd-panel">
            <div class="bd-pad"><form method="GET" class="bd-filter"><div class="bd-field bd-grow"><label>Ricerca</label><input name="q" value="{{ $search }}" placeholder="Cerca un ticket..."></div><div class="bd-field"><label>Stato</label><select name="status"><option value="">Tutti</option>@foreach(['open'=>'Aperti','progress'=>'In corso','waiting'=>'In attesa','done'=>'Completati','cancelled'=>'Annullati'] as $value=>$label)<option value="{{ $value }}" @selected($statusFilter===$value)>{{ $label }}</option>@endforeach</select></div><button class="bd-btn" type="submit">Filtra</button></form></div>
            <div class="bd-table-scroller"><table class="bd-table"><thead><tr><th>ID / richiesta</th><th>Cliente</th><th>Priorità</th><th>Stato</th><th>Scadenza</th></tr></thead><tbody>
                @forelse($tickets as $ticket)<tr>
                    <td><a href="{{ route('buttidesk.tickets.show', $ticket) }}"><strong>#{{ str_pad($ticket->id,4,'0',STR_PAD_LEFT) }} · {{ $ticket->title }}</strong></a><div class="bd-datestamp">{{ $ticket->category }} · {{ $ticket->created_at->copy()->timezone(config('buttidesk.timezone'))->format('d/m/Y') }}</div></td>
                    <td>{{ $ticket->client?->name ?? '—' }}</td>
                    <td><span class="bd-badge bd-badge--{{ $ticket->priority }}">{{ ['urgent'=>'Urgente','high'=>'Alta','normal'=>'Normale','low'=>'Bassa'][$ticket->priority] ?? $ticket->priority }}</span></td>
                    <td><span class="bd-badge bd-badge--{{ $ticket->status }}">{{ ['open'=>'Aperto','progress'=>'In corso','waiting'=>'In attesa','done'=>'Completato','cancelled'=>'Annullato'][$ticket->status] ?? $ticket->status }}</span></td>
                    <td class="bd-datestamp">{{ $ticket->due_at?->copy()->timezone(config('buttidesk.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>@empty<tr><td colspan="5" class="bd-empty"><strong>Nessun ticket trovato</strong>Modifica i filtri o registra una richiesta.</td></tr>@endforelse
            </tbody></table></div><div class="bd-pad">{{ $tickets->links() }}</div>
        </section>
        <section class="bd-panel"><div class="bd-panel-heading"><h2>Apri un nuovo ticket</h2></div><div class="bd-pad">
            @can('team-write')
                <form class="bd-form" method="POST" action="{{ route('buttidesk.tickets.store') }}">@csrf
                    <div class="bd-field"><label>Oggetto *</label><input name="title" required maxlength="180" value="{{ old('title') }}" placeholder="Es. Configurazione PC postazione"></div>
                    <div class="bd-field"><label>Descrizione</label><textarea name="description" maxlength="15000" placeholder="Problema, richiesta, materiale necessario...">{{ old('description') }}</textarea></div>
                    <div class="bd-form-grid">
                        <div class="bd-field"><label>Stato</label><select name="status"><option value="open">Aperto</option><option value="progress">In corso</option><option value="waiting">In attesa</option><option value="done">Completato</option></select></div>
                        <div class="bd-field"><label>Priorità</label><select name="priority"><option value="normal">Normale</option><option value="low">Bassa</option><option value="high">Alta</option><option value="urgent">Urgente</option></select></div>
                    </div>
                    <div class="bd-field"><label>Categoria</label><input name="category" maxlength="60" required value="support"></div>
                    <div class="bd-field"><label>Cliente</label><select name="client_id"><option value="">Non assegnato</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></div>
                    <div class="bd-field"><label>Fornitore</label><select name="supplier_id"><option value="">Nessuno</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div>
                    <div class="bd-field"><label>Scadenza</label><input name="due_at" type="datetime-local" value="{{ old('due_at') }}"></div>
                    <button class="bd-btn" type="submit">Crea ticket →</button>
                </form>
            @else <p class="bd-help">Non hai i permessi per creare ticket.</p> @endcan
        </div></section>
    </div>
</x-buttidesk.shell>
