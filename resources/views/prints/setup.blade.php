<x-prints.layout :$kind :$workspace :$reference :$generatedAt :$backUrl>
    <x-prints.vehicle :$vehicle />
    <section><h2>{{ $setup->name }}</h2><p class="print-caption">{{ __('prints.profile_help') }}</p>
        <x-prints.setup-values :rows="$setupRows" />
    </section>
    @if ($setup->description)<section><h2>{{ __('prints.notes') }}</h2><p class="print-note">{{ $setup->description }}</p></section>
    @endif
</x-prints.layout>
