<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Open Wheel Career 26 - PitMetric</title>
    <link rel="stylesheet" href="/game_assets/game_dev.css?v=1">
</head>
<body>
<div class="game-shell" id="gameApp">
    <header class="topbar">
        <div class="brand">
            <span class="brand-mark">PM</span>
            <div>
                <strong>Open Wheel Career 26</strong>
                <small>PitMetric experimental F1 / F2 career simulator</small>
            </div>
        </div>
        <div class="topbar-actions">
            <span class="save-state" id="saveState">salvataggio locale</span>
            <button class="button button-secondary" id="rulesButton" type="button">Regole 2026</button>
            <button class="button button-danger" id="resetButton" type="button">Nuova carriera</button>
        </div>
    </header>

    <main class="dashboard">
        <aside class="side-column">
            <section class="panel profile-panel">
                <div class="profile-main">
                    <div class="avatar" id="avatar">--</div>
                    <div>
                        <strong id="playerName">Nuovo profilo</strong>
                        <span id="playerRole">Pilota o ingegnere</span>
                    </div>
                </div>
                <div class="overall-row">
                    <div class="overall" id="overallRing"><strong id="overallValue">--</strong></div>
                    <div class="overall-meta">
                        <span>Reputazione <strong id="reputationValue">--</strong></span>
                        <span>Categoria <strong id="seriesValue">--</strong></span>
                        <span>Weekend <strong id="weekendsValue">0</strong></span>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div><span class="kicker">Progressione</span><h2>Abilita</h2></div>
                    <span class="tag" id="skillOverall">OVR --</span>
                </div>
                <div class="panel-body skill-list" id="skillsList"></div>
            </section>

            <section class="panel">
                <div class="panel-header"><div><span class="kicker">Human performance</span><h2>Rapporti</h2></div></div>
                <div class="panel-body relationship-list" id="relationshipPanel"></div>
            </section>
        </aside>

        <section class="main-column">
            <section class="panel hero-panel">
                <div class="hero-copy">
                    <span class="kicker" id="heroKicker">Career hub</span>
                    <h1 id="heroTitle">Costruisci la tua carriera.</h1>
                    <p id="heroDescription">Il contratto, la fiducia del team e il rating dipendono dalle decisioni prese durante ogni weekend.</p>
                    <div class="tag-row" id="heroTags"></div>
                </div>
                <div class="hero-car" aria-hidden="true">
                    <div class="hero-wing"></div><div class="hero-nose"></div><div class="hero-cockpit"></div><div class="hero-rear"></div>
                </div>
            </section>

            <section class="panel race-panel">
                <div class="session-strip" id="sessionStrip"></div>
                <div class="race-content">
                    <div class="race-toolbar">
                        <div><span class="kicker" id="liveLabel">Stand-by</span><h2 id="circuitName">Nessun weekend attivo</h2><small id="circuitMeta">Stagione 2026</small></div>
                        <div class="speed-buttons" id="speedButtons">
                            <button class="speed-button active" data-speed="1" type="button">1x</button>
                            <button class="speed-button" data-speed="4" type="button">4x</button>
                            <button class="speed-button" data-speed="12" type="button">12x</button>
                        </div>
                    </div>

                    <div class="track-stage">
                        <svg class="track-svg" viewBox="0 0 1000 600" role="img" aria-label="Circuito e auto in gara">
                            <path id="trackShadow" class="track-shadow"></path>
                            <path id="trackRoad" class="track-road"></path>
                            <path id="trackEdge" class="track-edge"></path>
                            <path id="trackGuide" class="track-guide"></path>
                            <g id="carsLayer"></g>
                        </svg>
                        <div class="race-hud">
                            <span id="raceState">STAND-BY</span>
                            <strong><b id="lapValue">0</b> / <b id="totalLapsValue">--</b></strong>
                            <div><span>Posizione</span><b id="positionValue">--</b></div>
                            <div><span>Gap leader</span><b id="gapValue">--</b></div>
                        </div>
                    </div>

                    <div class="race-controls" id="raceControls">
                        <button class="race-control active" data-control="pace" type="button"><span>Ritmo</span><strong id="paceControlLabel">Bilanciato</strong></button>
                        <button class="race-control" data-control="attack" type="button"><span>Duello</span><strong>Attacca</strong></button>
                        <button class="race-control" data-control="overtake" type="button"><span>Energia</span><strong>Overtake Mode</strong></button>
                        <button class="race-control" data-control="pit" type="button"><span>Strategia</span><strong>Box questo giro</strong></button>
                    </div>

                    <div class="telemetry-grid">
                        <div><span>Gomma</span><strong id="tyreTelemetry">--</strong></div>
                        <div><span>Usura</span><strong id="wearTelemetry">--</strong></div>
                        <div><span>Energia</span><strong id="energyTelemetry">--</strong></div>
                        <div><span>Auto</span><strong id="carTelemetry">--</strong></div>
                    </div>
                </div>
                <div class="weekend-action" id="weekendAction"></div>
            </section>

            <section class="panel timing-panel">
                <div class="panel-header"><div><span class="kicker">Live timing</span><h2>Classifica sessione</h2></div><span class="tag" id="fieldCount">-- auto</span></div>
                <div class="timing-scroll">
                    <table class="timing-table">
                        <thead><tr><th>Pos</th><th>Pilota</th><th>Team</th><th>Gomma</th><th>Gap</th></tr></thead>
                        <tbody id="timingBody"></tbody>
                    </table>
                </div>
            </section>

            <section class="panel calendar-panel">
                <div class="panel-header"><div><span class="kicker">Calendario ufficiale</span><h2 id="calendarTitle">Stagione 2026</h2></div></div>
                <div class="calendar-list" id="calendarList"></div>
            </section>
        </section>

        <aside class="right-column">
            <section class="panel">
                <div class="panel-header"><div><span class="kicker">Pit wall</span><h2>Strategia e meteo</h2></div><span class="tag" id="weatherLabel">Asciutto</span></div>
                <div class="panel-body strategy-list">
                    <label><span>Prossima gomma</span><select id="nextTyre"><option value="soft">Soft</option><option value="medium" selected>Medium</option><option value="hard">Hard</option><option value="inter">Intermediate</option><option value="wet">Wet</option></select></label>
                    <label><span>Finestra box</span><select id="pitWindow"><option value="early">Anticipa</option><option value="normal" selected>Normale</option><option value="late">Estendi</option></select></label>
                    <label><span>Rischio strategico</span><select id="riskMode"><option value="safe">Conservativo</option><option value="balanced" selected>Bilanciato</option><option value="attack">Aggressivo</option></select></label>
                    <div class="forecast" id="forecast"></div>
                </div>
            </section>

            <section class="panel radio-panel">
                <div class="panel-header"><div><span class="kicker">Radio</span><h2 id="radioTitle">Pilota e ingegnere</h2></div><span class="tag" id="trustTag">Fiducia --</span></div>
                <div class="radio-log" id="radioLog"></div>
                <div class="radio-choices" id="radioChoices"></div>
            </section>

            <section class="panel">
                <div class="panel-header"><div><span class="kicker">Race control</span><h2>Eventi pista</h2></div></div>
                <div class="event-feed" id="eventFeed"></div>
            </section>
        </aside>
    </main>
