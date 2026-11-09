<x-layouts::app title="Gestione sito">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-8 sm:px-6 lg:px-8">
        <section class="pm-panel mx-auto max-w-2xl p-6 sm:p-9">
            <span class="font-mono text-xs font-bold uppercase tracking-[.14em] text-pm-warning">Configurazione richiesta</span>
            <h1 class="mt-4 text-3xl font-black tracking-tight text-pm-text">Gestione sito</h1>
            <p class="mt-3 text-sm leading-7 text-pm-text-secondary">Il modulo è stato distribuito, ma le tabelle per statistiche e report non sono ancora state inizializzate. Esegui la migrazione dal terminale dell'applicazione su Coolify per attivarlo.</p>
            <pre class="mt-5 overflow-x-auto rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 font-mono text-sm text-pm-text">php artisan migrate --force --no-interaction</pre>
            <a class="pm-ghost-button mt-5" href="{{ route('studio.site.index') }}">Controlla nuovamente</a>
        </section>
    </div>
</x-layouts::app>
