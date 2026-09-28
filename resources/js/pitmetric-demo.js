const demoReadOnly = document.querySelector('meta[name="pitmetric-demo-read-only"]')?.content === 'true';

const showcaseState = () => ({
    version: 2,
    vehicles: [
        { id: 'demo-vehicle-27', name: 'Kart #27', type: 'Kart', manufacturer: 'Tony Kart', model: 'Racer 401 RR', year: '2026' },
        { id: 'demo-vehicle-86', name: 'GT Cup #86', type: 'Car', manufacturer: 'Porsche', model: '911 GT3 Cup (992)', year: '2025' },
        { id: 'demo-vehicle-12', name: 'Track Prototype #12', type: 'Prototype', manufacturer: 'PitMetric Labs', model: 'PM-R01', year: '2026' },
    ],
    components: [
        { id: 'demo-engine-01', name: 'Engine #01', type: 'Engine', serial: 'ENG-2026-001', metric: 'runtime', usage: 11.8 },
        { id: 'demo-chain-03', name: 'Chain #03', type: 'Transmission', serial: 'CH-003', metric: 'distance', usage: 286.4 },
        { id: 'demo-brakes-02', name: 'Front brake set #02', type: 'Brakes', serial: 'BRK-F-002', metric: 'sessions', usage: 9 },
        { id: 'demo-tyres-07', name: 'Slick set #07', type: 'Tyres', serial: 'SLK-07', metric: 'cycles', usage: 164 },
        { id: 'demo-gearbox-02', name: 'Sequential gearbox #02', type: 'Gearbox', serial: 'GBX-002', metric: 'runtime', usage: 18.6 },
        { id: 'demo-damper-fl', name: 'Damper FL', type: 'Suspension', serial: 'DMP-FL-14', metric: 'distance', usage: 742.7 },
        { id: 'demo-battery-01', name: 'Data logger battery', type: 'Electronics', serial: 'BAT-DL-01', metric: 'sessions', usage: 16 },
    ],
    configurations: [
        { id: 'demo-config-kart-race', name: 'Kart Race Build', vehicleId: 'demo-vehicle-27', componentIds: ['demo-engine-01', 'demo-chain-03', 'demo-brakes-02', 'demo-tyres-07'], version: 4 },
        { id: 'demo-config-gt-dry', name: 'GT3 Dry Setup', vehicleId: 'demo-vehicle-86', componentIds: ['demo-gearbox-02', 'demo-damper-fl', 'demo-battery-01'], version: 7 },
        { id: 'demo-config-proto-test', name: 'Prototype Test Package', vehicleId: 'demo-vehicle-12', componentIds: ['demo-damper-fl', 'demo-battery-01'], version: 3 },
    ],
    circuits: [
        { id: 'demo-circuit-busca', name: 'Busca Kart Planet', layout: 'Main layout', lengthMeters: 1250 },
        { id: 'demo-circuit-mugello', name: 'Mugello Circuit', layout: 'Grand Prix', lengthMeters: 5245 },
        { id: 'demo-circuit-imola', name: 'Autodromo di Imola', layout: 'Grand Prix', lengthMeters: 4909 },
        { id: 'demo-circuit-varano', name: 'Autodromo di Varano', layout: 'Full', lengthMeters: 2350 },
    ],
    sessions: [
        { id: 'demo-session-1', date: '2026-09-12', configurationId: 'demo-config-kart-race', circuitId: 'demo-circuit-busca', laps: 34, durationMinutes: 31, distanceMeters: 42500 },
        { id: 'demo-session-2', date: '2026-09-13', configurationId: 'demo-config-kart-race', circuitId: 'demo-circuit-busca', laps: 28, durationMinutes: 25, distanceMeters: 35000 },
        { id: 'demo-session-3', date: '2026-09-18', configurationId: 'demo-config-gt-dry', circuitId: 'demo-circuit-mugello', laps: 21, durationMinutes: 47, distanceMeters: 110145 },
        { id: 'demo-session-4', date: '2026-09-19', configurationId: 'demo-config-gt-dry', circuitId: 'demo-circuit-mugello', laps: 18, durationMinutes: 40, distanceMeters: 94410 },
        { id: 'demo-session-5', date: '2026-09-23', configurationId: 'demo-config-proto-test', circuitId: 'demo-circuit-varano', laps: 42, durationMinutes: 58, distanceMeters: 98700 },
        { id: 'demo-session-6', date: '2026-09-26', configurationId: 'demo-config-proto-test', circuitId: 'demo-circuit-imola', laps: 14, durationMinutes: 36, distanceMeters: 68726 },
    ],
    maintenance: [
        { id: 'demo-maint-1', componentId: 'demo-chain-03', date: '2026-09-13', action: 'Tension and lubrication', notes: 'Checked after final heat. Wear within tolerance.', resetUsage: false },
        { id: 'demo-maint-2', componentId: 'demo-engine-01', date: '2026-09-14', action: 'Top-end inspection', notes: 'Compression and plug checked; no replacement required.', resetUsage: false },
        { id: 'demo-maint-3', componentId: 'demo-brakes-02', date: '2026-09-19', action: 'Pad replacement', notes: 'New front pads fitted before afternoon session.', resetUsage: true },
        { id: 'demo-maint-4', componentId: 'demo-damper-fl', date: '2026-09-24', action: 'Damper service', notes: 'Oil replaced and rebound calibration verified.', resetUsage: true },
        { id: 'demo-maint-5', componentId: 'demo-battery-01', date: '2026-09-26', action: 'Battery health check', notes: 'Capacity 91%, connectors cleaned.', resetUsage: false },
    ],
    expenses: [
        { id: 'demo-expense-1', date: '2026-09-12', category: 'Track', vehicleId: 'demo-vehicle-27', amountCents: 9500, note: 'Track day entry' },
        { id: 'demo-expense-2', date: '2026-09-12', category: 'Fuel', vehicleId: 'demo-vehicle-27', amountCents: 4200, note: 'Race fuel' },
        { id: 'demo-expense-3', date: '2026-09-13', category: 'Parts', vehicleId: 'demo-vehicle-27', amountCents: 6850, note: 'Chain and sprocket consumables' },
        { id: 'demo-expense-4', date: '2026-09-18', category: 'Track', vehicleId: 'demo-vehicle-86', amountCents: 39000, note: 'Mugello test day' },
        { id: 'demo-expense-5', date: '2026-09-19', category: 'Maintenance', vehicleId: 'demo-vehicle-86', amountCents: 18450, note: 'Brake pads and workshop consumables' },
        { id: 'demo-expense-6', date: '2026-09-23', category: 'Fuel', vehicleId: 'demo-vehicle-12', amountCents: 12800, note: 'Test fuel' },
        { id: 'demo-expense-7', date: '2026-09-24', category: 'Maintenance', vehicleId: 'demo-vehicle-12', amountCents: 27500, note: 'Damper service' },
        { id: 'demo-expense-8', date: '2026-09-26', category: 'Other', vehicleId: '', amountCents: 7200, note: 'Data acquisition supplies' },
    ],
});