</div>

<div class="overlay" id="careerWizard">
    <div class="wizard">
        <div class="wizard-header">
            <div><span class="kicker">Nuova carriera 2026</span><h1>Crea il tuo personaggio</h1><p>Scegli il ruolo, costruisci le abilita e lascia che il mercato F1 / F2 valuti il tuo profilo e la stagione precedente.</p></div>
            <div class="steps"><span class="active"></span><span></span><span></span></div>
        </div>
        <div class="wizard-body">
            <section id="wizardStep1">
                <div class="role-grid">
                    <button class="role-card selected" data-role="driver" type="button"><strong>Pilota</strong><span>Ritmo, sorpassi, gomme, energia, feedback e fiducia con il tuo ingegnere.</span></button>
                    <button class="role-card" data-role="engineer" type="button"><strong>Ingegnere di gara</strong><span>Telemetria, strategia, meteo, pit stop e comunicazione sotto pressione.</span></button>
                </div>
                <div class="form-grid">
                    <label><span>Nome</span><input id="careerName" value="Simone Buttice" maxlength="36"></label>
                    <label><span>Nazionalita</span><select id="careerNation"><option>Italia</option><option>Regno Unito</option><option>Francia</option><option>Germania</option><option>Spagna</option><option>Paesi Bassi</option><option>Brasile</option><option>Australia</option><option>Altro</option></select></label>
                    <label><span>Anno precedente</span><select id="careerHistory"><option value="rookie">Rookie</option><option value="f3top10">F3 Top 10</option><option value="f3podiums">F3 con podi</option><option value="f3champion">Campione F3</option><option value="f2top10">F2 Top 10</option><option value="f2winner">Vincitore in F2</option><option value="f2champion">Campione F2</option></select></label>
                </div>
            </section>
            <section id="wizardStep2" hidden>
                <span class="kicker">Distribuisci il potenziale</span><h2>Costruisci le abilita</h2>
                <div class="skill-builder" id="skillBuilder"></div>
                <div class="budget-row"><span>Punti disponibili</span><strong id="skillBudget">24</strong></div>
            </section>
            <section id="wizardStep3" hidden>
                <span class="kicker">Mercato 2026</span><h2>Offerte ricevute</h2>
                <div class="offers" id="offersList"></div>
            </section>
        </div>
        <div class="wizard-footer">
            <button class="button button-secondary" id="wizardBack" type="button" hidden>Indietro</button>
            <button class="button button-primary" id="wizardNext" type="button">Costruisci abilita</button>
        </div>
    </div>
