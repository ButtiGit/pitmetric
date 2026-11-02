<x-prints.layout :$kind :$workspace :$reference :$generatedAt :$backUrl>
    <x-prints.vehicle :$vehicle />
    <section><h2>{{ __('prints.installed_components') }}</h2>
        <p class="print-caption">{{ __('prints.current_installations') }}</p>
        <table>
            <thead><tr><th scope="col">{{ __('prints.component') }}</th><th scope="col">{{ __('prints.position') }}</th><th scope="col">{{ __('prints.serial') }}</th></tr></thead>
            <tbody>
    @forelse ($vehicle->componentInstallations as $installation)<tr><td>{{ $installation->component?->name ?? '—' }}<br><span class="print-caption">{{ $installation->component?->type?->name ?? '' }}</span></td><td>{{ $installation->position_or_role ?: '—' }}</td><td>{{ $installation->component?->serial_number ?: '—' }}</td></tr>
    @empty<tr><td colspan="3">{{ __('prints.no_components') }}</td></tr>
    @endforelse</tbody>
        </table>
    </section>
    @if ($vehicle->notes)<section><h2>{{ __('prints.notes') }}</h2><p class="print-note">{{ $vehicle->notes }}</p></section>
    @endif
</x-prints.layout>
