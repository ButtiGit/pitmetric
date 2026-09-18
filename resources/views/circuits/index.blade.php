<x-layouts::app :title="__('Circuits')">
    @php($it = app()->getLocale() === 'it')
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <div class="pm-next-actions"><span class="font-semibold text-pm-muted">{{ __('workflow.next') }}</span><a href="{{ route('events.index') }}#create-event">{{ __('workflow.events') }}</a><a href="{{ route('sessions.index') }}#record-session">{{ __('workflow.sessions') }}</a></div>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">CIRCUITS</p><x-pitmetric.page-header :title="$it ? 'Circuiti e layout' : 'Circuits and layouts'" :description="$it ? 'La lunghezza del layout è la fonte autorevole per calcolare automaticamente la distanza di una sessione.' : 'Layout length is the authoritative source used to calculate session distance.'" /></div>
                <x-crud-modal id="create-circuit" :title="$it ? 'Aggiungi circuito' : 'Add circuit'" :description="$it ? 'I dati del circuito restano fuori dalla vista finché non devi aggiungerne uno.' : 'Circuit fields stay out of the way until you need to add one.'" :trigger="$it ? '+ Circuito' : '+ Circuit'">
                    @can('team-write')<form method="POST" action="{{ route('circuits.store') }}" class="grid gap-4 md:grid-cols-2">@csrf
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Circuito' : 'Circuit' }}</span><input class="pm-input" name="name" required maxlength="120" placeholder="Kart Planet"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Paese' : 'Country' }}</span><input class="pm-input" name="country" maxlength="80" placeholder="Italy"></label>
                        <label class="grid gap-2"><span class="pm-label">Layout</span><input class="pm-input" name="layout_name" required maxlength="120" placeholder="Full"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Lunghezza (m)' : 'Length (m)' }}</span><input class="pm-input" name="length_meters" type="number" required min="1" max="100000" step="1"></label>
                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea></label>
                        <div class="md:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Aggiungi circuito' : 'Add circuit' }}</button></div>
                    </form>@endcan
                </x-crud-modal>
            </div>
            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($circuits as $circuit)
                <article class="pm-panel p-5">
                    <h2 class="font-black text-pm-text">{{ $circuit->name }}</h2><p class="mt-1 text-sm text-pm-muted">{{ $circuit->country ?: '—' }}</p>
                    <div class="mt-4 flex flex-wrap gap-2"><x-crud-modal id="edit-circuit-{{ $circuit->id }}" :title="$it ? 'Modifica circuito' : 'Edit circuit'" :trigger="$it ? 'Modifica circuito' : 'Edit circuit'" trigger-class="pm-ghost-button">
 @can('team-write')<form method="POST" action="{{ route('circuits.update', $circuit) }}" class="grid gap-4 sm:grid-cols-2">@csrf @method('PUT')
 <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Circuito' : 'Circuit' }}</span><input class="pm-input" name="name" type="text" value="{{ $circuit->name }}" required maxlength="120"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Paese' : 'Country' }}</span><input class="pm-input" name="country" type="text" value="{{ $circuit->country }}" maxlength="80"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ $circuit->notes }}</textarea></label>
 <div class="sm:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Salva modifiche' : 'Save changes' }}</button></div>
 </form>@endcan</x-crud-modal><x-crud-modal id="add-layout-{{ $circuit->id }}" :title="$it ? 'Aggiungi layout' : 'Add layout'" :trigger="$it ? 'Aggiungi layout' : 'Add layout'" trigger-class="pm-ghost-button">
 @can('team-write')<form method="POST" action="{{ route('circuits.layouts.store', $circuit) }}" class="grid gap-4 sm:grid-cols-2">@csrf @method('POST')
 <label class="grid gap-2"><span class="pm-label">Layout</span><input class="pm-input" name="name" type="text" value="" required maxlength="120"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Lunghezza (m)' : 'Length (m)' }}</span><input class="pm-input" name="length_meters" type="number" value="" required min="1" max="100000" step="1"></label>
 <div class="sm:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Salva modifiche' : 'Save changes' }}</button></div>
 </form>@endcan</x-crud-modal></div>
                    <div class="mt-4 space-y-3">@foreach ($circuit->layouts as $layout)
                        <div class="rounded-lg border border-pm-border bg-pm-subtle p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2"><span class="font-semibold text-pm-text">{{ $layout->name }}</span><span class="font-mono text-sm text-pm-text-secondary">{{ number_format($layout->length_meters, 0, ',', '.') }} m</span></div>
                            @if (! $layout->is_active)<p class="my-2 text-xs text-pm-muted">{{ $it ? 'Archiviato · conservato nello storico' : 'Archived · kept in history' }}</p>@endif
                            <div class="mt-3"><x-crud-modal id="edit-layout-{{ $layout->id }}" :title="$it ? 'Modifica layout' : 'Edit layout'" :trigger="$it ? 'Modifica layout' : 'Edit layout'" trigger-class="pm-ghost-button">
 @can('team-write')<form method="POST" action="{{ route('circuits.layouts.update', [$circuit, $layout]) }}" class="grid gap-4 sm:grid-cols-2">@csrf @method('PUT')
 <label class="grid gap-2"><span class="pm-label">Layout</span><input class="pm-input" name="name" type="text" value="{{ $layout->name }}" required maxlength="120"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Lunghezza (m)' : 'Length (m)' }}</span><input class="pm-input" name="length_meters" type="number" value="{{ $layout->length_meters }}" required min="1" max="100000" step="1"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Disponibilità' : 'Availability' }}</span><select class="pm-input" name="is_active"><option value="1" @selected($layout->is_active)>{{ $it ? 'Attivo' : 'Active' }}</option><option value="0" @selected(! $layout->is_active)>{{ $it ? 'Archiviato' : 'Archived' }}</option></select></label><p class="text-sm text-pm-muted">{{ $it ? 'Se già utilizzato, aggiungi un nuovo layout per cambiare lunghezza. Puoi archiviarlo per escluderlo dalle nuove attività.' : 'If already used, add a new layout to change its length. Archive it to exclude it from new activities.' }}</p>
 <div class="sm:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Salva modifiche' : 'Save changes' }}</button></div>
 </form>@endcan</x-crud-modal></div>
                        </div>
                    @endforeach</div>
                </article>
                @empty<x-pitmetric.empty-state :title="$it ? 'Nessun circuito' : 'No circuits'" :description="$it ? 'Aggiungi il primo circuito usato nelle sessioni.' : 'Add the first circuit used by sessions.'" />@endforelse
            </div>
        </div>
    </div>
</x-layouts::app>