</div>

<div class="modal" id="resultModal" hidden>
    <div class="modal-card">
        <div class="modal-header"><span class="kicker">Weekend completato</span><h2 id="resultTitle">Debrief</h2></div>
        <div class="modal-body">
            <div class="result-grid"><div><span>Risultato</span><strong id="resultPosition">P--</strong></div><div><span>Reputazione</span><strong id="resultReputation">--</strong></div><div><span>Fiducia team</span><strong id="resultTrust">--</strong></div></div>
            <div class="growth-list" id="growthList"></div>
            <div class="modal-actions"><button class="button button-primary" id="closeResult" type="button">Prossimo round</button></div>
        </div>
    </div>
</div>

<div class="modal" id="rulesModal" hidden>
    <div class="modal-card rules-card">
        <div class="modal-header"><span class="kicker">Regolamento simulato</span><h2>Regole 2026 implementate</h2></div>
        <div class="modal-body rules-copy">
            <p><strong>F1.</strong> Griglia 2026 a 22 vetture, qualifica Q1/Q2/Q3, sei weekend Sprint, punteggi GP e Sprint 2026, Overtake Mode e gestione energia al posto del vecchio DRS, obbligo di due specifiche slick differenti in una gara asciutta.</p>
            <p><strong>F2.</strong> Sprint con top 10 invertita dalla qualifica, punteggi Sprint e Feature 2026 e weekend speciale di Baku con due qualifiche e due Feature Race.</p>
            <p>Le durate sono compresse per il gameplay, mentre degrado, incidenti, Safety Car, pit stop, reputazione, relazioni e crescita delle abilita restano collegati tra loro.</p>
            <div class="modal-actions"><button class="button button-primary" id="closeRules" type="button">Chiudi</button></div>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>
<script src="/game_assets/game_dev_data.js?v=1"></script>
<script src="/game_assets/game_dev.js?v=1"></script>
</body>
</html>
