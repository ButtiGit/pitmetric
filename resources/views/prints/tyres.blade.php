<x-prints.layout :$kind :$workspace :$reference :$generatedAt :$backUrl>
    <x-slot:actions><a href="{{ route('prints.session', $session) }}">{{ __('prints.titles.session') }}</a></x-slot:actions>
    <x-prints.session-context :$session />
    <section><h2>{{ __('prints.titles.tyres') }}</h2><x-prints.tyre-readings :$session /></section>
</x-prints.layout>
