@props(['rows'])
@if ($rows)
<table class="print-values">
    <thead><tr><th scope="col">{{ __('prints.parameter') }}</th><th scope="col">{{ __('prints.value') }}</th></tr></thead>
    <tbody>
    @foreach ($rows as $row)<tr><th scope="row">{{ $row['label'] }}</th><td>{{ $row['value'] }}</td></tr>
    @endforeach</tbody>
</table>
@else
<p>{{ __('prints.no_setup_values') }}</p>
@endif
