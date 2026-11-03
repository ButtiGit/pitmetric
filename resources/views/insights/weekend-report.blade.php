<x-layouts::app :title="__('weekend.title').' · '.$report['event']->name">
    <div class="pitmetric-app min-h-full bg-pm-page p-4 sm:p-6">
        <div class="mx-auto max-w-5xl space-y-4">
            <div class="flex flex-wrap gap-3">
                <a class="pm-ghost-button" href="{{ route('events.show', $report['event']) }}" wire:navigate>{{ __('prints.back') }}</a>
                <a class="pm-race-button" href="{{ route('prints.weekend', $report['event']) }}" target="_blank" rel="noopener">{{ __('prints.print') }}</a>
                <a class="pm-ghost-button" href="{{ route('insights.events.report.csv', $report['event']) }}">{{ __('weekend.csv') }}</a>
            </div>
            <div class="pm-weekend-report rounded-2xl p-4 sm:p-7" data-weekend-report="{{ $report['event']->id }}">
                <h1>{{ __('weekend.title') }}</h1>
                <p class="print-caption">{{ $report['workspace']->name }} · #{{ $report['event']->id }} · {{ __('prints.generated') }} {{ $report['generated_at']->format('d/m/Y H:i') }} · {{ __('prints.timezone') }}</p>
                <x-prints.weekend-report :$report :links="true" />
            </div>
        </div>
    </div>
</x-layouts::app>
