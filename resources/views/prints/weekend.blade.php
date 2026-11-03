<x-prints.layout :$kind :$workspace :$reference :$generatedAt :$backUrl>
    <x-slot:actions>
        <a href="{{ route('insights.events.report.csv', $report['event']) }}">{{ __('weekend.csv') }}</a>
    </x-slot:actions>
    <x-prints.weekend-report :$report />
</x-prints.layout>
