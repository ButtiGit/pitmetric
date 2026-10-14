<x-buttidesk.shell title="ButtiDesk-Pro | Rubrica">
    <div class="bd-hero"><div><p class="bd-eyebrow">RELATIONSHIPS / DIRECTORY</p><h1 class="bd-title">Clienti & fornitori</h1><p class="bd-desc">Una rubrica unica per persone, aziende, contatti tecnici e riferimenti dei ticket.</p></div><span class="bd-badge">{{ $contacts->total() }} contatti</span></div>
    <div class="bd-columns">
        <section class="bd-panel">
            <div class="bd-pad">
                <form method="GET" class="bd-filter">
                    <div class="bd-field bd-grow"><label for="contact-search">Cerca</label><input id="contact-search" name="q" value="{{ $search }}" placeholder="Nome, azienda, indirizzo email"></div>
                    <div class="bd-field"><label for="contact-type">Tipo</label><select id="contact-type" name="type"><option value="">Tutti</option><option value="client" @selected($type==='client')>Clienti</option><option value="supplier" @selected($type==='supplier')>Fornitori</option><option value="both" @selected($type==='both')>Entrambi</option></select></div>
                    <button class="bd-btn" type="submit">Filtra</button>
                </form>
            </div>
            <div class="bd-table-scroller"><table class="bd-table"><thead><tr><th>Contatto</th><th>Tipo</th><th>Recapiti</th><th></th></tr></thead><tbody>
                @forelse ($contacts as $contact)
                    <tr><td><strong>{{ $contact->name }}</strong><div class="bd-datestamp">{{ $contact->company }}</div></td><td><span class="bd-badge">{{ ['client'=>'Cliente','supplier'=>'Fornitore','both'=>'Cliente / Fornitore'][$contact->type] ?? $contact->type }}</span></td><td>@if($contact->email)<a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>@endif<div class="bd-datestamp">{{ $contact->phone }}</div></td><td><a class="bd-link" href="#edit-{{ $contact->id }}">Modifica </a></td></tr>
                    <tr><td colspan="4" style="padding:0;background:#141b28"><details class="bd-details" id="edit-{{ $contact->id }}"><summary>Dettagli e modifica · {{ $contact->name }}</summary>
                        @can('team-write')
                            <form method="POST" action="{{ route('buttidesk.contacts.update', $contact) }}" class="bd-form">@csrf @method('PUT')
                                <div class="bd-form-grid">
                                    <div class="bd-field"><label>Nome *</label><input name="name" required maxlength="180" value="{{ $contact->name }}"></div>
                                    <div class="bd-field"><label>Tipologia *</label><select name="type" required>@foreach(['client'=>'Cliente','supplier'=>'Fornitore','both'=>'Entrambi'] as $value=>$label)<option value="{{ $value }}" @selected($contact->type===$value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="bd-field"><label>Azienda</label><input name="company" value="{{ $contact->company }}"></div>
                                    <div class="bd-field"><label>Email</label><input name="email" type="email" value="{{ $contact->email }}"></div>
                                    <div class="bd-field"><label>Telefono</label><input name="phone" value="{{ $contact->phone }}"></div>
                                    <div class="bd-field"><label>Partita IVA</label><input name="vat_number" value="{{ $contact->vat_number }}"></div>
                                    <div class="bd-field bd-field--full"><label>Note</label><textarea name="notes">{{ $contact->notes }}</textarea></div>
                                </div><div><button class="bd-btn" type="submit">Salva modifiche</button></div>
                            </form>
                        @else <p class="bd-help">Contatto in sola lettura.</p> @endcan
                    </details></td></tr>
                @empty <tr><td colspan="4" class="bd-empty"><strong>Rubrica vuota</strong>Aggiungi un cliente o un fornitore.</td></tr> @endforelse
            </tbody></table></div>
            <div class="bd-pad">{{ $contacts->links() }}</div>
        </section>
        <section class="bd-panel"><div class="bd-panel-heading"><h2>Nuovo contatto</h2></div><div class="bd-pad">
            @can('team-write')
                <form class="bd-form" method="POST" action="{{ route('buttidesk.contacts.store') }}">@csrf
                    <div class="bd-field"><label for="new-contact-name">Nome / riferimento *</label><input id="new-contact-name" name="name" maxlength="180" value="{{ old('name') }}" required placeholder="Es. Mario Rossi"></div>
                    <div class="bd-field"><label>Tipologia *</label><select name="type" required><option value="client">Cliente</option><option value="supplier">Fornitore</option><option value="both">Entrambi</option></select></div>
                    <div class="bd-field"><label>Azienda</label><input name="company" maxlength="180" value="{{ old('company') }}"></div>
                    <div class="bd-field"><label>Email</label><input name="email" type="email" maxlength="254" value="{{ old('email') }}"></div>
                    <div class="bd-field"><label>Telefono</label><input name="phone" maxlength="60" value="{{ old('phone') }}"></div>
                    <div class="bd-field"><label>Partita IVA</label><input name="vat_number" maxlength="40" value="{{ old('vat_number') }}"></div>
                    <div class="bd-field"><label>Note</label><textarea name="notes" maxlength="10000">{{ old('notes') }}</textarea></div>
                    <button class="bd-btn" type="submit">Salva contatto »</button>
                </form>
            @else <p class="bd-help">Non disponi di permessi di modifica.</p> @endcan
        </div></section>
    </div>
</x-buttidesk.shell>
