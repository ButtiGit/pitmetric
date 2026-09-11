<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="theme-color" content="#080a0d" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'PitMetric') : config('app.name', 'PitMetric') }}
</title>

<link rel="icon" href="/favicon.svg" type="image/svg+xml">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
