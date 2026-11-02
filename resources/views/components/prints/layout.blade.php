@props(['kind', 'workspace', 'reference', 'generatedAt', 'backUrl'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('prints.titles.'.$kind) }} {{ $reference }} | PitMetric</title>
    @vite('resources/css/print.css')
</head>
<body>
    <nav class="print-toolbar" aria-label="{{ __('prints.actions') }}">
        <a href="{{ $backUrl }}">{{ __('prints.back') }}</a>
        <button type="button" onclick="window.print()">{{ __('prints.print') }}</button>
        {{ $actions ?? '' }}
        <p>{{ __('prints.help') }}</p>
    </nav>
    <main class="print-paper" data-print-document="{{ $kind }}">
        <header class="print-header">
            <p class="print-brand">PITMETRIC <span>{{ $workspace->name }}</span></p>
            <h1>{{ __('prints.titles.'.$kind) }}</h1>
            <p>{{ $reference }} · {{ __('prints.generated') }} {{ $generatedAt->format('d/m/Y H:i') }} · {{ __('prints.timezone') }}</p>
        </header>
        {{ $slot }}
        <section class="print-handwriting" aria-label="{{ __('prints.handwriting') }}">
            <h2>{{ __('prints.handwriting') }}</h2>
            <div></div><div></div><div></div><div></div>
        </section>
        <footer class="print-footer">PitMetric · {{ $workspace->name }} · {{ $reference }} · {{ __('prints.recorded_only') }}</footer>
    </main>
</body>
</html>
