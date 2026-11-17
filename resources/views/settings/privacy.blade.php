<x-layouts::app :title="__('Privacy e dati')">
    <section class="w-full">
        @include('partials.settings-heading')
        <x-pages::settings.layout :heading="__('Privacy e dati')" :subheading="__('Richiedi accesso, rettifica, cancellazione o altri diritti sui tuoi dati personali.')">
            <div class="space-y-6">
                @if (session('status'))
                    <p role="status" class="text-sm text-green-700 dark:text-green-400">{{ session('status') }}</p>
                @endif
                <flux:text>La richiesta viene valutata dal referente del servizio; la cancellazione richiede la verifica dei dati del team, degli obblighi di conservazione e dei diritti di altre persone. Puoi indicare anche dati che ti riguardano presenti nei team. Non allegare password, documenti di identità o informazioni non necessarie.</flux:text>
                <flux:button :href="route('newsletter.edit')" wire:navigate>Preferenze newsletter e revoca</flux:button>
                @if ($ready)
                    <form method="POST" action="{{ route('privacy.store') }}" class="space-y-4">
                        @csrf
                        <flux:select name="type" label="Diritto da esercitare" required>
                            @foreach (\App\Models\PrivacyRequest::TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="type" />
                        <flux:textarea name="details" label="Descrivi la richiesta" required minlength="10" maxlength="4000" rows="4" />
                        <flux:error name="details" />
                        <flux:text>Il riscontro viene pubblicato qui, normalmente entro un mese. Per ragioni di sicurezza potrebbe esserti chiesto di confermare nuovamente la password.</flux:text>
                        <flux:button type="submit" variant="primary">Invia richiesta</flux:button>
                    </form>
                    <flux:heading>Le tue richieste</flux:heading>
                    @forelse ($requests as $item)
                        <article class="space-y-3 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <h3 class="font-semibold">#{{ $item->id }} · {{ \App\Models\PrivacyRequest::TYPES[$item->type] }}</h3>
                            <p class="text-sm">{{ \App\Models\PrivacyRequest::STATUSES[$item->status] }} · Termine iniziale: {{ $item->due_at->timezone('Europe/Rome')->format('d/m/Y') }}</p>
                            <p class="whitespace-pre-wrap break-words text-sm">{{ $item->details }}</p>
                            @foreach ($item->history as $event)
                                <p class="whitespace-pre-wrap break-words text-sm">{{ \Illuminate\Support\Carbon::parse($event['at'])->timezone('Europe/Rome')->format('d/m/Y H:i') }} — {{ $event['message'] }}</p>
                            @endforeach
                        </article>
                    @empty
                        <flux:text>Non hai ancora inviato richieste.</flux:text>
                    @endforelse
                    {{ $requests->links() }}
                @else
                    <flux:text>Gestione richieste in attivazione. Il referente deve completare la configurazione del servizio.</flux:text>
                @endif
            </div>
        </x-pages::settings.layout>
    </section>
</x-layouts::app>
