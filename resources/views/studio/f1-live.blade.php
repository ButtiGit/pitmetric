<x-layouts::app title="F1 Live | PitMetric">
    <div class="pitmetric-app min-h-full bg-pm-page px-3 py-5 sm:px-6 lg:px-8" data-f1-dashboard
         data-feed-url="{{ route('studio.f1-live.feed') }}"
         data-events-url="{{ route('studio.f1-live.events') }}"
         data-history-url="{{ route('studio.f1-live.history') }}">
        <div class="mx-auto max-w-[1450px] space-y-5">
            <header class="pm-panel overflow-hidden p-5 sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[.22em] text-pm-accent">
                            <span class="size-2 animate-pulse rounded-full bg-pm-accent"></span> PITMETRIC / RACE INTELLIGENCE
                        </div>
                        <h1 class="mt-3 text-3xl font-black tracking-tight text-pm-text sm:text-4xl">F1 <span class="text-pm-accent">Live Lab</span></h1>
                        <p class="mt-2 text-sm text-pm-text-secondary">Telemetrie FastF1, confronto piloti, cronologia, condizioni e Race Control.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="f1-state" role="status" aria-live="polite" class="rounded-full border border-pm-border px-3 py-2 text-xs font-bold text-pm-text-secondary">Connessione in corso</span>
                        <span id="f1-age" class="text-xs tabular-nums text-pm-muted">—</span>
                    </div>
                </div>
                <div class="mt-5 flex flex-wrap gap-2 border-t border-pm-border pt-5" role="tablist" aria-label="Modalità F1">
                    <button id="f1-live-tab" type="button" aria-selected="true" class="pm-race-button" data-f1-tab="live">● Diretta</button>
                    <button id="f1-history-tab" type="button" aria-selected="false" class="pm-ghost-button" data-f1-tab="history">Sessioni e replay</button>
                </div>
            </header>
            <div id="f1-warning" role="alert" class="hidden rounded-xl border border-pm-warning/40 bg-pm-warning-subtle px-5 py-3 text-sm text-pm-warning"></div>

            <div id="f1-live-view" class="space-y-5">
                <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="pm-panel p-4"><div class="text-xs uppercase tracking-wide text-pm-muted">Sessione attuale</div><p id="f1-session" class="mt-2 text-lg font-black text-pm-text">—</p></div>
                    <div class="pm-panel p-4"><div class="text-xs uppercase tracking-wide text-pm-muted">Giro</div><p id="f1-lap" class="mt-2 text-lg font-black text-pm-text">—</p></div>
                    <div class="pm-panel p-4"><div class="text-xs uppercase tracking-wide text-pm-muted">Pista</div><p id="f1-track" class="mt-2 text-lg font-black text-pm-text">—</p></div>
                    <div class="pm-panel p-4"><div class="text-xs uppercase tracking-wide text-pm-muted">Meteo</div><p id="f1-weather" class="mt-2 text-lg font-black text-pm-text">—</p></div>
                </section>

                <div class="grid gap-5 xl:grid-cols-[minmax(0,1.7fr)_minmax(290px,1fr)]">
                    <section class="pm-panel overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-pm-border p-5">
                            <div><h2 class="text-lg font-black text-pm-text">Timing tower</h2><p class="text-xs text-pm-muted">Posizione, distacchi, settori, gomme e dati vettura</p></div>
                            <button id="f1-refresh" class="pm-ghost-button text-xs" type="button">Aggiorna ora</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[780px] text-left text-xs sm:text-sm">
                                <thead class="bg-pm-subtle text-xs uppercase text-pm-muted"><tr>
                                    <th class="px-4 py-3">P</th><th class="px-3 py-3">Pilota</th><th class="px-3 py-3">Gap</th>
                                    <th class="px-3 py-3">Ultimo</th><th class="px-3 py-3">Best</th><th class="px-3 py-3">S1/S2/S3</th>
                                    <th class="px-3 py-3">Gomme</th><th class="px-3 py-3">Km/h</th>
                                </tr></thead>
                                <tbody id="f1-timing" class="divide-y divide-pm-border text-pm-text-secondary">
                                    <tr><td colspan="8" class="px-4 py-8 text-center">In attesa del segnale live…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    <section class="pm-panel p-5">
                        <h2 class="text-lg font-black text-pm-text">Race Control</h2>
                        <p class="mb-4 text-xs text-pm-muted">Bandiere, investigazioni, safety car e comunicati disponibili</p>
                        <div id="f1-messages" class="max-h-[420px] space-y-2 overflow-y-auto text-sm text-pm-text-secondary"><p>In attesa di messaggi.</p></div>
                    </section>
                </div>
            </div>

            <div id="f1-history-view" class="hidden space-y-5">
                <section class="pm-panel p-5 sm:p-6">
                    <h2 class="text-lg font-black text-pm-text">Analisi di una sessione conclusa</h2>
                    <p class="mt-1 text-xs text-pm-muted">La libreria FastF1 carica la telemetria storica: può essere disponibile con ritardo dopo la sessione.</p>
                    <form id="f1-history-form" class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                        <label class="text-xs font-semibold text-pm-text-secondary">Stagione
                            <input id="f1-year" type="number" min="2018" max="2100" value="{{ now('UTC')->year }}" class="mt-1 w-full rounded-lg border border-pm-border bg-pm-subtle px-3 py-2.5 text-sm text-pm-text">
                        </label>
                        <label class="text-xs font-semibold text-pm-text-secondary lg:col-span-2">Gran Premio
                            <select id="f1-event" required class="mt-1 w-full rounded-lg border border-pm-border bg-pm-subtle px-3 py-2.5 text-sm text-pm-text"><option value="">Caricamento calendario…</option></select>
                        </label>
                        <label class="text-xs font-semibold text-pm-text-secondary">Sessione
                            <select id="f1-hsession" class="mt-1 w-full rounded-lg border border-pm-border bg-pm-subtle px-3 py-2.5 text-sm text-pm-text">
                                <option value="R">Gara</option><option value="Q">Qualifiche</option><option value="S">Sprint</option>
                                <option value="SQ">Sprint Qualifying</option><option value="FP1">FP1</option><option value="FP2">FP2</option><option value="FP3">FP3</option>
                            </select>
                        </label>
                        <label class="text-xs font-semibold text-pm-text-secondary">Piloti (max 3)
                            <input id="f1-hdrivers" value="VER,NOR" maxlength="30" placeholder="VER,NOR,LEC" class="mt-1 w-full rounded-lg border border-pm-border bg-pm-subtle px-3 py-2.5 text-sm uppercase text-pm-text">
                        </label>
                        <label class="text-xs font-semibold text-pm-text-secondary">Giro
                            <input id="f1-hlap" value="fastest" placeholder="fastest, latest, 12" class="mt-1 w-full rounded-lg border border-pm-border bg-pm-subtle px-3 py-2.5 text-sm text-pm-text">
                        </label>
                        <div class="sm:col-span-2 lg:col-span-6 flex flex-wrap items-center gap-3">
                            <button type="submit" id="f1-compare" class="pm-race-button">Confronta telemetrie</button>
                            <span id="f1-history-status" class="text-xs text-pm-muted" aria-live="polite">Seleziona una gara.</span>
                        </div>
                    </form>
                </section>
                <div id="f1-history-summary" class="grid gap-3 sm:grid-cols-3"></div>
            </div>

            <section class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(280px,1fr)]">
                <div class="pm-panel p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div><h2 class="text-lg font-black text-pm-text">Oscilloscopio telemetria</h2>
                            <p class="text-xs text-pm-muted">Campioni del feed / giro selezionato; niente interpolazioni inventate</p></div>
                        <label class="text-xs font-semibold text-pm-muted">Canale
                            <select id="f1-channel" class="ml-2 rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 text-sm text-pm-text">
                                <option value="speed">Velocità km/h</option><option value="rpm">Regime RPM</option>
                                <option value="throttle">Acceleratore %</option><option value="brake">Freno on/off</option>
                                <option value="gear">Marcia</option><option value="drs">DRS (codice)</option>
                                <option value="distance">Distanza m (archivio)</option>
                            </select>
                        </label>
                    </div>
                    <div id="f1-driver-filters" class="mt-4 flex flex-wrap gap-2"></div>
                    <div class="mt-4 overflow-hidden rounded-xl border border-pm-border bg-pm-subtle p-2">
                        <canvas id="f1-chart" height="340" width="1000" class="h-[280px] w-full" role="img" aria-label="Andamento della telemetria selezionata"></canvas>
                    </div>
                    <p id="f1-chart-caption" class="mt-2 text-xs text-pm-muted">Seleziona un pilota per visualizzare i campioni.</p>
                </div>
                <div class="pm-panel p-5 sm:p-6">
                    <h2 class="text-lg font-black text-pm-text">Posizione vetture</h2>
                    <p class="text-xs text-pm-muted">Coordinate X/Y quando trasmesse; non è una mappa ufficiale del circuito.</p>
                    <div class="mt-4 overflow-hidden rounded-xl border border-pm-border bg-pm-subtle p-2">
                        <canvas id="f1-position" width="500" height="440" class="h-[280px] w-full" role="img" aria-label="Coordinate relative delle vetture"></canvas>
                    </div>
                    <p id="f1-position-caption" class="mt-2 text-xs text-pm-muted">Posizioni non ancora disponibili.</p>
                </div>
            </section>

            <details class="pm-panel p-5">
                <summary class="cursor-pointer text-sm font-bold text-pm-text">Dati grezzi e canali disponibili (per analisi avanzata)</summary>
                <p id="f1-channels" class="mt-3 text-xs text-pm-muted"></p>
                <pre id="f1-raw" class="mt-3 max-h-[450px] overflow-auto whitespace-pre-wrap break-words text-xs text-pm-text-secondary">Nessun dato ricevuto.</pre>
            </details>
            <p class="px-2 text-xs text-pm-muted">I segnali sono forniti da FastF1 e dalla sorgente live F1 quando accessibile. Le letture possono essere ritardate, incomplete o assenti. La modalità diretta non simula dati.</p>
        </div>
    </div>
    <script>
    (() => {
        const root = document.querySelector('[data-f1-dashboard]');
        if (!root || root.dataset.f1Initialized) return;
        root.dataset.f1Initialized = '1';
        const $ = id => root.querySelector('#' + id);
        const escapeHtml = value => String(value ?? '—').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const metricLabels = {speed:'km/h',rpm:'RPM',throttle:'%',brake:'on/off',gear:'gear',drs:'DRS',distance:'m'};
        const palette = ['#E10600','#12adff','#f7ba4c'];
        let mode = 'live', current = null, replay = null, loading = false, calendar = [], selected = [];
        let interval = null;
        const requests = new AbortController();
        const cleanup = () => {
            clearInterval(interval);
            requests.abort();
            document.removeEventListener('livewire:navigating', cleanup);
            window.removeEventListener('pagehide', cleanup);
        };
        const status = (message, okay) => {
            $('f1-state').textContent = message;
            $('f1-state').style.color = okay ? '#48b98a' : '#e6b85c';
        };
        const field = (obj, key) => obj && typeof obj === 'object' ? obj[key] : undefined;
        const val = item => item && typeof item === 'object' && 'Value' in item ? item.Value : item;
        const maybe = item => { const data = val(item); return data == null || data === '' ? '—' : String(data); };
        const driverLabel = (driver, number) => maybe(driver.Tla && driver.Tla !== '' ? driver.Tla : (driver.BroadcastName || driver.FullName || number));
        const timeOr = entry => maybe(val(entry));

        async function json(url) {
            const response = await fetch(url, {signal:requests.signal,credentials:'same-origin',headers:{'Accept':'application/json'},cache:'no-store'});
            const body = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(body.message || ('HTTP ' + response.status));
            return body;
        }
        function switchMode(next) {
            mode = next;
            $('f1-live-view').classList.toggle('hidden', next !== 'live');
            $('f1-history-view').classList.toggle('hidden', next !== 'history');
            for (const button of root.querySelectorAll('[data-f1-tab]')) {
                const active = button.dataset.f1Tab === next;
                button.className = active ? 'pm-race-button' : 'pm-ghost-button';
                button.setAttribute('aria-selected', active ? 'true' : 'false');
            }
            selected = [];
            render();
        }
        root.querySelectorAll('[data-f1-tab]').forEach(button => button.addEventListener('click', () => switchMode(button.dataset.f1Tab)));

        function filterDrivers(names) {
            const filter = $('f1-driver-filters');
            const existing = selected.filter(d => names.includes(d));
            selected = existing.length ? existing : names.slice(0, Math.min(2, names.length));
            filter.innerHTML = '';
            names.forEach((name, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = name + (selected.includes(name) ? ' (attivo)' : '');
                button.style.borderColor = selected.includes(name) ? palette[index % 3] : '';
                button.className = 'rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 text-xs font-bold text-pm-text';
                button.setAttribute('aria-pressed', String(selected.includes(name)));
                button.addEventListener('click', () => {
                    selected = selected.includes(name) ? selected.filter(x => x !== name) : [...selected, name].slice(-3);
                    render();
                });
                filter.appendChild(button);
            });
        }
        function canvasContext(canvas) {
            const ratio = Math.min(window.devicePixelRatio || 1, 2);
            const width = Math.max(canvas.clientWidth, 1), height = Math.max(canvas.clientHeight, 1);
            canvas.width = Math.round(width * ratio);
            canvas.height = Math.round(height * ratio);
            const ctx = canvas.getContext('2d');
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            return {ctx, width, height};
        }
        function drawGraph(canvas, datasets, channel, caption) {
            const {ctx, width, height} = canvasContext(canvas);
            ctx.clearRect(0,0,width,height);
            const pad = {l:60,r:22,t:25,b:35};
            const all = datasets.flatMap(x => x.samples.filter(p => p[channel] != null && p.t != null && Number.isFinite(Number(p[channel])) && Number.isFinite(Number(p.t))));
            if (!all.length) { caption.textContent = 'Nessun campione disponibile per questo canale.'; return; }
            const values = all.map(x => Number(x[channel]));
            const times = all.map(x => Number(x.t));
            let bottom = Math.min(...values), top = Math.max(...values);
            if (top === bottom) top += 1;
            const minT = Math.min(...times), maxT = Math.max(...times);
            const xAxis = t => pad.l + ((t-minT)/(maxT-minT || 1))*(width-pad.l-pad.r);
            const yAxis = y => height-pad.b - ((y-bottom)/(top-bottom))*(height-pad.t-pad.b);
            ctx.lineWidth = 1;
            ctx.font = '11px system-ui';
            for (let i=0;i<=4;i++) {
                const y = pad.t+(height-pad.t-pad.b)*(i/4);
                ctx.strokeStyle = 'rgba(145,154,168,.22)';
                ctx.beginPath();ctx.moveTo(pad.l,y);ctx.lineTo(width-pad.r,y);ctx.stroke();
                ctx.fillStyle = '#9ca3af';ctx.fillText((top-(top-bottom)*i/4).toFixed(1),5,y+5);
            }
            datasets.forEach((series,index) => {
                const points = series.samples.filter(p => p[channel] != null && p.t != null && Number.isFinite(Number(p[channel])) && Number.isFinite(Number(p.t)));
                ctx.beginPath(); ctx.strokeStyle = palette[index%3];ctx.lineWidth=2.5;
                points.forEach((p,i) => {const x=xAxis(Number(p.t)), y=yAxis(Number(p[channel])); if(i)ctx.lineTo(x,y);else ctx.moveTo(x,y);});
                ctx.stroke();
                ctx.fillStyle = palette[index%3];ctx.fillText(series.name,pad.l+index*115,15);
            });
            ctx.fillStyle = '#9ca3af'; ctx.fillText(minT.toFixed(1)+'s',pad.l,height-9);
            ctx.fillText(maxT.toFixed(1)+'s',width-pad.r-70,height-9);
            caption.textContent = datasets.length + ' piloti · ' + all.length + ' campioni visualizzati · ' + (metricLabels[channel] || channel) + ' · tempi relativi al campione';
        }
        function plotPosition(data) {
            const {ctx, width, height} = canvasContext($('f1-position'));
            ctx.clearRect(0,0,width,height);
            const coords = data.flatMap(x => x.positions.filter(p => p.x != null && p.y != null && Number.isFinite(Number(p.x)) && Number.isFinite(Number(p.y))));
            if (!coords.length) {
                $('f1-position-caption').textContent='Nessuna posizione X/Y disponibile.';
                return;
            }
            const minX = Math.min(...coords.map(x=>Number(x.x))), maxX = Math.max(...coords.map(x=>Number(x.x)));
            const minY = Math.min(...coords.map(x=>Number(x.y))), maxY = Math.max(...coords.map(x=>Number(x.y)));
            const scale = Math.min((width-80)/(maxX-minX || 1),(height-80)/(maxY-minY || 1));
            data.forEach((set,index) => {
                const points = set.positions.filter(p=>p.x != null && p.y != null && Number.isFinite(Number(p.x))&&Number.isFinite(Number(p.y)));
                ctx.strokeStyle=palette[index%3];ctx.fillStyle=palette[index%3];ctx.lineWidth=2;
                ctx.beginPath();
                points.forEach((p,i)=>{const x=40+(Number(p.x)-minX)*scale,y=height-40-(Number(p.y)-minY)*scale; i?ctx.lineTo(x,y):ctx.moveTo(x,y);});
                if(points.length>1)ctx.stroke();
                const last=points[points.length-1];
                if(last){const x=40+(Number(last.x)-minX)*scale,y=height-40-(Number(last.y)-minY)*scale;ctx.beginPath();ctx.arc(x,y,5,0,Math.PI*2);ctx.fill();ctx.font='bold 11px system-ui';ctx.fillText(set.name,x+8,y+4);}
            });
            $('f1-position-caption').textContent='Coordinate relative dei piloti selezionati; nessun tracciato ricostruito.';
        }
        function render() {
            const data = mode === 'live' ? current : replay;
            if (!data) return;
            const names = mode === 'live' ? Object.keys(data.drivers || {}) : Object.keys(data.drivers || {}).filter(n => data.drivers[n].telemetry);
            filterDrivers(names);
            const graph = [], positions = [];
            selected.forEach((name,index)=>{
                if(mode === 'live') {
                    const hist = data.history?.[name] || [];
                    const valid=hist.find(p=>Number.isFinite(Date.parse(p.utc)));
                    const base=valid ? Date.parse(valid.utc) : 0;
                    graph.push({name, samples:hist.map(p => ({...p,t:Number.isFinite(Date.parse(p.utc)) ? (Date.parse(p.utc)-base)/1000 : null}))});
                    const position = data.drivers?.[name]?.position;
                    positions.push({name,positions:position && position.x != null ? [position] : []});
                } else {
                    const lap = data.drivers?.[name];
                    graph.push({name,samples:lap?.telemetry || []});
                    positions.push({name,positions:lap?.position || []});
                }
            });
            drawGraph($('f1-chart'), graph, $('f1-channel').value, $('f1-chart-caption'));
            plotPosition(positions);
            $('f1-raw').textContent = JSON.stringify(data, null, 2).slice(0,150000);
            $('f1-channels').textContent = mode === 'live' ? 'Canali ricevuti: ' + (data.channels || []).join(' · ') : 'Canali FastF1: velocità, RPM, marcia, acceleratore, freno, DRS, distanza e X/Y/Z separati.';
        }
        function renderLive(data) {
            const age = data.age_seconds;
            status(data.live ? '● LIVE · FastF1' : '○ Nessuna sessione live', data.live);
            $('f1-age').textContent=age == null ? 'Nessun messaggio' : 'Ultimo messaggio '+age+' s fa';
            $('f1-warning').classList.toggle('hidden',Boolean(data.live));
            $('f1-warning').textContent = data.live ? '' : (data.error ? 'Feed non disponibile ('+data.error+'). ' : '') + 'Nessun segnale in diretta. La sessione potrebbe non essere in corso oppure la sorgente non è raggiungibile.';
            $('f1-session').textContent = maybe(data.session?.Name || data.session?.Meeting?.Name || data.session?.Type);
            $('f1-lap').textContent = maybe(data.lap_count?.CurrentLap) + ' / ' + maybe(data.lap_count?.TotalLaps);
            $('f1-track').textContent = maybe(data.track?.Message || data.track?.Status || data.session_status?.Status);
            $('f1-weather').textContent = maybe(data.weather?.AirTemp)+'° aria · '+maybe(data.weather?.TrackTemp)+'° pista';
            const driverList = Object.entries(data.drivers || {}).sort((a,b)=>(Number(a[1].timing?.Position)||99)-(Number(b[1].timing?.Position)||99));
            $('f1-timing').innerHTML = driverList.length ? driverList.map(([number,d])=>{
                const timing=d.timing || {}, compounds=d.stints?.Stints || [], stint=Object.values(compounds).at(-1) || {};
                const sectors=Object.values(timing.Sectors || {}).map(s=>maybe(s?.Value)).join(' / ');
                return '<tr class="hover:bg-pm-subtle"><td class="px-4 py-3 font-black text-pm-text">'+escapeHtml(maybe(timing.Position))+'</td>'
                    +'<td class="px-3 py-3 font-bold text-pm-text">'+escapeHtml(driverLabel(d,number))+' <span class="text-pm-muted">#'+escapeHtml(number)+'</span></td>'
                    +'<td class="px-3 py-3">'+escapeHtml(maybe(timing.GapToLeader || timing.IntervalToPositionAhead))+'</td>'
                    +'<td class="px-3 py-3 tabular-nums">'+escapeHtml(maybe(timing.LastLapTime))+'</td>'
                    +'<td class="px-3 py-3 tabular-nums">'+escapeHtml(maybe(timing.BestLapTime))+'</td>'
                    +'<td class="px-3 py-3 tabular-nums">'+escapeHtml(sectors || '—')+'</td>'
                    +'<td class="px-3 py-3">'+escapeHtml(maybe(stint.Compound))+'</td>'
                    +'<td class="px-3 py-3 font-semibold text-pm-text">'+escapeHtml(maybe(d.car?.speed))+'</td></tr>';
            }).join('') : '<tr><td colspan="8" class="px-4 py-8 text-center">Nessun pilota trasmesso. Nessun dato simulato.</td></tr>';
            const messages=(data.race_control || []).slice(-18).reverse();
            $('f1-messages').innerHTML=messages.length ? messages.map(m=>'<article class="rounded-lg border border-pm-border bg-pm-subtle p-3"><span class="text-xs font-bold text-pm-accent">'+escapeHtml(m.Category || m.Flag || 'FIA')+'</span><p class="mt-1">'+escapeHtml(m.Message || '—')+'</p></article>').join('') : '<p>Nessun messaggio disponibile.</p>';
            if(mode==='live') render();
        }
        async function refreshLive() {
            if (loading) return;
            loading=true;
            try {
                const picks=selected.filter(x=>/^\d{1,3}$/.test(x));
                const data=await json(root.dataset.feedUrl + (picks.length ? '?drivers='+encodeURIComponent(picks.join(',')) : ''));
                current=data;
                renderLive(data);
            } catch(error) {
                if (requests.signal.aborted) return;
                status('○ FastF1 non raggiungibile',false);
                $('f1-warning').textContent = error.message;
                $('f1-warning').classList.remove('hidden');
                $('f1-age').textContent='Offline';
            } finally {loading=false;}
        }
        async function loadCalendar(){
            const year=Number($('f1-year').value);
            try {
                const data=await json(root.dataset.eventsUrl+'?year='+encodeURIComponent(year));
                calendar=data.events || [];
                const select=$('f1-event'); select.innerHTML='';
                const now=Date.now();
                const recent=[...calendar].reverse().find(e => e.sessions?.some(s => s.date_utc && new Date(s.date_utc).getTime()<now));
                calendar.forEach(e=>{const option=new Option('R'+e.round+' · '+e.name,String(e.round));select.add(option);});
                if(recent) select.value=String(recent.round);
                $('f1-history-status').textContent=calendar.length+' GP disponibili nel calendario '+year;
            }catch(error){if (!requests.signal.aborted) $('f1-history-status').textContent=error.message;}
        }
        async function loadReplay(event){
            event.preventDefault();
            const year=Number($('f1-year').value), round=Number($('f1-event').value);
            const session=$('f1-hsession').value;
            const drivers=$('f1-hdrivers').value.toUpperCase().replace(/\s/g,'');
            const lap=$('f1-hlap').value.trim() || 'fastest';
            if (!round || !/^[A-Z0-9]{1,3}(,[A-Z0-9]{1,3}){0,2}$/.test(drivers) || !/^(fastest|latest|[1-9][0-9]?)$/.test(lap)) {
                $('f1-history-status').textContent='Controlla GP, piloti (es. VER,NOR) e giro.';return;
            }
            $('f1-compare').disabled=true;
            $('f1-history-status').textContent='FastF1 sta caricando telemetrie e giri…';
            try{
                const q=new URLSearchParams({year,round,session,drivers,lap});
                let attempts=0;
                do {
                    if (requests.signal.aborted) return;
                    replay=await json(root.dataset.historyUrl+'?'+q.toString());
                    if (replay.pending) {
                        $('f1-history-status').textContent='FastF1 sta elaborando la sessione…';
                        await new Promise(resolve=>setTimeout(resolve,3000));
                    }
                } while (replay.pending && ++attempts<45);
                if (replay.pending) throw new Error('Elaborazione in corso: riprova tra poco.');
                selected=[];
                $('f1-history-status').textContent=replay.event+' · '+session+' · dati caricati';
                $('f1-history-summary').innerHTML=Object.values(replay.drivers).map(d=>'<div class="pm-panel p-4"><div class="text-sm font-bold text-pm-text">'+escapeHtml(d.driver || 'Pilota')+'</div><p class="mt-1 text-xl font-black text-pm-accent">'+escapeHtml(d.lap_seconds ? d.lap_seconds.toFixed(3)+' s' : '—')+'</p><p class="text-xs text-pm-muted">Giro '+escapeHtml(d.lap)+' · '+escapeHtml(d.compound || d.error)+'</p></div>').join('');
                render();
            }catch(error){if (!requests.signal.aborted) $('f1-history-status').textContent=error.message;}
            finally{$('f1-compare').disabled=false;}
        }
        $('f1-channel').addEventListener('change',render);
        $('f1-refresh').addEventListener('click',refreshLive);
        $('f1-year').addEventListener('change',loadCalendar);
        $('f1-history-form').addEventListener('submit',loadReplay);
        refreshLive();
        loadCalendar();
        interval=setInterval(()=>{if(!document.hidden && mode === 'live')refreshLive();},5000);
        window.addEventListener('pagehide',cleanup,{once:true});
        document.addEventListener('livewire:navigating',cleanup,{once:true});
    })();
    </script>
</x-layouts::app>
