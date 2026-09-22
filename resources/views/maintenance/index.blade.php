<x-layouts::app :title="__('Maintenance')">
    @php
        $it = app()->getLocale() === 'it';
        $formatUsage = static function (int $value, \App\Models\UsageMetricType $metric): string {
            $absolute = abs($value);
            $formatted = match ($metric->key) {
                'distance' => number_format($absolute / 1000, 1, ',', '.').' km',
                'runtime' => number_format($absolute / 3600, 1, ',', '.').' h',
                default => number_format($absolute, 0, ',', '.').' '.$metric->display_unit,
            };

            return $value < 0 ? '-'.$formatted : $formatted;
        };
        $boardColumns = [
            'todo' => $it ? 'Da fare' : 'To do',
            'in_progress' => $it ? 'In lavorazione' : 'In progress',
            'blocked' => $it ? 'Bloccati' : 'Blocked',
        ];
    @endphp

    <div class="pitmetric-app pm-mobile-page-with-dock min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1440px] space-y-5">
            @include('maintenance.partials.overview')
            @include('maintenance.partials.workboard')
            @include('maintenance.partials.health-history')
        </div>

        @include('maintenance.partials.mobile-actions')
    </div>
</x-layouts::app>
