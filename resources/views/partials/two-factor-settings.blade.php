@if (\Laravel\Fortify\Features::enabled(\Laravel\Fortify\Features::twoFactorAuthentication()))
    <div class="mt-8 space-y-4 border-t border-zinc-200 pt-6 dark:border-zinc-700">
        <flux:heading>{{ __('Two-factor authentication') }}</flux:heading>
        <flux:text>Aggiungi un codice temporaneo generato dalla tua app di autenticazione. Conserva i codici di recupero in un luogo sicuro.</flux:text>
        @if (session('status'))
            <p role="status" class="text-sm">{{ session('status') }}</p>
        @endif
        @if (! auth()->user()->two_factor_secret)
            <form method="POST" action="{{ route('two-factor.enable') }}">
                @csrf
                <flux:button type="submit" variant="primary">Configura autenticazione a due fattori</flux:button>
            </form>
        @else
            @if (! auth()->user()->two_factor_confirmed_at)
                <div class="inline-block rounded-xl bg-white p-3" aria-label="QR di configurazione autenticatore">{!! auth()->user()->twoFactorQrCodeSvg() !!}</div>
                <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-3">
                    @csrf
                    <flux:input name="code" label="Codice dell'autenticatore" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required />
                    @if ($errors->getBag('confirmTwoFactorAuthentication')->has('code'))
                        <p role="alert" class="text-sm text-red-600">{{ $errors->getBag('confirmTwoFactorAuthentication')->first('code') }}</p>
                    @endif
                    <flux:button type="submit">Conferma attivazione</flux:button>
                </form>
            @else
                <p role="status" class="text-sm font-semibold">Autenticazione a due fattori attiva.</p>
                <details class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <summary class="cursor-pointer text-sm font-semibold">Codici di recupero (ciascuno utilizzabile una volta)</summary>
                    <div class="mt-3 grid gap-2 font-mono text-sm">
                        @foreach (auth()->user()->recoveryCodes() as $recoveryCode)
                            <p>{{ $recoveryCode }}</p>
                        @endforeach
                    </div>
                    <form method="POST" action="{{ route('two-factor.regenerate-recovery-codes') }}" class="mt-4" x-data x-on:submit="if (! window.confirm('Sostituire tutti i codici di recupero? Quelli precedenti non saranno più validi.')) $event.preventDefault()">
                        @csrf
                        <flux:button type="submit">Rigenera codici</flux:button>
                    </form>
                </details>
            @endif
            <form method="POST" action="{{ route('two-factor.disable') }}" x-data x-on:submit="if (! window.confirm('Disattivare l’autenticazione a due fattori?')) $event.preventDefault()">
                @csrf
                @method('DELETE')
                <flux:button type="submit" variant="danger">Disattiva o annulla configurazione</flux:button>
            </form>
        @endif
    </div>
@endif