const root = document.getElementById('pitmetric-demo');

if (root) {
    const userId = root.dataset.user;
    const section = root.dataset.section;
    const copy = JSON.parse(root.dataset.copy || '{}');
    const it = copy.locale === 'it';
    const readOnly = root.dataset.readOnly === 'true' || demoReadOnly;
    const storageKey = `pitmetric:manager:v2:${userId}`;
    const legacyStorageKey = `pitmetric:demo:v2:${userId}`;

    const emptyState = () => ({
        version: 2,
        vehicles: [], components: [], configurations: [], circuits: [], sessions: [], maintenance: [], expenses: [],
    });

    const load = () => {
        if (readOnly) return showcaseState();

        try {
            const stored = localStorage.getItem(storageKey) || localStorage.getItem(legacyStorageKey) || '{}';
            const loaded = { ...emptyState(), ...JSON.parse(stored) };
            if (!localStorage.getItem(storageKey) && stored !== '{}') localStorage.setItem(storageKey, JSON.stringify(loaded));
            return loaded;
        } catch {
            return emptyState();
        }
    };

    let state = load();
    const save = () => {
        if (!readOnly) localStorage.setItem(storageKey, JSON.stringify(state));
    };
    const id = () => `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 7)}`;
    const esc = (value = '') => String(value).replace(/[&<>'"]/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;' }[char]));
    const money = cents => new Intl.NumberFormat(it ? 'it-IT' : 'en-GB', { style:'currency', currency:'EUR' }).format((cents || 0) / 100);
    const dateFmt = value => value ? new Intl.DateTimeFormat(it ? 'it-IT' : 'en-GB').format(new Date(`${value}T12:00:00`)) : '—';
    const nameOf = (items, itemId, fallback = '—') => items.find(item => item.id === itemId)?.name || fallback;
    const panel = content => `<section class="pm-panel min-w-0 p-5 sm:p-6">${content}</section>`;
    const editablePanel = content => readOnly ? '' : panel(content);
    const empty = text => `<div class="rounded-xl border border-dashed border-pm-border p-7 text-center text-sm text-pm-muted">${text}</div>`;
    const rowActions = (type, itemId) => readOnly ? '' : `<button type="button" class="shrink-0 text-xs font-semibold text-pm-danger hover:underline" data-delete="${type}" data-id="${itemId}">${it ? 'Elimina' : 'Delete'}</button>`;
    const heading = (eyebrow, title, description) => `<div class="min-w-0"><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">${eyebrow}</p><h1 class="mt-2 break-words text-2xl font-black tracking-[-0.03em] text-pm-text sm:text-3xl">${title}</h1><p class="mt-2 max-w-3xl break-words text-sm leading-6 text-pm-text-secondary">${description}</p></div>`;
    const input = (name, label, type='text', extra='') => `<label class="grid min-w-0 gap-2"><span class="pm-label break-words">${label}</span><input class="pm-input min-w-0" name="${name}" type="${type}" ${extra}></label>`;
    const select = (name, label, options, extra='') => `<label class="grid min-w-0 gap-2"><span class="pm-label break-words">${label}</span><select class="pm-input min-w-0" name="${name}" ${extra}>${options}</select></label>`;
    const primary = label => `<button class="pm-race-button max-w-full whitespace-normal text-center" type="submit">${label}</button>`;
    const readOnlyBanner = () => readOnly ? `<section class="pm-panel border-pm-accent/35 bg-pm-accent-subtle p-4 sm:p-5" role="status"><div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center"><span class="w-fit shrink-0 rounded-full bg-pm-accent px-3 py-1 text-[10px] font-black uppercase tracking-[0.14em] text-white">Demo</span><div class="min-w-0"><h2 class="break-words font-black text-pm-text">${it ? 'Modalità demo · sola lettura' : 'Demo mode · read only'}</h2><p class="mt-1 break-words text-sm leading-6 text-pm-text-secondary">${it ? 'Stai esplorando dati di esempio. Aggiunta, modifica ed eliminazione sono disattivate e nessun dato reale viene toccato.' : 'You are exploring sample data. Creating, editing and deleting are disabled, and no real data is touched.'}</p></div></div></section>` : '';

    function renderGarage() {
        const cards = state.vehicles.length
            ? state.vehicles.map(v => `<article class="min-w-0 rounded-xl border border-pm-border bg-pm-surface p-4"><div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div class="min-w-0"><h3 class="break-words font-bold text-pm-text">${esc(v.name)}</h3><p class="mt-1 break-words text-sm text-pm-text-secondary">${esc([v.manufacturer, v.model, v.year].filter(Boolean).join(' · '))}</p><span class="mt-3 inline-block max-w-full break-words rounded-full bg-pm-subtle px-2.5 py-1 text-xs text-pm-muted">${esc(v.type)}</span></div>${rowActions('vehicles', v.id)}</div></article>`).join('')
            : empty(it ? 'Nessun mezzo. Aggiungi quello che vuoi gestire con PitMetric.' : 'No vehicles yet. Add the vehicle you want to manage with PitMetric.');

        return `${heading('GARAGE', it?'I tuoi mezzi':'Your vehicles', it?'Crea i mezzi che userai per configurazioni, sessioni e costi.':'Create the vehicles used by configurations, sessions and costs.')}${editablePanel(`<form data-form="vehicle" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">${input('name',it?'Nome mezzo':'Vehicle name','text','required placeholder="Kart 125"')}${select('type',it?'Tipo':'Type',`<option>Kart</option><option>${it?'Auto':'Car'}</option><option>${it?'Moto':'Motorcycle'}</option><option>Prototype</option>`)}${input('manufacturer',it?'Marca':'Manufacturer')}${input('model',it?'Modello':'Model')}${input('year',it?'Anno':'Year','number','min="1950" max="2100"')}<div class="min-w-0 md:col-span-2 xl:col-span-5">${primary(it?'Aggiungi mezzo':'Add vehicle')}</div></form>`)}<div class="grid min-w-0 gap-3 md:grid-cols-2 xl:grid-cols-3">${cards}</div>`;
    }

    function renderComponents() {
        const metricLabel = { distance: it?'Distanza':'Distance', runtime: it?'Ore motore':'Runtime', cycles: it?'Cicli / giri':'Cycles / laps', sessions: it?'Sessioni':'Sessions' };
        const usage = c => c.metric === 'distance' ? `${(c.usage || 0).toFixed(1)} km` : c.metric === 'runtime' ? `${(c.usage || 0).toFixed(1)} h` : `${Math.round(c.usage || 0)}`;
        const rows = state.components.length
            ? state.components.map(c => `<tr><td class="max-w-56 break-words py-3 pr-4 font-semibold text-pm-text">${esc(c.name)}</td><td class="break-words py-3 pr-4 text-pm-text-secondary">${esc(c.type)}</td><td class="break-all py-3 pr-4 text-pm-text-secondary">${esc(c.serial || '—')}</td><td class="break-words py-3 pr-4 text-pm-text-secondary">${metricLabel[c.metric]}</td><td class="py-3 pr-4 font-mono text-pm-text">${usage(c)}</td><td class="py-3 text-right">${rowActions('components', c.id)}</td></tr>`).join('')
            : `<tr><td colspan="6">${empty(it?'Nessun componente tracciato.':'No tracked components yet.')}</td></tr>`;

        return `${heading('COMPONENTS',it?'Componenti tracciati':'Tracked components',it?'Ogni componente sceglie la metrica che ha senso per lui.':'Each component tracks the metric that actually makes sense for it.')}${editablePanel(`<form data-form="component" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">${input('name',it?'Nome':'Name','text','required placeholder="Engine #01"')}${input('type',it?'Tipo':'Type','text','required placeholder="Engine"')}${input('serial',it?'Seriale / codice':'Serial / code')}${select('metric',it?'Metrica':'Metric',`<option value="distance">${metricLabel.distance}</option><option value="runtime">${metricLabel.runtime}</option><option value="cycles">${metricLabel.cycles}</option><option value="sessions">${metricLabel.sessions}</option>`)}<div class="min-w-0 md:col-span-2 xl:col-span-4">${primary(it?'Aggiungi componente':'Add component')}</div></form>`)}${panel(`<div class="max-w-full overflow-x-auto"><table class="w-full min-w-[680px] text-left text-sm"><thead class="border-b border-pm-border text-[11px] uppercase tracking-[0.1em] text-pm-muted"><tr><th class="pb-3 pr-4">${it?'Nome':'Name'}</th><th class="pb-3 pr-4">${it?'Tipo':'Type'}</th><th class="pb-3 pr-4">Serial</th><th class="pb-3 pr-4">${it?'Metrica':'Metric'}</th><th class="pb-3 pr-4">Usage</th><th></th></tr></thead><tbody class="divide-y divide-pm-border">${rows}</tbody></table></div>`)}`;
    }

    function renderConfigurations() {
        const vehicleOptions = `<option value="">${it?'Seleziona mezzo':'Select vehicle'}</option>` + state.vehicles.map(v=>`<option value="${v.id}">${esc(v.name)}</option>`).join('');
        const checks = state.components.length
            ? state.components.map(c=>`<label class="flex min-w-0 items-center gap-2 rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 text-sm text-pm-text-secondary"><input type="checkbox" name="componentIds" value="${c.id}" class="shrink-0 rounded border-pm-border-strong text-pm-accent"><span class="min-w-0 break-words">${esc(c.name)}</span></label>`).join('')
            : `<p class="text-sm text-pm-muted">${it?'Prima crea dei componenti.':'Create some components first.'}</p>`;
        const cards = state.configurations.length
            ? state.configurations.map(c=>`<article class="min-w-0 rounded-xl border border-pm-border bg-pm-surface p-4"><div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div class="min-w-0"><p class="text-xs font-semibold text-pm-accent">v${c.version || 1}</p><h3 class="mt-1 break-words font-bold text-pm-text">${esc(c.name)}</h3><p class="mt-1 break-words text-sm text-pm-text-secondary">${esc(nameOf(state.vehicles,c.vehicleId))}</p><p class="mt-3 text-xs text-pm-muted">${(c.componentIds || []).length} ${it?'componenti':'components'}</p></div>${rowActions('configurations',c.id)}</div></article>`).join('')
            : empty(it?'Nessuna configurazione.':'No configurations yet.');

        return `${heading('CONFIGURATIONS',it?'Build e configurazioni':'Builds & configurations',it?'Raggruppa i componenti installati in una configurazione del mezzo.':'Group the installed components into a vehicle configuration.')}${editablePanel(`<form data-form="configuration" class="space-y-4"><div class="grid gap-4 md:grid-cols-2">${input('name',it?'Nome configurazione':'Configuration name','text','required placeholder="Race Build"')}${select('vehicleId',it?'Mezzo':'Vehicle',vehicleOptions,'required')}</div><div class="min-w-0"><span class="pm-label">${it?'Componenti installati':'Installed components'}</span><div class="mt-2 grid min-w-0 gap-2 sm:grid-cols-2 lg:grid-cols-3">${checks}</div></div>${primary(it?'Salva configurazione':'Save configuration')}</form>`)}<div class="grid min-w-0 gap-3 md:grid-cols-2 xl:grid-cols-3">${cards}</div>`;
    }

    function renderCircuits() {
        const cards = state.circuits.length
            ? state.circuits.map(c=>`<article class="min-w-0 rounded-xl border border-pm-border bg-pm-surface p-4"><div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:justify-between"><div class="min-w-0"><h3 class="break-words font-bold text-pm-text">${esc(c.name)}</h3><p class="mt-1 break-words text-sm text-pm-text-secondary">${esc(c.layout)}</p><p class="mt-3 font-mono text-sm text-pm-text">${Number(c.lengthMeters).toLocaleString(it?'it-IT':'en-GB')} m</p></div>${rowActions('circuits',c.id)}</div></article>`).join('')
            : empty(it?'Nessun circuito.':'No circuits yet.');

        return `${heading('CIRCUITS',it?'Circuiti e layout':'Circuits & layouts',it?'La lunghezza del layout permette di calcolare automaticamente la distanza percorsa.':'Layout length lets PitMetric calculate driven distance automatically.')}${editablePanel(`<form data-form="circuit" class="grid gap-4 md:grid-cols-3">${input('name',it?'Circuito':'Circuit','text','required placeholder="Kart Planet"')}${input('layout',it?'Layout':'Layout','text','required placeholder="Full"')}${input('lengthMeters',it?'Lunghezza (m)':'Length (m)','number','required min="1" step="1"')}<div class="min-w-0 md:col-span-3">${primary(it?'Aggiungi circuito':'Add circuit')}</div></form>`)}<div class="grid min-w-0 gap-3 md:grid-cols-2 xl:grid-cols-3">${cards}</div>`;
    }

    function renderSessions() {
        const configOptions = `<option value="">${it?'Seleziona configurazione':'Select configuration'}</option>`+state.configurations.map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('');
        const circuitOptions = `<option value="">${it?'Seleziona circuito':'Select circuit'}</option>`+state.circuits.map(c=>`<option value="${c.id}">${esc(c.name)} · ${esc(c.layout)}</option>`).join('');
        const rows = state.sessions.length
            ? state.sessions.slice().reverse().map(s=>`<tr><td class="py-3 pr-4">${dateFmt(s.date)}</td><td class="max-w-60 break-words py-3 pr-4 font-semibold text-pm-text">${esc(nameOf(state.configurations,s.configurationId))}</td><td class="max-w-60 break-words py-3 pr-4 text-pm-text-secondary">${esc(nameOf(state.circuits,s.circuitId))}</td><td class="py-3 pr-4">${s.laps}</td><td class="py-3 pr-4 font-mono">${(s.distanceMeters/1000).toFixed(2)} km</td><td class="py-3 text-right">${rowActions('sessions',s.id)}</td></tr>`).join('')
            : `<tr><td colspan="6">${empty(it?'Nessuna sessione registrata.':'No sessions recorded yet.')}</td></tr>`;

        return `${heading('SESSIONS',it?'Registra una sessione':'Record a session',it?'Giri e lunghezza circuito aggiornano automaticamente l’utilizzo dei componenti inclusi nella configurazione.':'Laps and circuit length automatically update the usage of components in the configuration.')}${editablePanel(`<form data-form="session" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">${input('date',it?'Data':'Date','date','required')}${select('configurationId',it?'Configurazione':'Configuration',configOptions,'required')}${select('circuitId',it?'Circuito':'Circuit',circuitOptions,'required')}${input('laps',it?'Giri':'Laps','number','required min="1" step="1"')}${input('durationMinutes',it?'Durata (min)':'Duration (min)','number','required min="0" step="1"')}<div class="min-w-0 md:col-span-2 xl:col-span-5">${primary(it?'Registra sessione':'Record session')}</div></form>`)}${panel(`<div class="max-w-full overflow-x-auto"><table class="w-full min-w-[680px] text-left text-sm"><thead class="border-b border-pm-border text-[11px] uppercase tracking-[0.1em] text-pm-muted"><tr><th class="pb-3 pr-4">${it?'Data':'Date'}</th><th class="pb-3 pr-4">${it?'Configurazione':'Configuration'}</th><th class="pb-3 pr-4">${it?'Circuito':'Circuit'}</th><th class="pb-3 pr-4">${it?'Giri':'Laps'}</th><th class="pb-3 pr-4">${it?'Distanza':'Distance'}</th><th></th></tr></thead><tbody class="divide-y divide-pm-border">${rows}</tbody></table></div>`)}`;
    }

    function renderMaintenance() {
        const options = `<option value="">${it?'Seleziona componente':'Select component'}</option>`+state.components.map(c=>`<option value="${c.id}">${esc(c.name)}</option>`).join('');
        const rows = state.maintenance.length
            ? state.maintenance.slice().reverse().map(m=>`<article class="min-w-0 rounded-xl border border-pm-border bg-pm-surface p-4"><div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:justify-between"><div class="min-w-0"><p class="text-xs text-pm-muted">${dateFmt(m.date)}</p><h3 class="mt-1 break-words font-bold text-pm-text">${esc(nameOf(state.components,m.componentId))}</h3><p class="mt-1 break-words text-sm text-pm-text-secondary">${esc(m.action)}</p>${m.notes?`<p class="mt-2 break-words text-sm text-pm-muted">${esc(m.notes)}</p>`:''}${m.resetUsage?`<span class="mt-3 inline-block max-w-full break-words rounded-full bg-pm-success-subtle px-2.5 py-1 text-xs font-semibold text-pm-success">${it?'Contatore azzerato':'Counter reset'}</span>`:''}</div>${rowActions('maintenance',m.id)}</div></article>`).join('')
            : empty(it?'Nessun intervento registrato.':'No maintenance records yet.');

        return `${heading('MAINTENANCE',it?'Storico manutenzione':'Maintenance history',it?'Registra controlli, revisioni e sostituzioni e, quando serve, azzera il contatore del componente dopo l’intervento.':'Record inspections, rebuilds and replacements and reset the component counter after service when needed.')}${editablePanel(`<form data-form="maintenance" class="grid gap-4 md:grid-cols-2">${select('componentId',it?'Componente':'Component',options,'required')}${input('date',it?'Data':'Date','date','required')}${input('action',it?'Intervento':'Action','text','required placeholder="Rebuild"')}${input('notes',it?'Note':'Notes')}<label class="flex min-w-0 items-center gap-2 text-sm text-pm-text-secondary"><input type="checkbox" name="resetUsage" value="1" class="shrink-0 rounded border-pm-border-strong text-pm-accent"><span class="min-w-0 break-words">${it?'Azzera il contatore utilizzo dopo l’intervento':'Reset usage counter after this service'}</span></label><div class="min-w-0 md:col-span-2">${primary(it?'Registra intervento':'Add maintenance')}</div></form>`)}<div class="grid min-w-0 gap-3 md:grid-cols-2">${rows}</div>`;
    }

    function renderExpenses() {
        const vehicles = `<option value="">${it?'Generale / nessun mezzo':'General / no vehicle'}</option>`+state.vehicles.map(v=>`<option value="${v.id}">${esc(v.name)}</option>`).join('');
        const total = state.expenses.reduce((sum,e)=>sum+(e.amountCents||0),0);
        const rows = state.expenses.length
            ? state.expenses.slice().reverse().map(e=>`<tr><td class="py-3 pr-4">${dateFmt(e.date)}</td><td class="break-words py-3 pr-4 font-semibold text-pm-text">${esc(e.category)}</td><td class="max-w-52 break-words py-3 pr-4 text-pm-text-secondary">${esc(nameOf(state.vehicles,e.vehicleId,it?'Generale':'General'))}</td><td class="max-w-64 break-words py-3 pr-4 text-pm-text-secondary">${esc(e.note||'—')}</td><td class="py-3 pr-4 text-right font-mono text-pm-text">${money(e.amountCents)}</td><td class="py-3 text-right">${rowActions('expenses',e.id)}</td></tr>`).join('')
            : `<tr><td colspan="6">${empty(it?'Nessuna spesa registrata.':'No expenses recorded yet.')}</td></tr>`;

        return `${heading('EXPENSES',it?'Costi e spese':'Costs & expenses',it?'Registra le spese e tieni sotto controllo il totale.':'Record expenses and keep the running total under control.')}${panel(`<div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div class="min-w-0"><p class="break-words text-xs uppercase tracking-[0.12em] text-pm-muted">${it?'Totale registrato':'Recorded total'}</p><p class="mt-2 break-words text-3xl font-black text-pm-text">${money(total)}</p></div><span class="shrink-0 text-xs text-pm-muted">${state.expenses.length} ${it?'voci':'entries'}</span></div>`)}${editablePanel(`<form data-form="expense" class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">${input('date',it?'Data':'Date','date','required')}${select('category',it?'Categoria':'Category',`<option>${it?'Carburante':'Fuel'}</option><option>${it?'Ricambi':'Parts'}</option><option>${it?'Pista':'Track'}</option><option>${it?'Manutenzione':'Maintenance'}</option><option>${it?'Altro':'Other'}</option>`)}${select('vehicleId',it?'Mezzo':'Vehicle',vehicles)}${input('amount',it?'Importo (€)':'Amount (€)','number','required min="0" step="0.01"')}${input('note',it?'Nota':'Note')}<div class="min-w-0 md:col-span-2 xl:col-span-5">${primary(it?'Aggiungi spesa':'Add expense')}</div></form>`)}${panel(`<div class="max-w-full overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead class="border-b border-pm-border text-[11px] uppercase tracking-[0.1em] text-pm-muted"><tr><th class="pb-3 pr-4">${it?'Data':'Date'}</th><th class="pb-3 pr-4">${it?'Categoria':'Category'}</th><th class="pb-3 pr-4">${it?'Mezzo':'Vehicle'}</th><th class="pb-3 pr-4">${it?'Nota':'Note'}</th><th class="pb-3 pr-4 text-right">${it?'Importo':'Amount'}</th><th></th></tr></thead><tbody class="divide-y divide-pm-border">${rows}</tbody></table></div>`)}`;
    }

    const renderers = { garage:renderGarage, components:renderComponents, configurations:renderConfigurations, circuits:renderCircuits, sessions:renderSessions, maintenance:renderMaintenance, expenses:renderExpenses };
    const render = () => {
        root.innerHTML = `${readOnlyBanner()}${(renderers[section] || renderGarage)()}`;
        bind();
    };

    function bind() {
        if (readOnly) return;

        root.querySelectorAll('form[data-form]').forEach(form => form.addEventListener('submit', event => {
            event.preventDefault();
            const data = new FormData(form);
            const type = form.dataset.form;

            if (type === 'vehicle') state.vehicles.push({ id:id(), name:data.get('name'), type:data.get('type'), manufacturer:data.get('manufacturer'), model:data.get('model'), year:data.get('year') });
            if (type === 'component') state.components.push({ id:id(), name:data.get('name'), type:data.get('type'), serial:data.get('serial'), metric:data.get('metric'), usage:0 });
            if (type === 'configuration') state.configurations.push({ id:id(), name:data.get('name'), vehicleId:data.get('vehicleId'), componentIds:data.getAll('componentIds'), version:1 });
            if (type === 'circuit') state.circuits.push({ id:id(), name:data.get('name'), layout:data.get('layout'), lengthMeters:Number(data.get('lengthMeters')) });

            if (type === 'session') {
                const configuration = state.configurations.find(item => item.id === data.get('configurationId'));
                const circuit = state.circuits.find(item => item.id === data.get('circuitId'));
                if (!configuration || !circuit) return;
                const laps = Number(data.get('laps'));
                const durationMinutes = Number(data.get('durationMinutes'));
                const distanceMeters = circuit.lengthMeters * laps;
                state.sessions.push({ id:id(), date:data.get('date'), configurationId:configuration.id, circuitId:circuit.id, laps, durationMinutes, distanceMeters });
                configuration.componentIds.forEach(componentId => {
                    const component = state.components.find(item => item.id === componentId);
                    if (!component) return;
                    if (component.metric === 'distance') component.usage += distanceMeters / 1000;
                    if (component.metric === 'runtime') component.usage += durationMinutes / 60;
                    if (component.metric === 'cycles') component.usage += laps;
                    if (component.metric === 'sessions') component.usage += 1;
                });
            }

            if (type === 'maintenance') {
                const resetUsage = data.get('resetUsage') === '1';
                state.maintenance.push({ id:id(), componentId:data.get('componentId'), date:data.get('date'), action:data.get('action'), notes:data.get('notes'), resetUsage });
                if (resetUsage) {
                    const component = state.components.find(item => item.id === data.get('componentId'));
                    if (component) component.usage = 0;
                }
            }

            if (type === 'expense') state.expenses.push({ id:id(), date:data.get('date'), category:data.get('category'), vehicleId:data.get('vehicleId'), amountCents:Math.round(Number(data.get('amount'))*100), note:data.get('note') });
            save();
            render();
        }));

        root.querySelectorAll('[data-delete]').forEach(button => button.addEventListener('click', () => {
            const collection = button.dataset.delete;
            const itemId = button.dataset.id;
            state[collection] = state[collection].filter(item => item.id !== itemId);
            if (collection === 'components') state.configurations.forEach(c => c.componentIds = c.componentIds.filter(componentId => componentId !== itemId));
            save();
            render();
        }));
    }

    if (!readOnly) {
        document.getElementById('demo-seed')?.addEventListener('click', () => {
            state = showcaseState();
            save();
            render();
        });

        document.getElementById('demo-reset')?.addEventListener('click', () => {
            if (confirm(it ? 'Azzerare tutti i dati inseriti?' : 'Reset all entered data?')) {
                state = emptyState();
                save();
                render();
            }
        });
    }

    render();
}

const dashboard = document.querySelector('[data-demo-dashboard]');
if (dashboard) {
    const userId = dashboard.dataset.user;
    let state = demoReadOnly ? showcaseState() : {};

    if (!demoReadOnly) {
        try {
            state = JSON.parse(localStorage.getItem(`pitmetric:manager:v2:${userId}`) || localStorage.getItem(`pitmetric:demo:v2:${userId}`) || '{}');
        } catch {}
    }

    const set = (name, value) => {
        const node = document.querySelector(`[data-demo-count="${name}"]`);
        if (node) node.textContent = value;
    };

    set('vehicles', (state.vehicles || []).length);
    set('configurations', (state.configurations || []).length);
    set('sessions', (state.sessions || []).length);
    set('maintenance', (state.maintenance || []).length);
}
