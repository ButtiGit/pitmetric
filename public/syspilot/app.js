/*
 * PitMetric · SysPilot workspace (shared visual language)
 *
 * Routes are hash-based to keep the standalone web UI independent from
 * PitMetric routing. All mutations and access decisions remain server-side.
 *
 * Sections: icons, API, common UI, pages, interactions, bootstrap.
 */
(() => {
    'use strict';

    const API_ROOT = '/api/syspilot';
    const app = document.getElementById('app');
    const flashBox = document.getElementById('flash');

    let bootstrapData = null;
    let busy = false;
    let navigationId = 0;
    const archiveState = { search: '', status: 'all' };
    let currentWorkId = null;
    const saveTasks = new Map();
    let aiPreview = null;

    /* Autosave queue: one in-flight request per step, with a revision guard. */
    function saveIndicator(task, label, error = false) {
        const node = document.querySelector('[data-save-status="' + task.stepId + '"]');
        if (!node) return;
        node.textContent = label;
        node.classList.toggle('error', error);
    }

    function queueStep(form, urgent = false) {
        const task = saveTasks.get(Number(form.dataset.step));
        if (!task) return;
        task.form = form;
        task.pending = {
            state: form.querySelector('[name="state"]').value,
            note: form.querySelector('[name="note"]').value
        };
        task.version++;
        task.dirty = true;
        const printable = form.closest('.check-item')?.querySelector('.print-note');
        if (printable) printable.textContent = 'Esito tecnico: ' + (task.pending.note || 'Non ancora documentato');
        clearTimeout(task.timer);
        if (task.pending.state === 'skipped' && !task.pending.note.trim()) {
            saveIndicator(task, 'Motiva il passaggio saltato', true);
            return;
        }
        saveIndicator(task, 'Modifiche non salvate');
        task.timer = setTimeout(() => persistStep(task).catch(() => {}), urgent ? 0 : 1100);
    }

    async function persistStep(task) {
        clearTimeout(task.timer);
        if (task.sending) {
            await task.sending;
            return task.dirty ? persistStep(task) : undefined;
        }
        if (!task.dirty) return;
        if (task.pending.state === 'skipped' && !task.pending.note.trim()) {
            throw new Error('Aggiungi una motivazione prima di saltare il passaggio.');
        }

        const version = task.version;
        const payload = { ...task.pending };
        if (task.revision) payload.revision = task.revision;
        task.dirty = false;
        saveIndicator(task, 'Salvataggio…');
        // The promise includes revision handling and is shared by concurrent flushes.
        task.sending = (async () => {
            try {
                const response = await api('/interventions/' + task.workId + '/steps/' + task.stepId, 'PATCH', payload);
                task.revision = response.revision || task.revision;
                task.saved = { state: payload.state, note: payload.note };
                if (task.form.isConnected) task.form.dataset.revision = task.revision;
                updateSavedProgress(task);
                if (task.version === version) saveIndicator(task, 'Salvato automaticamente');
            } catch (error) {
                task.dirty = true;
                saveIndicator(task, 'Errore di salvataggio. Riprova.', true);
                throw error;
            }
        })();

        try {
            await task.sending;
        } finally {
            task.sending = null;
            if (task.dirty && task.version > version) {
                task.timer = setTimeout(() => persistStep(task).catch(() => {}), 120);
            }
            updateSavedProgress(task);
        }
    }

    function updateSavedProgress(task) {
        if (task.workId !== currentWorkId || !task.form.isConnected) return;
        const element = task.form.closest('.check-item');
        const tag = element?.querySelector('.check-content .tag');
        if (tag) tag.outerHTML = statusTag(task.saved.state);
        const counter = element?.querySelector('.check-counter');
        if (counter) {
            counter.classList.toggle('done', task.saved.state === 'done');
            counter.innerHTML = task.saved.state === 'done' ? icon('check') : counter.dataset.index;
        }
        const items = [...saveTasks.values()];
        const done = items.filter(item => item.saved.state === 'done').length;
        const skipped = items.filter(item => item.saved.state === 'skipped').length;
        const pending = items.filter(item => ['todo', 'blocked'].includes(item.saved.state)).length;
        const percent = items.length ? Math.round(done / items.length * 100) : 0;
        const caption = document.getElementById('summary-caption');
        if (caption) caption.textContent = pending + ' ancora da risolvere · ' + skipped + ' saltati · ' + percent + '% completati';
        const count = document.getElementById('summary-count');
        if (count) count.textContent = done + ' completati su ' + items.length;
        const bar = document.getElementById('summary-progress');
        if (bar) {
            bar.setAttribute('aria-valuenow', String(percent));
            bar.querySelector('div').style.width = percent + '%';
        }
        const close = document.getElementById('close-job');
        if (close) close.disabled = pending !== 0 || items.some(item => item.dirty || item.sending);
    }

    async function flushPending() {
        for (const task of saveTasks.values()) {
            if (task.dirty || task.sending) {
                clearTimeout(task.timer);
                await persistStep(task);
            }
        }
    }

    window.addEventListener('beforeunload', event => {
        if (![...saveTasks.values()].some(task => task.dirty || task.sending)) return;
        event.preventDefault();
        event.returnValue = '';
    });


    /* Decorative inline symbols share the same stroke and optical size. */
    const paths = {
        home: '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        layers: '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/>',
        plus: '<path d="M12 5v14M5 12h14"/>',
        arrow: '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        back: '<path d="M19 12H5m6-6-6 6 6 6"/>',
        search: '<circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/>',
        file: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/>',
        check: '<path d="m5 12 5 5L20 7"/>',
        clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        briefcase: '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/>',
        server: '<rect x="3" y="3" width="18" height="8" rx="2"/><rect x="3" y="13" width="18" height="8" rx="2"/><path d="M7 7h.01M7 17h.01M11 7h6M11 17h6"/>',
        spark: '<path d="m12 3 1.9 6.1L20 11l-6.1 1.9L12 19l-1.9-6.1L4 11l6.1-1.9L12 3ZM19 18l.7 1.3L21 20l-1.3.7L19 22l-.7-1.3L17 20l1.3-.7L19 18Z"/>',
        shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        alert: '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 17h.01"/>',
        print: '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6zM17 12h.01"/>',
        list: '<path d="M9 6h12M9 12h12M9 18h12M3 6h.01M3 12h.01M3 18h.01"/>',
        dots: '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>'
    };

    function icon(name) {
        return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
            (paths[name] || paths.dots) + '</svg>';
    }

    const escape = value => String(value == null ? '' : value).replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[character]);

    function humanDate(value) {
        if (!value) return '—';
        const result = new Date(String(value).replace(' ', 'T'));
        return isNaN(result.getTime()) ? '—' :
            new Intl.DateTimeFormat('it-IT', { day: '2-digit', month: 'short', year: 'numeric' }).format(result);
    }

    function fullDate(value) {
        if (!value) return '—';
        const result = new Date(String(value).replace(' ', 'T'));
        return isNaN(result.getTime()) ? '—' :
            new Intl.DateTimeFormat('it-IT', { dateStyle: 'short', timeStyle: 'short' }).format(result);
    }

    function statusTag(status) {
        const state = ({
            open: ['open', 'In corso'],
            closed: ['done', 'Concluso'],
            todo: ['neutral', 'Da fare'],
            done: ['done', 'Completato'],
            blocked: ['blocked', 'Bloccato'],
            skipped: ['skipped', 'Saltato']
        })[status] || ['neutral', 'Non definito'];

        return '<span class="tag ' + state[0] + '"><span></span>' + state[1] + '</span>';
    }

    function heading(category, title, subtitle, actions) {
        return '<header class="page-heading"><div><div class="eyebrow">' + escape(category) +
            '</div><h1>' + escape(title) + '</h1><p>' + escape(subtitle) + '</p></div>' +
            '<div class="heading-tools">' + (actions || '') + '</div></header>';
    }

    function button(label, href, primary, symbol) {
        return '<a class="button ' + (primary ? 'primary' : 'quiet') + '" href="' + href + '">' +
            (symbol ? icon(symbol) : '') + escape(label) + '</a>';
    }

    function emptyState(title, body, action, glyph) {
        return '<div class="empty-state"><div class="empty-icon">' + icon(glyph || 'layers') +
            '</div><h3>' + escape(title) + '</h3><p>' + escape(body) + '</p>' +
            (action || '') + '</div>';
    }

    function flash(message, error) {
        flashBox.textContent = message;
        flashBox.className = 'flash' + (error ? ' error' : '');
        flashBox.hidden = false;
    }

    function clearFlash() {
        flashBox.textContent = '';
        flashBox.hidden = true;
    }

    function setBusy(form, active, message) {
        const submit = form && form.querySelector('button[type="submit"]');
        if (!submit) return;
        if (active) {
            submit.dataset.label = submit.innerHTML;
            submit.textContent = message || 'Elaborazione in corso…';
        } else if (submit.dataset.label) {
            submit.innerHTML = submit.dataset.label;
        }
        submit.disabled = active;
    }

    /* Same-origin requests; the OpenAI key never leaves the PHP server. */
    async function api(path, method, payload) {
        const options = {
            method: method || 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        };

        if (payload !== undefined) {
            options.headers['Content-Type'] = 'application/json';
            options.headers['X-CSRF-TOKEN'] = bootstrapData ? bootstrapData.csrf_token : '';
            options.body = JSON.stringify(payload);
        }

        let response;
        try {
            response = await fetch(API_ROOT + path, options);
        } catch (_error) {
            const failure = new Error('Connessione al server non disponibile. Controlla la rete e riprova.');
            failure.status = 0;
            throw failure;
        }

        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validation = result.errors && Object.values(result.errors)[0];
            const firstError = validation && validation[0];
            const fallback = ({
                401: 'Sessione non autenticata.',
                403: 'Accesso al database non autorizzato.',
                419: 'Sessione scaduta: aggiorna la pagina.',
                500: 'Il backend ha riscontrato un errore. Controlla i log Laravel e le migrazioni SysPilot.',
                503: 'Il servizio è temporaneamente non disponibile.'
            })[response.status] || 'Impossibile completare la richiesta (HTTP ' + response.status + ').';

            const message = response.status >= 500 ? fallback : (firstError || result.message || fallback);
            const failure = new Error(message);
            failure.status = response.status;
            throw failure;
        }

        return result;
    }

    async function refresh() {
        bootstrapData = await api('/bootstrap');
        const user = bootstrapData.user && bootstrapData.user.name ? bootstrapData.user.name : 'Account PitMetric';
        document.getElementById('sidebar-user').textContent = user;
        document.getElementById('user-avatar').textContent = user.trim().substring(0, 1).toUpperCase() || 'S';

        const recent = (bootstrapData.interventions || []).filter(item => item.status === 'open').slice(0, 5);
        document.getElementById('recent-nav').innerHTML = recent.length ?
            recent.map(item => '<a class="recent-link" href="#work/' + Number(item.id) +
                '"><span class="recent-pip"></span><span><strong>' + escape(item.title) +
                '</strong><small>#' + Number(item.id) + ' · ' +
                escape(item.client || 'Nessun cliente') + '</small></span></a>').join('') :
            '<span class="muted" style="margin:4px 12px;font-size:12px">Nessuna attività aperta</span>';
    }

    /* A reusable table serves both dashboard and archive. */
    function recordsTable(records) {
        if (!records.length) return emptyState(
            'Nessun intervento trovato',
            'Non ci sono registrazioni che corrispondono ai criteri scelti.',
            '',
            'search'
        );

        const rows = records.map(item =>
            '<tr><td><a class="record-name" href="#work/' + Number(item.id) + '">' +
            escape(item.title) + '</a><span class="record-id">SP-' + String(Number(item.id)).padStart(4, '0') +
            '</span></td><td>' + escape(item.client || '—') +
            '<span class="record-id">' + escape(item.asset || 'Asset non specificato') +
            '</span></td><td class="date">' + humanDate(item.created_at) +
            '</td><td>' + statusTag(item.status) +
            '</td><td><a class="text-link" href="#work/' + Number(item.id) +
            '" aria-label="Apri intervento ' + Number(item.id) + '">' + icon('arrow') + '</a></td></tr>'
        ).join('');

        return '<div class="table-wrap"><table class="records"><thead><tr>' +
            '<th>INTERVENTO</th><th>CLIENTE / ASSET</th><th>CREATO</th><th>STATO</th><th><span class="muted">APRI</span></th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table></div>';
    }

    /* SCREEN 1 — Overview */
    function renderHome() {
        const data = bootstrapData;
        const recent = data.interventions || [];
        const open = recent.find(item => item.status === 'open');

        const metrics = [
            ['Interventi registrati', data.counts.total, 'Nel tuo workspace', 'layers'],
            ['Interventi aperti', data.counts.open, 'Da portare a termine', 'clock'],
            ['Interventi conclusi', data.counts.closed, 'Report consultabili', 'check']
        ];

        const cards = metrics.map(metric =>
            '<article class="panel metric"><div class="metric-head"><span>' +
            metric[0] + '</span>' + icon(metric[3]) + '</div><div class="metric-number">' +
            Number(metric[1] || 0) + '</div><div class="metric-note">' + metric[2] +
            '</div></article>'
        ).join('');

        const resume = open ?
            '<div class="panel resume-card"><div class="eyebrow">RIPRENDI DA QUI</div>' +
            '<div class="resume-title">' + escape(open.title) +
            '</div><div class="resume-description">' + escape(open.client || 'Intervento interno') +
            ' · SP-' + String(Number(open.id)).padStart(4, '0') + '</div>' +
            '<div class="resume-bottom"><span class="tag open"><span></span>Da completare</span>' +
            '<a class="text-link" href="#work/' + Number(open.id) + '">Apri intervento ' +
            icon('arrow') + '</a></div></div>' :
            '<div class="panel resume-card"><div class="eyebrow">TUTTO IN ORDINE</div>' +
            '<div class="resume-title">Nessun intervento aperto</div>' +
            '<div class="resume-description">Quando avvierai un lavoro, potrai riprenderlo da questo spazio.</div>' +
            '<div class="resume-bottom"><span class="muted">Workspace aggiornato</span>' +
            '<a class="text-link" href="#new">Crea il prossimo ' + icon('arrow') + '</a></div></div>';

        app.innerHTML = heading('PITMETRIC / SYSPILOT', 'Il tuo lavoro, a colpo d’occhio.',
            'Interventi, attività aperte e documentazione: tutto in un posto.',
            button('Nuovo intervento', '#new', true, 'plus')) +
            '<section class="metric-grid" aria-label="Statistiche interventi">' + cards + '</section>' +
            '<section class="overview-grid" aria-label="Prossime azioni">' +
            resume +
            '<aside class="panel guide-card">' + icon('spark') + '<h3>Un intervento, tre passaggi.</h3>' +
            '<p>Descrivi cosa devi fare. L’IA organizza il lavoro. Tu registri l’esito e ottieni un report.</p>' +
            '<a class="text-link" href="#new">Inizia con SysPilot ' + icon('arrow') + '</a></aside>' +
            '</section>' +
            '<div class="section-heading"><div><h2>Attività recenti</h2>' +
            '<p>Gli ultimi interventi del tuo account</p></div>' +
            '<a class="text-link" href="#archive">Apri archivio ' + icon('arrow') + '</a></div>' +
            '<section class="panel">' + (recent.length ?
                recordsTable(recent.slice(0, 6)) :
                emptyState('Il tuo archivio è vuoto',
                    'Crea il primo intervento: i report e le checklist compilate compariranno qui.',
                    button('Crea intervento', '#new', true, 'plus'), 'file')) +
            '</section>';
    }

    /* SCREEN 2 — New intervention */
    function renderNew() {
        const configured = Boolean(bootstrapData.configured);
        const examples = [
            ['server', 'Diagnostica server', 'Verificare risorse, log e servizi di un server che risulta lento.'],
            ['shield', 'Aggiornamento sicurezza', 'Aggiornare un sistema operativo e verificare backup, compatibilità e rollback.'],
            ['briefcase', 'Nuova postazione', 'Preparare un PC Windows, configurare l’account aziendale, Office e stampanti.'],
            ['server', 'Proiettore già installato', 'Oggi ho montato e sistemato il proiettore della sala riunioni. Devo ancora collegarlo alla rete aziendale e verificarne la connessione.']
        ];

        app.innerHTML = heading('INTERVENTI / NUOVO', 'Comincia dall’obiettivo.',
            'Descrivi il lavoro con parole tue. Alla struttura e all’ordine delle attività pensa SysPilot.',
            '<span class="tag open"><span></span>AI ASSISTED</span>') +
            '<div class="new-grid">' +
            '<section class="panel"><div class="panel-header"><div><h2>Dettagli dell’intervento</h2>' +
            '<p>Solo le informazioni necessarie per iniziare.</p></div>' + icon('file') + '</div>' +
            '<div class="panel-body">' +
            (!configured ? '<div class="notice error" style="margin-bottom:23px">' + icon('alert') +
                '<span>OpenAI non è ancora configurata. Aggiungi la chiave SYSPILOT_OPENAI_API_KEY nelle variabili di Coolify.</span></div>' : '') +
            '<form id="create-form">' +
            '<label class="field-label" for="request">Cosa devi fare? <span aria-hidden="true">*</span></label>' +
            '<textarea class="field-input" id="request" name="request" required minlength="15" maxlength="2500" placeholder="Per esempio: devo preparare un server Ubuntu per ospitare un gestionale. Configurare web server, database, backup, firewall e certificato SSL."></textarea>' +
            '<p class="field-note">Indica anche cosa hai già fatto: SysPilot distinguerà le attività concluse da quelle da completare. Evita password e dati riservati.</p>' +
            '<div class="field-grid" style="margin-top:24px">' +
            '<div><label class="field-label" for="client">Cliente <small>· facoltativo</small></label>' +
            '<input class="field-input" id="client" name="client" maxlength="120" placeholder="Es. Azienda Demo"></div>' +
            '<div><label class="field-label" for="asset">Asset o dispositivo <small>· facoltativo</small></label>' +
            '<input class="field-input" id="asset" name="asset" maxlength="120" placeholder="Es. SRV-PROD-01"></div></div>' +
            '<div class="form-actions"><p>Le attività create dall’IA saranno sempre verificabili e modificabili in seguito.</p>' +
            '<button class="button primary" type="submit"' + (configured ? '' : ' disabled') + '>' +
            icon('spark') + ' Genera checklist</button></div></form></div></section>' +
            '<aside class="right-rail"><section class="panel"><div class="panel-body">' +
            '<div class="eyebrow">COME FUNZIONA</div><h2 style="margin-top:10px">La parte noiosa la facciamo noi.</h2>' +
            '<ol class="how-list"><li><span class="how-number">01</span><span>Descrivi il risultato che vuoi ottenere.</span></li>' +
            '<li><span class="how-number">02</span><span>L’IA suddivide il lavoro in fasi ordinate.</span></li>' +
            '<li><span class="how-number">03</span><span>Durante l’intervento registri gli esiti. Il report si compone da solo.</span></li></ol>' +
            '<div class="model-chip" style="margin-top:22px">MODELLO · ' + escape(bootstrapData.model || 'Non configurato') + '</div>' +
            '</div></section><section class="panel"><div class="panel-body">' +
            '<h2>Parti da un esempio</h2><p class="field-note">Un clic compila la descrizione, senza avviare l’IA.</p>' +
            '<div class="examples">' + examples.map((example, index) =>
                '<button type="button" class="example" data-example="' + index + '">' +
                icon(example[0]) + escape(example[1]) + '</button>'
            ).join('') + '</div></div></section></aside></div>' +
            '<p class="field-note" style="margin-top:18px">SysPilot non esegue comandi: propone procedure. Ogni passaggio deve essere validato da un tecnico.</p>';

        app.dataset.examples = JSON.stringify(examples.map(example => example[2]));
    }

    /* SCREEN 3 — Archive. The backend currently returns only the latest 40. */
    function renderArchive() {
        app.innerHTML = heading('INTERVENTI / ARCHIVIO', 'Ritrova ogni intervento.',
            'Uno storico consultabile, con ricerca rapida per titolo, cliente, dispositivo e ID.',
            button('Nuovo intervento', '#new', true, 'plus')) +
            '<div class="archive-bar no-print"><label class="search-wrap" aria-label="Cerca negli interventi">' +
            icon('search') + '<input id="archive-search" class="field-input" type="search" placeholder="Cerca per intervento, cliente o asset…" autocomplete="off" value="' + escape(archiveState.search) + '"></label>' +
            '<select id="archive-status" class="field-input" aria-label="Filtra per stato">' +
            '<option value="all">Tutti gli stati</option><option value="open">In corso</option><option value="closed">Conclusi</option></select></div>' +
            '<section class="panel" id="archive-results"></section>' +
            '<p class="archive-caption">Mostriamo al massimo gli ultimi 40 interventi caricati. La paginazione completa arriverà in una versione successiva.</p>';

        document.getElementById('archive-status').value = archiveState.status;
        updateArchive();
    }

    function updateArchive() {
        const term = archiveState.search.toLocaleLowerCase('it').trim();
        const records = (bootstrapData.interventions || []).filter(item => {
            if (archiveState.status !== 'all' && item.status !== archiveState.status) return false;
            const searchable = [item.title, item.client, item.asset, 'SP-' + String(Number(item.id)).padStart(4, '0')].join(' ').toLocaleLowerCase('it');
            return !term || searchable.includes(term);
        });

        document.getElementById('archive-results').innerHTML = records.length ?
            recordsTable(records) :
            emptyState('Nessun risultato', 'Prova a cambiare ricerca o filtro per visualizzare altri interventi.', '', 'search');
    }

    /* An AI revision is a reviewable suggestion, not an automatic rewrite. */
    function aiEditorMarkup() {
        return '<section class="panel ai-editor no-print">' +
            '<header class="panel-header"><div><div class="eyebrow">EDITOR IA</div>' +
            '<h2 style="margin-top:8px">La checklist può evolvere.</h2>' +
            '<p>Aggiungi, correggi, sposta o rimuovi attività senza ricominciare da zero.</p></div>' +
            icon('spark') + '</header><div class="panel-body">' +
            '<form id="ai-form"><label for="ai-instruction" class="field-label">Che cosa vuoi cambiare?</label>' +
            '<textarea id="ai-instruction" name="instruction" class="field-input" minlength="8" maxlength="750" required rows="2" placeholder="Es.: Aggiungi il controllo del backup prima del riavvio, senza toccare i passaggi completati."></textarea>' +
            '<div class="ai-editor-actions"><span class="field-note">Mostriamo sempre un’anteprima prima di applicare qualsiasi modifica.</span>' +
            '<button class="button primary" type="submit">' + icon('spark') +
            ' Proponi modifiche</button></div></form>' +
            '<div id="ai-review" class="ai-review" aria-live="polite"></div>' +
            '</div></section>';
    }

    function renderAiPreview(proposal) {
        const target = document.getElementById('ai-review');
        if (!target) return;
        const labels = { add: 'AGGIUNTA', edit: 'MODIFICA', remove: 'RIMOZIONE', move: 'SPOSTAMENTO' };
        target.innerHTML = '<div class="proposal-heading"><strong>' + escape(proposal.summary) +
            '</strong><span class="mono muted">' + proposal.changes.length + ' CAMBIAMENTI</span></div>' +
            '<ol class="proposal-list">' + proposal.changes.map(change =>
                '<li><span class="tag neutral">' + labels[change.action] +
                '</span><div><strong>' + escape(change.label) + '</strong>' +
                (change.parent_title ? '<p class="proposal-location">Sotto-attività di: ' +
                    escape(change.parent_title) + '</p>' : '') +
                (change.detail ? '<p>' + escape(change.detail) + '</p>' : '') +
                (change.location ? '<p class="proposal-location">' + escape(change.location) + '</p>' : '') +
                (change.reason ? '<p>' + escape(change.reason) + '</p>' : '') +
                '</div></li>').join('') + '</ol>' +
            '<p class="field-note">I passaggi completati e le note esistenti rimangono protetti. La proposta scade dopo 10 minuti.</p>' +
            '<div class="proposal-actions"><button type="button" class="button quiet" id="cancel-ai">Annulla</button>' +
            '<button type="button" class="button primary" id="apply-ai">' +
            'Conferma modifiche</button></div>';
    }

    /* SCREEN 4 — Intervention workspace */
    async function renderWork(id, sequence) {
        app.innerHTML = '<div class="skeleton-page"><span></span><span></span><span></span></div>';
        const data = await api('/interventions/' + id);
        if (sequence !== undefined && sequence !== navigationId) return;

        const job = data.intervention;
        const incoming = data.steps || [];
        const stepById = new Map(incoming.map(step => [Number(step.id), step]));
        const children = new Map();
        for (const step of incoming) {
            const parent = Number(step.parent_step_id || 0);
            if (!parent || !stepById.has(parent)) continue;
            if (!children.has(parent)) children.set(parent, []);
            children.get(parent).push(step);
        }
        const steps = [];
        for (const step of incoming) {
            if (Number(step.parent_step_id || 0) && stepById.has(Number(step.parent_step_id))) continue;
            steps.push(step);
            steps.push(...(children.get(Number(step.id)) || []));
        }
        const closed = job.status === 'closed';
        const completed = steps.filter(step => step.state === 'done').length;
        const skipped = steps.filter(step => step.state === 'skipped').length;
        const remaining = steps.filter(step => step.state === 'todo' || step.state === 'blocked').length;
        const percent = steps.length ? Math.round(completed / steps.length * 100) : 0;

        let phases = '';
        let activePhase = '';
        let group = '';
        let phaseCount = 0;

        steps.forEach((step, index) => {
            if (step.phase !== activePhase) {
                if (activePhase) phases += group + '</div></section>';
                activePhase = step.phase;
                phaseCount++;
                group = '<section class="panel phase-card"><header class="phase-heading"><h3>' +
                    escape(activePhase) + '</h3><span class="mono">FASE ' +
                    String(phaseCount).padStart(2, '0') + '</span></header><div>';
            }

            const isChild = Number(step.parent_step_id || 0) > 0 && stepById.has(Number(step.parent_step_id));
            group += '<article class="check-item' + (isChild ? ' check-substep' : '') +
                '"><div class="check-top"><div class="check-counter ' +
                (step.state === 'done' ? 'done' : '') + '" data-index="' + String(index + 1).padStart(2, '0') + '">' +
                (step.state === 'done' ? icon('check') : String(index + 1).padStart(2, '0')) +
                '</div><div class="check-content">' +
                (isChild ? '<span class="substep-caption">SOTTO-ATTIVITÀ</span>' : '') +
                '<h3>' + escape(step.title) +
                '</h3><p>' + escape(step.detail || '') + '</p>' + statusTag(step.state) +
                (String(step.note || '').startsWith('Dichiarato già svolto nella richiesta:')
                    ? '<p class="initial-completion">' + icon('check') +
                        ' Dichiarato come già svolto nella richiesta. Verifica che sia corretto.</p>'
                    : '') +
                (!closed && !isChild && ['todo', 'blocked'].includes(step.state)
                    ? '<button type="button" class="subcheck-action no-print" data-expand-step="' +
                        Number(step.id) + '" data-expand-title="' + escape(step.title) +
                        '">' + icon('spark') + ' Approfondisci con IA</button>'
                    : '') +
                (closed ? '<div class="readonly-note">Esito: ' + escape(step.note || 'Nessuna nota registrata') + '</div>' : '') +
                '</div></div>';

            if (!closed) {
                group += '<div class="print-note">Esito tecnico: ' + escape(step.note || 'Non ancora documentato') + '</div>' +
                    '<form class="step-form" data-step="' + Number(step.id) +
                    '" data-revision="' + escape(step.revision || '') + '">' +
                    '<div><label class="field-label" for="state-' + Number(step.id) + '">Stato</label>' +
                    '<select class="field-input" id="state-' + Number(step.id) + '" name="state">' +
                    ['todo', 'done', 'blocked', 'skipped'].map(value => '<option value="' + value + '"' +
                        (step.state === value ? ' selected' : '') + '>' +
                        ({ todo: 'Da fare', done: 'Completato', blocked: 'Bloccato', skipped: 'Saltato' })[value] +
                        '</option>').join('') + '</select></div>' +
                    '<div class="note-field"><label class="field-label" for="note-' + Number(step.id) + '">Note e risultato</label>' +
                    '<textarea class="field-input" id="note-' + Number(step.id) +
                    '" name="note" maxlength="3000" rows="2" placeholder="Registra cosa hai verificato…">' +
                    escape(step.note || '') + '</textarea>' +
                    '<div class="quick-note-row no-print"><span class="muted">NOTE RAPIDE</span>' +
                    '<button type="button" data-quick-note="Verifica effettuata.">Verificato</button>' +
                    '<button type="button" data-quick-note="Da verificare: ">Da verificare</button>' +
                    '<button type="button" data-quick-note="Anomalia rilevata: ">Anomalia</button></div></div>' +
                    '<div class="save-tools no-print"><span class="save-status" data-save-status="' +
                    Number(step.id) + '" aria-live="polite">Salvataggio automatico</span>' +
                    '<button type="submit" class="button quiet small">Salva ora</button></div></form>';
            }
            group += '</article>';
        });
        if (activePhase) phases += group + '</div></section>';

        const timeline = (data.events || []).map(item =>
            '<li><p>' + escape(item.message) + '</p><time>' +
            fullDate(item.created_at) + '</time></li>'
        ).join('');

        const finalAction = closed ?
            '<button type="button" class="button primary" data-print="true">' + icon('print') +
            ' Stampa / Salva PDF</button>' :
            '<button id="close-job" type="button" class="button primary"' +
            (remaining ? ' disabled title="Completa o salta tutti i passaggi prima di chiudere"' : '') +
            '>' + icon('check') + ' Chiudi intervento</button>';

        app.innerHTML = '<a href="#archive" class="back-link no-print">' + icon('back') +
            ' Tutti gli interventi</a>' +
            heading('INTERVENTO / SP-' + String(Number(job.id)).padStart(4, '0'), job.title,
                closed ? 'Report consultabile · intervento chiuso dal tecnico' :
                    'Workspace operativo · documenta gli esiti mentre lavori',
                statusTag(job.status)) +
            '<div class="detail-meta"><span>' + icon('briefcase') + escape(job.client || 'Cliente non specificato') +
            '</span><span>' + icon('server') + escape(job.asset || 'Asset non specificato') +
            '</span><span>' + icon('clock') + fullDate(job.created_at) + '</span></div>' +
            '<section class="panel summary-bar" style="margin-top:24px"><div class="summary-content">' +
            '<div class="summary-line"><strong>Stato di avanzamento</strong><span id="summary-count">' + completed +
            ' completati su ' + steps.length + '</span></div>' +
            '<div class="progress" id="summary-progress" role="progressbar" aria-label="Passaggi completati" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' +
            percent + '"><div style="width:' + percent + '%"></div></div>' +
            '<div class="summary-caption" id="summary-caption">' + remaining + ' ancora da risolvere · ' +
            skipped + ' saltati · ' + percent + '% completati</div></div>' +
            '<button type="button" class="button quiet no-print" data-print="true">' +
            icon('print') + ' Esporta report</button></section>' +
            '<div class="detail-grid"><div>' +
            (closed ? '' : aiEditorMarkup()) +
            '<div class="section-heading"><div><h2>Checklist operativa</h2><p>Segui le fasi e registra ogni verifica.</p></div>' +
            '<span class="mono muted">' + steps.length + ' PASSAGGI</span></div>' +
            (phases || emptyState('Checklist vuota', 'Non sono stati creati passaggi per questo intervento.', '', 'list')) +
            '</div><aside class="detail-rail">' +
            '<section class="panel"><div class="panel-header"><h2>Informazioni</h2></div>' +
            '<div class="panel-body"><dl class="context-list">' +
            '<div><dt>CLIENTE</dt><dd>' + escape(job.client || 'Non specificato') + '</dd></div>' +
            '<div><dt>DISPOSITIVO</dt><dd>' + escape(job.asset || 'Non specificato') + '</dd></div>' +
            '<div><dt>DATA</dt><dd>' + fullDate(job.created_at) + '</dd></div></dl></div></section>' +
            '<section class="panel"><div class="panel-header"><h2>Richiesta iniziale</h2></div>' +
            '<div class="panel-body"><p class="rail-text">' + escape(job.request) + '</p></div></section>' +
            '<section class="panel no-print"><div class="panel-header"><h2>Azioni</h2></div>' +
            '<div class="panel-body"><div class="rail-actions">' + finalAction +
            '<a href="#archive" class="button quiet">Torna all’archivio</a></div>' +
            (remaining ? '<p class="field-note" style="margin-top:12px">Per chiudere, completa o salta con motivazione gli altri passaggi.</p>' : '') +
            '</div></section><section class="panel"><div class="panel-header"><h2>Registro attività</h2>' +
            '<span class="mono muted">' + (data.events || []).length + '</span></div>' +
            '<div class="panel-body">' +
            (timeline ? '<ol class="event-list">' + timeline + '</ol>' :
                '<p class="rail-text">Le operazioni documentate compariranno qui.</p>') +
            '</div></section></aside></div>';

        currentWorkId = Number(id);
        saveTasks.clear();
        if (!closed) {
            for (const step of steps) {
                const form = document.querySelector('.step-form[data-step="' + Number(step.id) + '"]');
                if (!form) continue;
                saveTasks.set(Number(step.id), {
                    stepId: Number(step.id), workId: Number(id),
                    revision: step.revision || '', form,
                    pending: { state: step.state, note: step.note || '' },
                    saved: { state: step.state, note: step.note || '' },
                    dirty: false, sending: null, timer: null, version: 0
                });
            }
            if (aiPreview?.workId === Number(id)) renderAiPreview(aiPreview);
        }
    }

    /* Navigation and feedback */
    function renderAccessProblem(error) {
        const status = Number(error.status || 0);
        const unauthorized = status === 401;
        const forbidden = status === 403;
        const title = unauthorized ? 'Accedi al tuo account.' :
            forbidden ? 'Permessi insufficienti.' :
                'Impossibile caricare il workspace.';
        const description = unauthorized ?
            'Per entrare in SysPilot utilizza un account PitMetric con email verificata.' :
            forbidden ?
                'Il tuo account potrebbe non avere accesso al database oppure avere un’appartenenza al team sospesa.' :
                'La richiesta non è stata completata dal server. Il problema non indica necessariamente un errore dei permessi.';

        const detail = unauthorized ? 'Autenticazione richiesta' :
            forbidden ? 'HTTP 403 · Verifica i permessi PitMetric e lo stato del team.' :
                'HTTP ' + (status || '—') +
                ' · Controlla i log Laravel e che siano state eseguite le migrazioni delle tabelle SysPilot.';

        app.innerHTML = '<section class="panel access-panel"><div class="eyebrow">PITMETRIC / SYSPILOT</div>' +
            '<h1>' + title + '</h1><p>' + description + '</p>' +
            '<div class="notice ' + (forbidden ? 'error' : '') + '" style="margin-top:20px">' +
            icon('alert') + '<span>' + escape(detail) + '</span></div>' +
            '<div class="heading-tools" style="margin-top:20px">' +
            '<button type="button" class="button primary" id="retry-app">Riprova</button>' +
            '<a class="button quiet" href="' + (unauthorized ? '/login' : '/dashboard') + '">' +
            (unauthorized ? 'Accedi a PitMetric' : 'Torna a PitMetric') + '</a></div></section>';
    }

    function updateNavigation(hash) {
        const section = hash === 'new' ? 'new' : hash === 'archive' ? 'archive' : 'home';
        const names = { home: 'Panoramica', archive: 'Interventi', new: 'Nuovo intervento' };
        const page = hash.startsWith('work/') ? 'Dettaglio intervento' : names[section];
        document.getElementById('topbar-page').textContent = page;
        document.querySelectorAll('[data-nav], [data-mobile]').forEach(link => {
            const active = (link.dataset.nav || link.dataset.mobile) === section;
            link.classList.toggle('active', active);
            if (active) link.setAttribute('aria-current', 'page');
            else link.removeAttribute('aria-current');
        });
    }

    async function navigate() {
        if (!bootstrapData) return;
        const hash = location.hash.replace(/^#/, '') || 'home';
        if (currentWorkId !== null && hash !== 'work/' + currentWorkId) {
            try {
                await flushPending();
            } catch (error) {
                flash('Salvataggio incompleto: ' + error.message, true);
                location.hash = 'work/' + currentWorkId;
                return;
            }
            currentWorkId = null;
            saveTasks.clear();
            aiPreview = null;
        }
        clearFlash();
        const seq = ++navigationId;
        updateNavigation(hash);

        try {
            if (hash === 'new') renderNew();
            else if (hash === 'archive') renderArchive();
            else if (/^work\/[1-9]\d*$/.test(hash)) await renderWork(Number(hash.slice(5)), seq);
            else renderHome();
        } catch (error) {
            if (seq !== navigationId) return;
            app.innerHTML = '<section class="panel access-panel"><h2>Impossibile aprire l’intervento</h2>' +
                '<p>' + escape(error.message) + '</p>' +
                '<a href="#archive" class="button quiet">Torna all’archivio</a></section>';
        }
        window.scrollTo({ top: 0, behavior: 'auto' });
    }

    /* One delegated handler per interaction family: safe after rerenders. */
    document.addEventListener('submit', async event => {
        const form = event.target;
        if (form.id !== 'create-form' && form.id !== 'ai-form' && !form.matches('.step-form')) return;
        event.preventDefault();

        if (form.matches('.step-form')) {
            const task = saveTasks.get(Number(form.dataset.step));
            if (!task) return;
            queueStep(form, true);
            persistStep(task).catch(error => flash(error.message, true));
            return;
        }

        if (busy) return;
        busy = true;
        setBusy(form, true, form.id === 'create-form' ? 'Generazione in corso…' : 'Preparazione proposta…');

        try {
            if (form.id === 'create-form') {
                const fields = new FormData(form);
                const response = await api('/interventions', 'POST', {
                    request: fields.get('request'),
                    client: fields.get('client'),
                    asset: fields.get('asset')
                });
                await refresh();
                location.hash = 'work/' + Number(response.id);
                await navigate();
                flash('Checklist pronta. Verifica i passaggi prima di iniziare.', false);
            } else if (form.id === 'ai-form') {
                await flushPending();
                const data = new FormData(form);
                const id = currentWorkId;
                aiPreview = null;
                const review = document.getElementById('ai-review');
                if (review) review.innerHTML = '<p class="field-note">Preparazione della proposta in corso…</p>';
                const result = await api('/interventions/' + id + '/ai/propose', 'POST', {
                    instruction: data.get('instruction')
                });
                aiPreview = { ...result, workId: id };
                renderAiPreview(aiPreview);
            }
        } catch (error) {
            flash(error.message, true);
        } finally {
            busy = false;
            if (form.isConnected) setBusy(form, false);
        }
    });

    document.addEventListener('input', event => {
        if (event.target.id === 'archive-search') {
            archiveState.search = event.target.value;
            updateArchive();
            return;
        }
        if (event.target.matches('.step-form textarea[name="note"]')) {
            queueStep(event.target.closest('.step-form'));
        }
    });

    document.addEventListener('change', event => {
        if (event.target.id === 'archive-status') {
            archiveState.status = event.target.value;
            updateArchive();
            return;
        }
        if (event.target.matches('.step-form select[name="state"]')) {
            queueStep(event.target.closest('.step-form'), true);
        }
    });

    document.addEventListener('click', async event => {
        if (event.target.closest('.skip-link')) {
            event.preventDefault();
            document.getElementById('main-content').focus();
            return;
        }
        const template = event.target.closest('[data-example]');
        if (template) {
            const items = JSON.parse(app.dataset.examples || '[]');
            const input = document.getElementById('request');
            if (input) {
                input.value = items[Number(template.dataset.example)] || '';
                input.focus();
            }
            return;
        }

        const quick = event.target.closest('[data-quick-note]');
        if (quick) {
            const textarea = quick.closest('.step-form')?.querySelector('[name="note"]');
            if (textarea) {
                const separator = textarea.value.trim() ? (textarea.value.endsWith('\n') ? '' : '\n') : '';
                textarea.value += separator + quick.dataset.quickNote;
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
                textarea.focus();
            }
            return;
        }

        const expand = event.target.closest('[data-expand-step]');
        if (expand) {
            const editor = document.getElementById('ai-instruction');
            if (editor) {
                const id = Number(expand.dataset.expandStep);
                const title = expand.dataset.expandTitle || 'questo controllo';
                editor.value = 'Approfondisci il passaggio ID ' + id + ' «' + title +
                    '» con 3 sotto-attività pratiche (action=add, parent_step_id=' + id +
                    '). Indica verifiche concrete, senza segnare operazioni come già eseguite.';
                editor.focus();
                editor.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        if (event.target.closest('#cancel-ai')) {
            aiPreview = null;
            const panel = document.getElementById('ai-review');
            if (panel) panel.innerHTML = '';
            return;
        }

        const apply = event.target.closest('#apply-ai');
        if (apply) {
            if (busy || !aiPreview || aiPreview.workId !== currentWorkId) return;
            busy = true;
            apply.disabled = true;
            try {
                await flushPending();
                const id = currentWorkId;
                await api('/interventions/' + id + '/ai/apply', 'POST', { token: aiPreview.token });
                aiPreview = null;
                await refresh();
                await renderWork(id);
                flash('Checklist modificata. Stati e note esistenti sono rimasti invariati.', false);
            } catch (error) {
                flash(error.message, true);
                if (apply.isConnected) apply.disabled = false;
            } finally {
                busy = false;
            }
            return;
        }

        if (event.target.closest('[data-print]')) {
            flushPending().then(() => window.print()).catch(error => flash(error.message, true));
            return;
        }

        if (event.target.closest('#retry-app')) {
            location.reload();
            return;
        }

        const close = event.target.closest('#close-job');
        if (!close || busy) return;
        if (!confirm('Chiudere questo intervento? Le note e i passaggi non saranno più modificabili.')) return;

        busy = true;
        close.disabled = true;
        try {
            await flushPending();
            const id = Number(location.hash.replace('#work/', ''));
            await api('/interventions/' + id + '/close', 'POST', {});
            await refresh();
            await renderWork(id);
            flash('Intervento chiuso. Il report può essere stampato o salvato in PDF.', false);
        } catch (error) {
            flash(error.message, true);
            if (close.isConnected) close.disabled = false;
        } finally {
            busy = false;
        }
    });

    window.addEventListener('hashchange', navigate);

    /* Startup: never report a generic HTTP 500 as a permissions problem. */
    async function initialize() {
        try {
            await refresh();
            document.querySelectorAll('[data-icon]').forEach(node => {
                node.innerHTML = icon(node.dataset.icon);
            });
            await navigate();
        } catch (error) {
            renderAccessProblem(error);
        }
    }
    initialize();
})();
