const demoRoot = document.getElementById('pitmetric-public-demo');

if (demoRoot) {
    const it = demoRoot.dataset.locale === 'it';

    const state = {
        vehicles: [
            { id: 'kart-kr2', name: 'KR2 Racing Kart', type: 'Kart', manufacturer: 'KR', model: 'KR2', year: 2025, status: 'Track ready', distance: 1842 },
            { id: 'kart-tony', name: 'Tony Kart Racer 401 RR', type: 'Kart', manufacturer: 'Tony Kart', model: 'Racer 401 RR', year: 2024, status: 'Workshop', distance: 963 },
            { id: 'sim-rig', name: 'Development Rig', type: 'Simulator', manufacturer: 'PitMetric', model: 'Telemetry Lab', year: 2026, status: 'Development', distance: 0 },
        ],
        components: [
            { id:'eng-02', name:'Rotax MAX EVO #02', type: it?'Motore':'Engine', serial:'RTX-EVO-2402', metric:'runtime', usage:'11.8 h', life:72, vehicle:'KR2 Racing Kart' },
            { id:'chain-04', name:'Chain DID #04', type:it?'Trasmissione':'Drivetrain', serial:'DID-219V2-04', metric:'distance', usage:'286 km', life:61, vehicle:'KR2 Racing Kart' },
            { id:'tyres-08', name:'Bridgestone YLR #08', type:it?'Pneumatici':'Tyres', serial:'YLR-08', metric:'cycles', usage:'93 laps', life:44, vehicle:'KR2 Racing Kart' },
            { id:'pads-03', name:'Brake Pads #03', type:it?'Freni':'Brakes', serial:'BRK-03', metric:'distance', usage:'417 km', life:79, vehicle:'KR2 Racing Kart' },
            { id:'eng-01', name:'Rotax MAX EVO #01', type:it?'Motore':'Engine', serial:'RTX-EVO-2301', metric:'runtime', usage:'28.4 h', life:31, vehicle:'Tony Kart Racer 401 RR' },
            { id:'chain-02', name:'Chain DID #02', type:it?'Trasmissione':'Drivetrain', serial:'DID-219V2-02', metric:'distance', usage:'511 km', life:28, vehicle:'Tony Kart Racer 401 RR' },
            { id:'axle-02', name:'Rear Axle 50 mm #02', type:it?'Telaio':'Chassis', serial:'AXL-50-02', metric:'distance', usage:'1,120 km', life:68, vehicle:'KR2 Racing Kart' },
            { id:'bearing-05', name:'Bearing Set #05', type:it?'Telaio':'Chassis', serial:'BRG-05', metric:'distance', usage:'732 km', life:52, vehicle:'KR2 Racing Kart' },
            { id:'sprocket-11', name:'Rear Sprocket 78T #11', type:it?'Trasmissione':'Drivetrain', serial:'SP78-11', metric:'distance', usage:'194 km', life:87, vehicle:'KR2 Racing Kart' },
            { id:'battery-02', name:'Lithium Battery #02', type:it?'Elettrico':'Electrical', serial:'LFP-02', metric:'sessions', usage:'23 sessions', life:83, vehicle:'KR2 Racing Kart' },
        ],
        configurations: [
            { name:'Race Build V3', vehicle:'KR2 Racing Kart', version:3, components:['Rotax MAX EVO #02','Chain DID #04','Bridgestone YLR #08','Brake Pads #03','Rear Axle 50 mm #02','Rear Sprocket 78T #11'], status:it?'Attiva':'Active' },
            { name:'Wet Build V2', vehicle:'KR2 Racing Kart', version:2, components:['Rotax MAX EVO #02','Chain DID #04','Brake Pads #03','Bearing Set #05'], status:it?'Pronta':'Ready' },
            { name:'Training Build', vehicle:'Tony Kart Racer 401 RR', version:4, components:['Rotax MAX EVO #01','Chain DID #02'], status:it?'Secondaria':'Secondary' },
        ],
        setups: [
            { name:'Busca Qualifying', vehicle:'KR2 Racing Kart', front:'2 mm', rear:'1395 mm', pressure:'0.86 / 0.88 bar', ratio:'11/78', note:it?'Anteriore preciso, uscita curva stabile.':'Sharp front end, stable corner exit.' },
            { name:'Kart Planet Race', vehicle:'KR2 Racing Kart', front:'4 mm', rear:'1400 mm', pressure:'0.84 / 0.86 bar', ratio:'11/79', note:it?'Più trazione nel lento.':'More traction in slow sections.' },
            { name:'Wet Baseline', vehicle:'KR2 Racing Kart', front:'8 mm', rear:'1388 mm', pressure:'1.05 / 1.08 bar', ratio:'11/80', note:it?'Base pioggia, risposta progressiva.':'Wet baseline, progressive response.' },
        ],
        circuits: [
            { name:'Circuito di Busca', layout:'Full', length:1200, best:'52.184', sessions:8 },
            { name:'Kart Planet', layout:'Race', length:1125, best:'48.932', sessions:6 },
            { name:'South Garda Karting', layout:'International', length:1200, best:'49.741', sessions:2 },
            { name:'Lonato Training Loop', layout:'Short', length:873, best:'38.604', sessions:2 },
        ],
        sessions: [
            { date:'2026-09-24', circuit:'Circuito di Busca', layout:'Full', vehicle:'KR2 Racing Kart', laps:42, best:'52.184', avg:'54.902', distance:50.4, condition:it?'Asciutto':'Dry' },
            { date:'2026-09-18', circuit:'Kart Planet', layout:'Race', vehicle:'KR2 Racing Kart', laps:37, best:'48.932', avg:'51.118', distance:41.6, condition:it?'Asciutto':'Dry' },
            { date:'2026-09-07', circuit:'Circuito di Busca', layout:'Full', vehicle:'KR2 Racing Kart', laps:31, best:'52.611', avg:'55.002', distance:37.2, condition:it?'Variabile':'Mixed' },
            { date:'2026-08-30', circuit:'Kart Planet', layout:'Race', vehicle:'KR2 Racing Kart', laps:44, best:'49.105', avg:'51.477', distance:49.5, condition:it?'Asciutto':'Dry' },
            { date:'2026-08-22', circuit:'Circuito di Busca', layout:'Full', vehicle:'KR2 Racing Kart', laps:36, best:'52.780', avg:'55.330', distance:43.2, condition:it?'Caldo':'Hot' },
            { date:'2026-08-10', circuit:'South Garda Karting', layout:'International', vehicle:'KR2 Racing Kart', laps:51, best:'49.741', avg:'52.104', distance:61.2, condition:it?'Asciutto':'Dry' },
            { date:'2026-07-27', circuit:'Circuito di Busca', layout:'Full', vehicle:'Tony Kart Racer 401 RR', laps:34, best:'53.404', avg:'55.902', distance:40.8, condition:it?'Asciutto':'Dry' },
            { date:'2026-07-13', circuit:'Kart Planet', layout:'Race', vehicle:'KR2 Racing Kart', laps:39, best:'49.330', avg:'51.862', distance:43.9, condition:it?'Asciutto':'Dry' },
            { date:'2026-06-28', circuit:'Lonato Training Loop', layout:'Short', vehicle:'KR2 Racing Kart', laps:63, best:'38.604', avg:'40.991', distance:55.0, condition:it?'Asciutto':'Dry' },
            { date:'2026-06-15', circuit:'Circuito di Busca', layout:'Full', vehicle:'Tony Kart Racer 401 RR', laps:29, best:'53.780', avg:'56.123', distance:34.8, condition:it?'Asciutto':'Dry' },
        ],
        maintenance: [
            { date:'2026-09-26', component:'Chain DID #04', action:it?'Controllo tensione e lubrificazione':'Tension check and lubrication', status:it?'Completata':'Completed', next:it?'tra 64 km':'in 64 km' },
            { date:'2026-09-20', component:'Rotax MAX EVO #02', action:it?'Controllo candela e carburazione':'Spark plug and carburation check', status:it?'Completata':'Completed', next:it?'tra 3.2 h':'in 3.2 h' },
            { date:'2026-09-12', component:'Brake Pads #03', action:it?'Controllo spessore pastiglie':'Brake pad thickness check', status:it?'Monitorata':'Monitored', next:it?'tra 183 km':'in 183 km' },
            { date:'2026-08-31', component:'Bridgestone YLR #08', action:it?'Rotazione pneumatici':'Tyre rotation', status:it?'Completata':'Completed', next:it?'tra 27 giri':'in 27 laps' },
            { date:'2026-08-11', component:'Rear Axle 50 mm #02', action:it?'Controllo allineamento':'Alignment inspection', status:it?'Completata':'Completed', next:it?'tra 380 km':'in 380 km' },
            { date:'2026-07-29', component:'Bearing Set #05', action:it?'Pulizia e ingrassaggio':'Clean and grease', status:it?'Completata':'Completed', next:it?'tra 268 km':'in 268 km' },
        ],
        expenses: [
            { date:'2026-09-24', category:it?'Pista':'Track', vehicle:'KR2 Racing Kart', note:it?'Ingresso Circuito di Busca':'Circuito di Busca entry', amount:65.00 },
            { date:'2026-09-22', category:it?'Ricambi':'Parts', vehicle:'KR2 Racing Kart', note:'DID chain + sprocket', amount:146.50 },
            { date:'2026-09-18', category:it?'Pista':'Track', vehicle:'KR2 Racing Kart', note:'Kart Planet', amount:58.00 },
            { date:'2026-09-10', category:it?'Consumabili':'Consumables', vehicle:'KR2 Racing Kart', note:it?'Set pneumatici Bridgestone':'Bridgestone tyre set', amount:219.00 },
            { date:'2026-08-28', category:it?'Manutenzione':'Maintenance', vehicle:'KR2 Racing Kart', note:it?'Revisione carburatore':'Carburettor service', amount:125.00 },
            { date:'2026-08-10', category:it?'Pista':'Track', vehicle:'KR2 Racing Kart', note:'South Garda Karting', amount:95.00 },
            { date:'2026-08-03', category:it?'Carburante':'Fuel', vehicle:'KR2 Racing Kart', note:'98 RON + oil', amount:73.20 },
            { date:'2026-07-21', category:it?'Ricambi':'Parts', vehicle:'Tony Kart Racer 401 RR', note:it?'Pastiglie + cuscinetti':'Pads + bearings', amount:184.00 },
            { date:'2026-07-02', category:it?'Manutenzione':'Maintenance', vehicle:'KR2 Racing Kart', note:it?'Revisione motore parziale':'Partial engine service', amount:420.00 },
            { date:'2026-06-18', category:it?'Consumabili':'Consumables', vehicle:'KR2 Racing Kart', note:it?'Pneumatici + lubrificanti':'Tyres + lubricants', amount:278.00 },
        ],
        events: [
            { name:'Busca Club Race · Round 5', date:'2026-10-04', circuit:'Circuito di Busca', status:it?'Preparazione':'Preparing', entries:2, tasks:'7 / 10' },
            { name:'Kart Planet Sprint Night', date:'2026-09-18', circuit:'Kart Planet', status:it?'Completato':'Completed', entries:1, tasks:'8 / 8' },
            { name:'South Garda Test Day', date:'2026-08-10', circuit:'South Garda Karting', status:it?'Completato':'Completed', entries:1, tasks:'6 / 6' },
            { name:'Busca Summer Trophy', date:'2026-07-27', circuit:'Circuito di Busca', status:it?'Completato':'Completed', entries:2, tasks:'9 / 9' },
        ],
    };

    const esc = (value = '') => String(value).replace(/[&<>'"]/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#39;', '"':'&quot;' }[char]));
    const dateFmt = value => new Intl.DateTimeFormat(it ? 'it-IT' : 'en-GB', { day:'2-digit', month:'short', year:'numeric' }).format(new Date(`${value}T12:00:00`));
    const money = value => new Intl.NumberFormat(it ? 'it-IT' : 'en-GB', { style:'currency', currency:'EUR' }).format(value);
    const panel = content => `<section class="pm-panel min-w-0 overflow-hidden p-5 sm:p-6">${content}</section>`;
    const heading = (eyebrow, title, description) => `<div class="min-w-0"><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">${eyebrow}</p><h1 class="mt-2 break-words text-2xl font-black tracking-[-0.03em] text-pm-text sm:text-3xl">${title}</h1><p class="mt-2 max-w-3xl break-words text-sm leading-6 text-pm-text-secondary">${description}</p></div>`;
    const disabledAction = label => `<button type="button" disabled class="pm-race-button cursor-not-allowed opacity-40" title="${it?'Disabilitato nella demo':'Disabled in demo'}">${label}</button>`;
    const badge = label => `<span class="inline-flex max-w-full items-center rounded-full border border-pm-border bg-pm-subtle px-2.5 py-1 text-xs font-semibold text-pm-text-secondary">${esc(label)}</span>`;

    function renderDashboard() {
        const distance = state.sessions.reduce((sum, s) => sum + s.distance, 0);
        const spend = state.expenses.reduce((sum, e) => sum + e.amount, 0);
        const lastSessions = state.sessions.slice(0, 5);
        return `${heading('DASHBOARD', it?'Race Team Demo':'Race Team Demo', it?'Una panoramica realistica del workspace PitMetric: attività, stato tecnico, costi e prossimi interventi.':'A realistic PitMetric workspace overview: activity, technical state, costs and upcoming work.')}
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                ${[[it?'Distanza registrata':'Recorded distance',`${distance.toFixed(1)} km`,it?'10 sessioni recenti':'10 recent sessions'],[it?'Componenti tracciati':'Tracked components',state.components.length,it?'su 2 kart attivi':'across 2 active karts'],[it?'Spesa registrata':'Recorded spend',money(spend),it?'ultimi movimenti demo':'latest demo entries'],[it?'Eventi':'Events',state.events.length,it?'1 in preparazione':'1 preparing']].map(([l,v,n])=>`<article class="pm-panel min-w-0 p-5"><p class="text-xs font-semibold uppercase tracking-[0.1em] text-pm-muted">${l}</p><p class="mt-3 break-words text-2xl font-black text-pm-text">${v}</p><p class="mt-2 text-xs text-pm-muted">${n}</p></article>`).join('')}
            </div>
            <div class="grid min-w-0 gap-4 xl:grid-cols-[1.35fr_.65fr]">
                ${panel(`<div class="flex flex-wrap items-end justify-between gap-3"><div><p class="pm-label">${it?'Attività recente':'Recent activity'}</p><h2 class="mt-1 text-xl font-bold text-pm-text">${it?'Ultime sessioni':'Latest sessions'}</h2></div><button data-jump="sessions" class="text-sm font-semibold text-pm-accent hover:underline">${it?'Apri sessioni':'Open sessions'} →</button></div><div class="mt-5 overflow-x-auto"><table class="w-full min-w-[620px] text-left text-sm"><thead class="border-b border-pm-border text-[11px] uppercase tracking-[0.1em] text-pm-muted"><tr><th class="pb-3 pr-4">${it?'Data':'Date'}</th><th class="pb-3 pr-4">${it?'Circuito':'Circuit'}</th><th class="pb-3 pr-4">${it?'Giri':'Laps'}</th><th class="pb-3 pr-4">Best</th><th class="pb-3">km</th></tr></thead><tbody class="divide-y divide-pm-border">${lastSessions.map(s=>`<tr><td class="py-3 pr-4 text-pm-muted">${dateFmt(s.date)}</td><td class="py-3 pr-4 font-semibold text-pm-text">${esc(s.circuit)}</td><td class="py-3 pr-4">${s.laps}</td><td class="py-3 pr-4 font-mono">${s.best}</td><td class="py-3 font-mono">${s.distance.toFixed(1)}</td></tr>`).join('')}</tbody></table></div>`)}
                ${panel(`<p class="pm-label">${it?'Stato tecnico':'Technical status'}</p><h2 class="mt-1 text-xl font-bold text-pm-text">${it?'Componenti da osservare':'Components to watch'}</h2><div class="mt-5 space-y-4">${state.components.filter(c=>c.life<65).slice(0,4).map(c=>`<div><div class="flex min-w-0 justify-between gap-3 text-sm"><span class="min-w-0 break-words font-semibold text-pm-text">${esc(c.name)}</span><span class="shrink-0 font-mono text-pm-muted">${c.life}%</span></div><div class="mt-2 h-1.5 overflow-hidden rounded-full bg-pm-subtle"><div class="h-full bg-pm-accent" style="width:${c.life}%"></div></div></div>`).join('')}</div>`) }
            </div>`;
    }

    function renderEvents() {
        return `${heading('EVENTS',it?'Eventi e weekend':'Events & weekends',it?'Pianificazione gara, stato attività e checklist operative del team.':'Race planning, activity status and team operational checklists.')}${panel(`<div class="flex flex-wrap items-center justify-between gap-3"><div><p class="pm-label">${it?'Calendario':'Calendar'}</p><p class="mt-1 text-sm text-pm-muted">${state.events.length} ${it?'eventi demo':'demo events'}</p></div>${disabledAction(it?'Nuovo evento':'New event')}</div><div class="mt-5 grid gap-3 md:grid-cols-2">${state.events.map(e=>`<article class="rounded-xl border border-pm-border bg-pm-surface p-4"><div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs text-pm-muted">${dateFmt(e.date)}</p><h3 class="mt-1 break-words font-bold text-pm-text">${esc(e.name)}</h3><p class="mt-1 text-sm text-pm-text-secondary">${esc(e.circuit)}</p></div>${badge(e.status)}</div><div class="mt-4 grid grid-cols-2 gap-2 text-xs"><div class="rounded-lg bg-pm-subtle p-3"><p class="text-pm-muted">Entries</p><p class="mt-1 font-bold text-pm-text">${e.entries}</p></div><div class="rounded-lg bg-pm-subtle p-3"><p class="text-pm-muted">Tasks</p><p class="mt-1 font-bold text-pm-text">${e.tasks}</p></div></div></article>`).join('')}</div>`)}`;
    }

    function renderGarage() {
        return `${heading('GARAGE',it?'Mezzi del team':'Team vehicles',it?'Il garage demo mostra mezzi con utilizzo e stato operativo differenti.':'The demo garage shows vehicles with different usage and operational states.')}${panel(`<div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-pm-muted">${state.vehicles.length} ${it?'mezzi registrati':'registered vehicles'}</p>${disabledAction(it?'Aggiungi mezzo':'Add vehicle')}</div><div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">${state.vehicles.map(v=>`<article class="rounded-xl border border-pm-border bg-pm-surface p-5"><div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-[0.1em] text-pm-accent">${esc(v.type)}</p><h3 class="mt-2 break-words text-lg font-bold text-pm-text">${esc(v.name)}</h3><p class="mt-1 text-sm text-pm-text-secondary">${esc(v.manufacturer)} · ${esc(v.model)} · ${v.year}</p></div>${badge(v.status)}</div><div class="mt-5 border-t border-pm-border pt-4"><p class="text-xs text-pm-muted">${it?'Utilizzo registrato':'Recorded usage'}</p><p class="mt-1 font-mono text-lg font-bold text-pm-text">${v.distance.toLocaleString(it?'it-IT':'en-GB')} km</p></div></article>`).join('')}</div>`)}`;
    }

    function renderComponents() {
        return `${heading('COMPONENTS',it?'Componenti tracciati':'Tracked components',it?'Utilizzo, seriali e vita residua dei componenti montati sui mezzi demo.':'Usage, serials and remaining life for components installed on demo vehicles.')}${panel(`<div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-pm-muted">${state.components.length} ${it?'componenti monitorati':'tracked components'}</p>${disabledAction(it?'Aggiungi componente':'Add component')}</div><div class="mt-5 overflow-x-auto"><table class="w-full min-w-[820px] text-left text-sm"><thead class="border-b border-pm-border text-[11px] uppercase tracking-[0.1em] text-pm-muted"><tr><th class="pb-3 pr-4">${it?'Nome':'Name'}</th><th class="pb-3 pr-4">${it?'Tipo':'Type'}</th><th class="pb-3 pr-4">Serial</th><th class="pb-3 pr-4">${it?'Mezzo':'Vehicle'}</th><th class="pb-3 pr-4">Usage</th><th class="pb-3">Life</th></tr></thead><tbody class="divide-y divide-pm-border">${state.components.map(c=>`<tr><td class="py-3 pr-4 font-semibold text-pm-text">${esc(c.name)}</td><td class="py-3 pr-4 text-pm-text-secondary">${esc(c.type)}</td><td class="py-3 pr-4 font-mono text-xs text-pm-muted">${esc(c.serial)}</td><td class="py-3 pr-4 text-pm-text-secondary">${esc(c.vehicle)}</td><td class="py-3 pr-4 font-mono">${esc(c.usage)}</td><td class="py-3"><div class="flex items-center gap-2"><div class="h-1.5 w-20 overflow-hidden rounded-full bg-pm-subtle"><div class="h-full bg-pm-accent" style="width:${c.life}%"></div></div><span class="font-mono text-xs">${c.life}%</span></div></td></tr>`).join('')}</tbody></table></div>`)}`;
    }

    function renderConfigurations() {
        return `${heading('CONFIGURATIONS',it?'Build e configurazioni':'Builds & configurations',it?'Ogni configurazione raggruppa i componenti installati e mantiene una versione chiara del mezzo.':'Each configuration groups installed components and keeps a clear vehicle version.')}${panel(`<div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-pm-muted">${state.configurations.length} build</p>${disabledAction(it?'Nuova configurazione':'New configuration')}</div><div class="mt-5 grid gap-3 lg:grid-cols-3">${state.configurations.map(c=>`<article class="rounded-xl border border-pm-border bg-pm-surface p-5"><div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold text-pm-accent">v${c.version}</p><h3 class="mt-1 text-lg font-bold text-pm-text">${esc(c.name)}</h3><p class="mt-1 text-sm text-pm-muted">${esc(c.vehicle)}</p></div>${badge(c.status)}</div><div class="mt-5 space-y-2">${c.components.map(name=>`<div class="rounded-lg bg-pm-subtle px-3 py-2 text-sm text-pm-text-secondary">${esc(name)}</div>`).join('')}</div></article>`).join('')}</div>`)}`;
    }

    function renderSetups() {
        return `${heading('SETUPS',it?'Setup tecnici':'Technical setups',it?'Esempi di assetto salvati per circuito e condizione, consultabili senza modifiche.':'Saved setup examples by circuit and condition, viewable without edits.')}${panel(`<div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-pm-muted">${state.setups.length} setup</p>${disabledAction(it?'Nuovo setup':'New setup')}</div><div class="mt-5 grid gap-3 lg:grid-cols-3">${state.setups.map(s=>`<article class="rounded-xl border border-pm-border bg-pm-surface p-5"><h3 class="font-bold text-pm-text">${esc(s.name)}</h3><p class="mt-1 text-xs text-pm-muted">${esc(s.vehicle)}</p><dl class="mt-5 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-xs text-pm-muted">Front</dt><dd class="mt-1 font-mono text-pm-text">${esc(s.front)}</dd></div><div><dt class="text-xs text-pm-muted">Rear</dt><dd class="mt-1 font-mono text-pm-text">${esc(s.rear)}</dd></div><div><dt class="text-xs text-pm-muted">Pressure</dt><dd class="mt-1 font-mono text-pm-text">${esc(s.pressure)}</dd></div><div><dt class="text-xs text-pm-muted">Ratio</dt><dd class="mt-1 font-mono text-pm-text">${esc(s.ratio)}</dd></div></dl><p class="mt-5 border-t border-pm-border pt-4 text-sm leading-6 text-pm-text-secondary">${esc(s.note)}</p></article>`).join('')}</div>`)}`;
    }

    function renderCircuits() {
        return `${heading('CIRCUITS',it?'Circuiti e layout':'Circuits & layouts',it?'Circuiti usati dal team demo con lunghezza, attività e miglior riferimento registrato.':'Circuits used by the demo team with length, activity and best recorded reference.')}${panel(`<div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-pm-muted">${state.circuits.length} circuiti</p>${disabledAction(it?'Aggiungi circuito':'Add circuit')}</div><div class="mt-5 grid gap-3 md:grid-cols-2">${state.circuits.map(c=>`<article class="rounded-xl border border-pm-border bg-pm-surface p-5"><div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="font-bold text-pm-text">${esc(c.name)}</h3><p class="mt-1 text-sm text-pm-muted">${esc(c.layout)}</p></div><span class="font-mono text-sm text-pm-text">${c.length.toLocaleString(it?'it-IT':'en-GB')} m</span></div><div class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-lg bg-pm-subtle p-3"><p class="text-xs text-pm-muted">Best</p><p class="mt-1 font-mono font-bold text-pm-text">${c.best}</p></div><div class="rounded-lg bg-pm-subtle p-3"><p class="text-xs text-pm-muted">Sessions</p><p class="mt-1 font-bold text-pm-text">${c.sessions}</p></div></div></article>`).join('')}</div>`)}`;
    }

    function renderSessions() {
        return `${heading('SESSIONS',it?'Sessioni registrate':'Recorded sessions',it?'Storico completo di esempio con giri, tempi, distanza e condizioni pista.':'Example history with laps, times, distance and track conditions.')}${panel(`<div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-pm-muted">${state.sessions.length} ${it?'sessioni mostrate':'sessions shown'}</p>${disabledAction(it?'Registra sessione':'Record session')}</div><div class="mt-5 overflow-x-auto"><table class="w-full min-w-[900px] text-left text-sm"><thead class="border-b border-pm-border text-[11px] uppercase tracking-[0.1em] text-pm-muted"><tr><th class="pb-3 pr-4">${it?'Data':'Date'}</th><th class="pb-3 pr-4">${it?'Circuito':'Circuit'}</th><th class="pb-3 pr-4">${it?'Mezzo':'Vehicle'}</th><th class="pb-3 pr-4">${it?'Giri':'Laps'}</th><th class="pb-3 pr-4">Best</th><th class="pb-3 pr-4">Avg</th><th class="pb-3 pr-4">km</th><th class="pb-3">${it?'Condizioni':'Conditions'}</th></tr></thead><tbody class="divide-y divide-pm-border">${state.sessions.map(s=>`<tr><td class="py-3 pr-4 whitespace-nowrap text-pm-muted">${dateFmt(s.date)}</td><td class="py-3 pr-4 font-semibold text-pm-text">${esc(s.circuit)}<span class="block text-xs font-normal text-pm-muted">${esc(s.layout)}</span></td><td class="py-3 pr-4 text-pm-text-secondary">${esc(s.vehicle)}</td><td class="py-3 pr-4">${s.laps}</td><td class="py-3 pr-4 font-mono font-semibold text-pm-text">${s.best}</td><td class="py-3 pr-4 font-mono text-pm-muted">${s.avg}</td><td class="py-3 pr-4 font-mono">${s.distance.toFixed(1)}</td><td class="py-3">${badge(s.condition)}</td></tr>`).join('')}</tbody></table></div>`)}`;
    }

    function renderMaintenance() {
        return `${heading('MAINTENANCE',it?'Manutenzione':'Maintenance',it?'Storico interventi e prossime scadenze tecniche dei componenti demo.':'Service history and upcoming technical deadlines for demo components.')}${panel(`<div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-pm-muted">${state.maintenance.length} ${it?'interventi recenti':'recent records'}</p>${disabledAction(it?'Registra intervento':'Add maintenance')}</div><div class="mt-5 grid gap-3 md:grid-cols-2">${state.maintenance.map(m=>`<article class="rounded-xl border border-pm-border bg-pm-surface p-4"><div class="flex flex-wrap items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs text-pm-muted">${dateFmt(m.date)}</p><h3 class="mt-1 break-words font-bold text-pm-text">${esc(m.component)}</h3><p class="mt-2 text-sm leading-6 text-pm-text-secondary">${esc(m.action)}</p></div>${badge(m.status)}</div><div class="mt-4 border-t border-pm-border pt-3 text-xs text-pm-muted">${it?'Prossimo controllo':'Next check'}: <span class="font-semibold text-pm-text-secondary">${esc(m.next)}</span></div></article>`).join('')}</div>`)}`;
    }

    function renderExpenses() {
        const total = state.expenses.reduce((sum,e)=>sum+e.amount,0);
        const byCategory = state.expenses.reduce((acc,e)=>{acc[e.category]=(acc[e.category]||0)+e.amount;return acc;},{});
        return `${heading('EXPENSES',it?'Costi e spese':'Costs & expenses',it?'Movimenti economici d’esempio con totale e ripartizione per categoria.':'Example financial entries with total spend and category breakdown.')}
            <div class="grid gap-3 md:grid-cols-3">${panel(`<p class="text-xs uppercase tracking-[0.1em] text-pm-muted">${it?'Totale demo':'Demo total'}</p><p class="mt-3 text-3xl font-black text-pm-text">${money(total)}</p>`)}${panel(`<p class="text-xs uppercase tracking-[0.1em] text-pm-muted">${it?'Voci registrate':'Entries'}</p><p class="mt-3 text-3xl font-black text-pm-text">${state.expenses.length}</p>`)}${panel(`<p class="text-xs uppercase tracking-[0.1em] text-pm-muted">${it?'Media movimento':'Average entry'}</p><p class="mt-3 text-3xl font-black text-pm-text">${money(total/state.expenses.length)}</p>`)}</div>
            ${panel(`<div class="flex flex-wrap items-center justify-between gap-3"><div class="flex flex-wrap gap-2">${Object.entries(byCategory).map(([name,value])=>badge(`${name}: ${money(value)}`)).join('')}</div>${disabledAction(it?'Aggiungi spesa':'Add expense')}</div><div class="mt-5 overflow-x-auto"><table class="w-full min-w-[760px] text-left text-sm"><thead class="border-b border-pm-border text-[11px] uppercase tracking-[0.1em] text-pm-muted"><tr><th class="pb-3 pr-4">${it?'Data':'Date'}</th><th class="pb-3 pr-4">${it?'Categoria':'Category'}</th><th class="pb-3 pr-4">${it?'Mezzo':'Vehicle'}</th><th class="pb-3 pr-4">${it?'Nota':'Note'}</th><th class="pb-3 text-right">${it?'Importo':'Amount'}</th></tr></thead><tbody class="divide-y divide-pm-border">${state.expenses.map(e=>`<tr><td class="py-3 pr-4 text-pm-muted">${dateFmt(e.date)}</td><td class="py-3 pr-4 font-semibold text-pm-text">${esc(e.category)}</td><td class="py-3 pr-4 text-pm-text-secondary">${esc(e.vehicle)}</td><td class="py-3 pr-4 text-pm-text-secondary">${esc(e.note)}</td><td class="py-3 text-right font-mono font-semibold text-pm-text">${money(e.amount)}</td></tr>`).join('')}</tbody></table></div>`)}`;
    }

    const renderers = { dashboard:renderDashboard, events:renderEvents, garage:renderGarage, components:renderComponents, configurations:renderConfigurations, setups:renderSetups, circuits:renderCircuits, sessions:renderSessions, maintenance:renderMaintenance, expenses:renderExpenses };
    const validSections = Object.keys(renderers);
    let section = validSections.includes(location.hash.slice(1)) ? location.hash.slice(1) : 'dashboard';

    function updateNav() {
        document.querySelectorAll('[data-public-demo-nav]').forEach(button => {
            const active = button.dataset.publicDemoNav === section;
            button.classList.toggle('bg-white/10', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('text-zinc-400', !active);
            button.setAttribute('aria-current', active ? 'page' : 'false');
        });
        const mobileSelect = document.getElementById('public-demo-section');
        if (mobileSelect) mobileSelect.value = section;
    }

    function render() {
        demoRoot.innerHTML = renderers[section]();
        updateNav();
        demoRoot.querySelectorAll('[data-jump]').forEach(button => button.addEventListener('click', () => navigate(button.dataset.jump)));
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function navigate(next) {
        if (!validSections.includes(next)) return;
        section = next;
        history.replaceState(null, '', `#${section}`);
        render();
    }

    document.querySelectorAll('[data-public-demo-nav]').forEach(button => button.addEventListener('click', () => navigate(button.dataset.publicDemoNav)));
    document.getElementById('public-demo-section')?.addEventListener('change', event => navigate(event.target.value));
    window.addEventListener('hashchange', () => {
        const next = location.hash.slice(1);
        if (validSections.includes(next) && next !== section) {
            section = next;
            render();
        }
    });

    render();
}
