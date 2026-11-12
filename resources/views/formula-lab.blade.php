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
        <a class="brand" href="/formula-lab" aria-label="Formula Lab, riprendi il gioco"><span class="brand-mark">F<svg viewBox="0 0 16 16" width="13" height="13" aria-hidden="true"><path d="M4 12 12 4M4 4h8v8" fill="none" stroke="currentColor" stroke-width="2"/></svg></span><span>FORMULA <b>LAB</b><small>by PitMetric · un piccolo laboratorio di corse</small></span></a>
        <div class="top-actions"><span id="save-status" role="status">Salvataggio su questo dispositivo</span><button type="button" class="icon-button" data-action="help" aria-label="Come si gioca">?</button><button type="button" class="icon-button" data-action="settings" aria-label="Salvataggi e impostazioni"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M10 3h4l1 3 3 1 3 3v4l-3 1-1 3-3 3h-4l-1-3-3-1-3-3v-4l3-1 1-3z" fill="none" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></button></div>
    </header>
    <div id="app"><main id="content"><p class="loading">Apriamo il garage…</p></main></div>
    <noscript><p class="notice">Attiva JavaScript per aprire il laboratorio.</p></noscript>
    <dialog id="dialog" aria-labelledby="dialog-title"><div id="dialog-body"></div></dialog>
    <div id="toast" class="toast" role="status" aria-live="polite"></div>
</body>
</html>
