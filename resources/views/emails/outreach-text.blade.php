{!! $messageBody !!}
@if ($note !== '')

{!! $note !!}
@endif

{{ $locale === 'it' ? 'Esplora la demo' : 'Explore the demo' }}: {!! route('demo.public') !!}
{{ $locale === 'it' ? 'Scopri l’app Android' : 'Discover the Android app' }}: {!! route('localized.app', ['locale' => $locale]) !!}

{{ $locale === 'it' ? 'Rispondete direttamente a questa email per un confronto o per iniziare la prova.' : 'Reply directly to this email to discuss the project or start testing.' }}

Simone Buttice
{{ $locale === 'it' ? 'Sviluppatore e ideatore di PitMetric' : 'Developer and creator of PitMetric' }}
{!! route('localized.home', ['locale' => $locale]) !!}

{{ $locale === 'it' ? 'Non ricevere altre email' : 'Stop future emails' }}: {!! $unsubscribeUrl !!}
