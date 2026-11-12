<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="theme-color" content="#101b20">
    <meta name="description" content="Un piccolo laboratorio di corse. Progetta la tua monoposto, ascolta i piloti e goditi la gara, al tuo ritmo.">
    <title>Formula Lab · PitMetric</title>
    <link rel="icon" href="/brand/pitmetric-app-icon-dark.svg">
    <link rel="stylesheet" href="/game_assets/formula-lab/style.css?v=1">
    <script type="module" src="/game_assets/formula-lab/app.mjs?v=1"></script>
</head>
<body>
    <a class="skip" href="#content">Vai al gioco</a>
    <header class="topbar">
        <a class="brand" href="/formula-lab" aria-label="Formula Lab, riprendi il gioco"><span class="brand-mark">F<span>↗</span></span><span>FORMULA <b>LAB</b><small>by PitMetric · un piccolo laboratorio di corse</small></span></a>
        <div class="top-actions"><span id="save-status" role="status">Salvataggio su questo dispositivo</span><button type="button" class="icon-button" data-action="help" aria-label="Come si gioca">?</button><button type="button" class="icon-button" data-action="settings" aria-label="Salvataggi e impostazioni">⚙</button></div>
    </header>
    <div id="app"><main id="content"><p class="loading">Apriamo il garage…</p></main></div>
    <noscript><p class="notice">Attiva JavaScript per aprire il laboratorio.</p></noscript>
    <dialog id="dialog" aria-labelledby="dialog-title"><div id="dialog-body"></div></dialog>
    <div id="toast" class="toast" role="status" aria-live="polite"></div>
</body>
</html>
