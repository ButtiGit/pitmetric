<x-layouts::auth :title="__('Two-factor authentication')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Two-factor authentication')" :description="__('Inserisci il codice della tua app di autenticazione oppure un codice di recupero.')" />
        <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-4">
            @csrf
            <flux:input name="code" label="Codice autenticatore" inputmode="numeric" autocomplete="one-time-code" maxlength="6" />
            <flux:error name="code" />
            <flux:input name="recovery_code" label="Oppure codice di recupero" autocomplete="off" />
            <flux:error name="recovery_code" />
            <flux:button type="submit" variant="primary" class="w-full">Accedi</flux:button>
        </form>
        <flux:link :href="route('login')">Torna al login</flux:link>
    </div>
</x-layouts::auth>
