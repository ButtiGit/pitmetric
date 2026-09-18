<x-layouts::app :title="__('Circuits')">
    @php($it = app()->getLocale() === 'it')
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-6">
            <div class="pm-page-toolbar">
                <x-pitmetric.page-header :title="$it ? 'Circuiti' : 'Circuits'" :description="$it ? 'Tracciati e lunghezze utilizzati nelle sessioni.' : 'Tracks and lengths used in your sessions.'" />
                <x-crud-modal id="create-circuit" :title="$it ? 'Aggiungi circuito' : 'Add circuit'" :trigger="$it ? 'Aggiungi circuito' : 'Add circuit'">
                    <form method="POST" action="{{ route('circuits.store') }}" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nome circuito' : 'Circuit name' }}</span><input class="pm-input" name="name" required maxlength="120"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Paese' : 'Country' }}</span><input class="pm-input" name="country" maxlength="80"></label>
                        <label class="grid gap-2"><span class="pm-label">Layout</span><input class="pm-input" name="layout_name" required maxlength="120" placeholder="{{ $it ? 'Completo' : 'Full track' }}"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Lunghezza (m)' : 'Length (m)' }}</span><input class="pm-input" name="length_meters" type="number" required min="1" max="100000" step="1"></label>
                        <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input" name="notes" maxlength="2000"></textarea></label>
                        <div class="sm:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Aggiungi circuito' : 'Add circuit' }}</button></div>
                    </form>
                </x-crud-modal>
            </div>
            @if (session('status'))<p role="status" class="pm-feedback">{{ session('status') }}</p>@endif
            @if ($errors->any())<div role="alert" class="pm-feedback text-pm-danger"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <div class="space-y-4">
                @forelse ($circuits->reject(fn ($circuit) => $circuit->trashed()) as $circuit)
                    <section class="pm-panel" aria-labelledby="circuit-{{ $circuit->id }}">
                        <div class="pm-record-heading">
                            <div class="min-w-0"><h2 id="circuit-{{ $circuit->id }}" class="text-lg font-semibold text-pm-text break-words">{{ $circuit->name }}</h2>@if ($circuit->country)<p class="mt-1 text-sm text-pm-muted">{{ $circuit->country }}</p>@endif</div>
                            <div class="pm-record-actions">
                                <x-pitmetric.record-editor id="edit-circuit-{{ $circuit->id }}" :title="$it ? 'Modifica circuito' : 'Edit circuit'" :action="route('circuits.update', $circuit)">
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nome circuito' : 'Circuit name' }}</span><input class="pm-input" name="name" required maxlength="120" value="{{ $circuit->name }}"></label>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Paese' : 'Country' }}</span><input class="pm-input" name="country" maxlength="80" value="{{ $circuit->country }}"></label>
                                    <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input" name="notes" maxlength="2000">{{ $circuit->notes }}</textarea></label>
                                </x-pitmetric.record-editor>
                                <x-pitmetric.record-delete :action="route('circuits.destroy', $circuit)" :label="$it ? 'Elimina circuito' : 'Delete circuit'" :message="$it ? 'Eliminare il circuito dalla lista? Le sessioni e i weekend già registrati resteranno disponibili. Puoi ripristinarlo in seguito.' : 'Remove this circuit from the list? Existing sessions and weekends will remain available. You can restore it later.'" />
                            </div>
                        </div>
                        @if ($circuit->notes)<p class="px-5 pb-4 text-sm text-pm-muted whitespace-pre-line">{{ $circuit->notes }}</p>@endif
                        <div class="divide-y divide-pm-border border-t border-pm-border">
                            @forelse ($circuit->layouts->reject(fn ($layout) => $layout->trashed()) as $layout)
                                <div class="pm-record-heading">
                                    <div class="min-w-0"><h3 class="font-medium text-pm-text break-words">{{ $layout->name }}</h3><p class="mt-1 text-sm font-mono text-pm-muted">{{ number_format($layout->length_meters, 0, ',', '.') }} m @if (! $layout->is_active) · {{ $it ? 'Non attivo' : 'Inactive' }} @endif</p></div>
                                    <div class="pm-record-actions">
                                        <x-pitmetric.record-editor id="edit-layout-{{ $layout->id }}" :title="$it ? 'Modifica layout' : 'Edit layout'" :action="route('circuits.layouts.update', [$circuit, $layout])">
                                            <label class="grid gap-2"><span class="pm-label">Layout</span><input class="pm-input" name="name" required maxlength="120" value="{{ $layout->name }}"></label>
                                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Lunghezza (m)' : 'Length (m)' }}</span><input class="pm-input" name="length_meters" type="number" required min="1" max="100000" step="1" value="{{ $layout->length_meters }}"></label>
                                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Disponibilità' : 'Availability' }}</span><select class="pm-input" name="is_active"><option value="1" @selected($layout->is_active)>{{ $it ? 'Attivo' : 'Active' }}</option><option value="0" @selected(! $layout->is_active)>{{ $it ? 'Non attivo' : 'Inactive' }}</option></select></label>
                                            <p class="text-sm text-pm-muted sm:col-span-2">{{ $it ? 'Se il layout è già stato usato, crea un nuovo layout per cambiare la lunghezza.' : 'If the layout has already been used, add a new layout to change its length.' }}</p>
                                        </x-pitmetric.record-editor>
                                        <x-pitmetric.record-delete :action="route('circuits.layouts.destroy', [$circuit, $layout])" :label="$it ? 'Elimina layout' : 'Delete layout'" :message="$it ? 'Eliminare il layout dalla lista? Le distanze già registrate non cambieranno.' : 'Remove this layout? Previously recorded distances will not change.'" />
                                    </div>
                                </div>
                            @empty
                                <p class="p-5 text-sm text-pm-muted">{{ $it ? 'Aggiungi un layout per usare questo circuito nelle sessioni.' : 'Add a layout to use this circuit in sessions.' }}</p>
                            @endforelse
                        </div>
                        <div class="border-t border-pm-border px-5 py-3">
                            <x-pitmetric.record-editor id="add-layout-{{ $circuit->id }}" :title="$it ? 'Aggiungi layout' : 'Add layout'" :trigger="$it ? 'Aggiungi layout' : 'Add layout'" :action="route('circuits.layouts.store', $circuit)" method="POST">
                                <label class="grid gap-2"><span class="pm-label">Layout</span><input class="pm-input" name="name" required maxlength="120"></label>
                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Lunghezza (m)' : 'Length (m)' }}</span><input class="pm-input" name="length_meters" type="number" required min="1" max="100000" step="1"></label>
                            </x-pitmetric.record-editor>
                            @if ($circuit->layouts->contains(fn ($layout) => $layout->trashed()))
                                <details class="pm-archive-list mt-3"><summary>{{ __('crud.removed_layouts') }}</summary>
                                    @foreach ($circuit->layouts->filter(fn ($layout) => $layout->trashed()) as $layout)
                                        <div class="pm-record-heading px-0"><span class="text-sm text-pm-muted">{{ $layout->name }} · {{ $layout->length_meters }} m</span>@can('team-write')<form method="POST" action="{{ route('circuits.layouts.restore', [$circuit, $layout]) }}">@csrf @method('PATCH')<button class="pm-row-action" type="submit">{{ __('crud.restore') }}</button></form>@endcan</div>
                                    @endforeach
                                </details>
                            @endif
                        </div>
                    </section>
                @empty
                    <x-pitmetric.empty-state :title="$it ? 'Nessun circuito' : 'No circuits'" :description="$it ? 'Aggiungi il circuito e la lunghezza del primo layout.' : 'Add a circuit and the length of its first layout.'" />
                @endforelse
            </div>
            @if ($circuits->contains(fn ($circuit) => $circuit->trashed()))
                <details class="pm-archive-list"><summary>{{ __('crud.removed_circuits') }} ({{ $circuits->filter(fn ($circuit) => $circuit->trashed())->count() }})</summary>
                    @foreach ($circuits->filter(fn ($circuit) => $circuit->trashed()) as $circuit)
                        <div class="pm-record-heading px-0"><span class="text-sm text-pm-muted">{{ $circuit->name }}</span>@can('team-write')<form method="POST" action="{{ route('circuits.restore', $circuit) }}">@csrf @method('PATCH')<button class="pm-row-action" type="submit">{{ __('crud.restore') }}</button></form>@endcan</div>
                    @endforeach
                </details>
            @endif
        </div>
    </div>
</x-layouts::app>
