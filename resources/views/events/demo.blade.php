<x-layouts::app :title="__('Race weekends')">
    @php($it = app()->getLocale() === 'it')
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1200px] space-y-5">
            <div><x-pitmetric.page-header :title="$it ? 'Il weekend diventa il centro del lavoro' : 'The weekend becomes the center of operations'" :description="$it ? 'Questa anteprima resta locale: quando l’account viene attivato, gli eventi reali vengono salvati nel database PitMetric.' : 'This preview stays local: when the account is activated, real events are stored in the PitMetric database.'" /></div>

            <section class="pm-panel overflow-hidden">
                <div class="border-b border-pm-border p-5 sm:p-7">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div><div class="flex flex-wrap items-center gap-2"><h2 class="text-2xl font-black text-pm-text">Busca - Race Weekend</h2><x-pitmetric.status-badge :label="$it ? 'In corso' : 'Active'" variant="success" /></div><p class="mt-2 text-sm text-pm-text-secondary">12/09 - 13/09 · Busca Kart Planet · Main layout</p></div>
                        <div class="grid grid-cols-3 gap-2 text-center"><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-2xl font-black text-pm-text">2</p><p class="text-[10px] uppercase text-pm-muted">Entries</p></div><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-2xl font-black text-pm-text">5</p><p class="text-[10px] uppercase text-pm-muted">Sessions</p></div><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-2xl font-black text-pm-warning">3</p><p class="text-[10px] uppercase text-pm-muted">Tasks</p></div></div>
                    </div>
                </div>
                <div class="grid gap-4 p-5 md:grid-cols-2 sm:p-7">
                    <article class="rounded-xl border border-pm-border bg-pm-subtle p-4"><p class="text-[10px] font-black uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'ENTRY' : 'ENTRY' }}</p><h3 class="mt-2 font-black text-pm-text">Pilota A · Kart 27</h3><p class="mt-1 text-sm text-pm-text-secondary">Setup gara v4</p><div class="mt-4 flex flex-wrap gap-2"><span class="rounded-lg border border-pm-border px-2 py-1 text-xs text-pm-text">Practice</span><span class="rounded-lg border border-pm-border px-2 py-1 text-xs text-pm-text">Qualifying</span><span class="rounded-lg border border-pm-border px-2 py-1 text-xs text-pm-text">Heat</span><span class="rounded-lg border border-pm-accent/30 bg-pm-accent/10 px-2 py-1 text-xs font-bold text-pm-accent">Final</span></div></article>
                    <article class="rounded-xl border border-pm-border bg-pm-subtle p-4"><p class="text-[10px] font-black uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'LAVORI' : 'WORK' }}</p><div class="mt-2 space-y-2"><div class="flex items-center justify-between gap-3"><span class="text-sm font-bold text-pm-text">{{ $it ? 'Controllo catena dopo manche' : 'Check chain after heat' }}</span><x-pitmetric.status-badge label="High" variant="warning" /></div><div class="flex items-center justify-between gap-3"><span class="text-sm text-pm-text-secondary">{{ $it ? 'Pressioni prima della finale' : 'Tyre pressures before final' }}</span><x-pitmetric.status-badge label="Todo" variant="neutral" /></div></div></article>
                    <article class="rounded-xl border border-pm-border bg-pm-subtle p-4"><p class="text-[10px] font-black uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'NOTE TECNICHE' : 'TECH NOTES' }}</p><p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Post qualifica: il posteriore scivola in uscita T3. Provare una modifica al setup prima della manche.' : 'Post qualifying: rear slides on T3 exit. Try a setup change before the heat.' }}</p></article>
                    <article class="rounded-xl border border-pm-border bg-pm-subtle p-4"><p class="text-[10px] font-black uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'COSTI EVENTO' : 'EVENT COSTS' }}</p><p class="mt-2 text-2xl font-black text-pm-text">€ 438,50</p><p class="mt-1 text-sm text-pm-text-secondary">{{ $it ? 'Iscrizione, pista, sessioni e lavori collegati nello stesso weekend.' : 'Entry, track, sessions and linked work inside the same weekend.' }}</p></article>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
