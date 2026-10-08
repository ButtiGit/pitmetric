/* SysPilot MVP: same-origin Laravel API, no API key in the browser. */
(() => {
  'use strict';

  const API = '/api/syspilot';
  const root = document.getElementById('app');
  const flashBox = document.getElementById('flash');
  let boot = null;
  let busy = false;

  const escape = value => String(value == null ? '' : value).replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[c]);
  const date = value => value ? new Date(String(value).replace(' ', 'T')).toLocaleString('it-IT') : '—';
  const stateLabel = value => ({ todo: 'Da fare', done: 'Completato', blocked: 'Bloccato', skipped: 'Saltato' }[value] || 'Da fare');
  const header = (eyebrow, title, description, extra) =>
    '<div class="header-row"><div><div class="eyebrow">' + escape(eyebrow) +
    '</div><h1>' + escape(title) + '</h1><p class="subtitle">' + escape(description) +
    '</p></div>' + (extra || '') + '</div>';

  function flash(message, isError) {
    flashBox.textContent = message;
    flashBox.className = 'flash' + (isError ? ' error' : '');
    flashBox.hidden = false;
    flashBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
  function clearFlash() { flashBox.hidden = true; flashBox.textContent = ''; }

  async function api(path, method, data) {
    const options = {
      method: method || 'GET',
      credentials: 'same-origin',
      headers: { Accept: 'application/json' }
    };
    if (data !== undefined) {
      options.headers['Content-Type'] = 'application/json';
      options.headers['X-CSRF-TOKEN'] = boot.csrf_token;
      options.body = JSON.stringify(data);
    }
    let response;
    try {
      response = await fetch(API + path, options);
    } catch (e) {
      throw new Error('Impossibile raggiungere il server. Controlla la connessione.');
    }
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const firstValidation = payload.errors && Object.values(payload.errors)[0];
      const message = (firstValidation && firstValidation[0]) || payload.message ||
        (response.status === 419 ? 'Sessione scaduta. Aggiorna la pagina.' : 'Errore HTTP ' + response.status);
      const err = new Error(message);
      err.status = response.status;
      throw err;
    }
    return payload;
  }

  async function refresh() {
    boot = await api('/bootstrap');
    document.getElementById('sidebar-user').textContent = boot.user.name;
    document.getElementById('recent-nav').innerHTML = boot.interventions.length
      ? boot.interventions.slice(0, 10).map(item =>
        '<a href="#work/' + Number(item.id) + '"><strong>' + escape(item.title) +
        '</strong><small>#' + Number(item.id) + ' · ' + escape(item.client || 'Intervento IT') + '</small></a>'
      ).join('')
      : '<span class="muted">Nessun intervento</span>';
  }

  function renderHome() {
    const rows = boot.interventions.map(item =>
      '<a class="work-row" href="#work/' + Number(item.id) + '">' +
      '<div><div class="work-title">' + escape(item.title) + '</div><div class="work-sub">#' +
      Number(item.id) + ' · ' + escape(item.client || 'Nessun cliente') + ' · ' + date(item.created_at) +
      '</div></div><span class="badge ' + (item.status === 'closed' ? 'closed' : '') + '">' +
      (item.status === 'closed' ? '✓ Concluso' : '● In corso') + '</span></a>'
    ).join('');

    root.innerHTML = header('IT Operations / Workspace', 'Il lavoro, finalmente documentato.',
      'Da una richiesta in linguaggio naturale a un piano operativo tracciabile.', '<span class="pill">● MVP 0.2</span>') +
      '<div class="stats">' +
      stat('Interventi registrati', boot.counts.total, 'Totale personale') +
      stat('Interventi aperti', boot.counts.open, 'Ancora in lavorazione') +
      stat('Interventi conclusi', boot.counts.closed, 'Report disponibili') +
      '</div>' +
      '<div class="section-row"><h2>Interventi recenti</h2><a class="btn primary" href="#new">✦ Nuovo intervento</a></div>' +
      (rows ? '<div class="work-list">' + rows + '</div>' :
        '<div class="empty"><div class="empty-mark">✦</div><h2>Inizia dal primo intervento</h2>' +
        '<p>Descrivi cosa devi fare: l’IA creerà una checklist da seguire e documentare.</p>' +
        '<a class="btn primary" href="#new">Crea intervento</a></div>') +
      '<p class="hint" style="margin-top:30px">Beta privata · Nessuna attività viene eseguita sui sistemi gestiti.</p>';
  }

  function stat(name, count, hint) {
    return '<div class="card"><div class="stat-label">' + name + '</div><div class="number">' +
      Number(count) + '</div><div class="stat-foot">' + hint + '</div></div>';
  }

  function renderNew() {
    root.innerHTML = '<a href="#home" class="back">← Torna alla panoramica</a>' +
      header('Nuovo intervento / AI Planning', 'Da richiesta a checklist.',
        'Racconta il lavoro in linguaggio naturale. SysPilot organizzerà le attività senza dichiararle già eseguite.',
        '<span class="pill">✦ AI assisted</span>') +
      (!boot.configured ? '<div class="flash error">API OpenAI non configurata. Imposta SYSPILOT_OPENAI_API_KEY in Coolify.</div>' : '') +
      '<div class="card form-card">' +
      '<form id="create-form"><div><label for="request">Cosa devi fare? *</label>' +
      '<textarea id="request" name="request" minlength="15" maxlength="2500" required placeholder="Es.: Devo installare un nuovo server Ubuntu, configurare Nginx e PHP, impostare SSL e verificare backup e firewall."></textarea>' +
      '<div class="hint">Descrivi l’obiettivo, non serve elencare tutti i passaggi. Non inserire password o informazioni riservate.</div></div>' +
      '<div class="fields"><div><label for="client">Cliente (facoltativo)</label>' +
      '<input id="client" name="client" maxlength="120" placeholder="Es. Cliente Demo"></div>' +
      '<div><label for="asset">Dispositivo / asset (facoltativo)</label>' +
      '<input id="asset" name="asset" maxlength="120" placeholder="Es. SRV-APP-01"></div></div>' +
      '<div class="tip">✦ La checklist viene proposta dall’IA. Il tecnico deve sempre verificare i passaggi, autorizzazioni e procedure prima di applicarli.</div>' +
      '<div class="actions"><a class="btn ghost" href="#home">Annulla</a>' +
      '<button class="btn primary" type="submit" ' + (!boot.configured ? 'disabled' : '') + '>✦ Genera checklist con IA</button></div></form></div>';
  }

  async function renderWork(id) {
    root.innerHTML = '<div class="muted">Caricamento intervento…</div>';
    const data = await api('/interventions/' + id);
    const record = data.intervention;
    const closed = record.status === 'closed';
    const done = data.steps.filter(s => s.state === 'done').length;
    const total = data.steps.length;
    const remaining = data.steps.filter(s => s.state === 'todo' || s.state === 'blocked').length;
    const percentage = total ? Math.round(done / total * 100) : 0;
    const report = '<div class="card" style="margin-top:25px">' +
      '<div class="section-row"><h2>Riepilogo intervento</h2><span class="badge">Report</span></div>' +
      '<div class="report-grid"><div>Cliente: <strong>' + escape(record.client || '—') + '</strong></div>' +
      '<div>Asset: <strong>' + escape(record.asset || '—') + '</strong></div>' +
      '<div>Creato: <strong>' + date(record.created_at) + '</strong></div>' +
      '<div>Stato: <strong>' + (closed ? 'Concluso' : 'In corso') + '</strong></div></div>' +
      '<p class="muted" style="white-space:pre-wrap;margin-top:16px">' + escape(record.request) + '</p></div>';
    let lastPhase = '';
    const steps = data.steps.map((step, index) => {
      const label = step.phase !== lastPhase ? '<div class="phase">' + escape(step.phase) + '</div>' : '';
      lastPhase = step.phase;
      return label + '<section class="step"><div class="step-heading"><div><h3>' + (index + 1) +
        '. ' + escape(step.title) + '</h3><p>' + escape(step.detail || '') + '</p></div>' +
        '<span class="badge ' + (step.state === 'done' ? 'closed' : '') + '">' + stateLabel(step.state) + '</span></div>' +
        (closed ? '<p class="hint" style="margin-top:12px">Note: ' + escape(step.note || 'Nessuna nota') + '</p>' :
          '<form class="step-form step-fields" data-step="' + Number(step.id) + '">' +
          '<div><label>Stato</label><select name="state">' +
          ['todo', 'done', 'blocked', 'skipped'].map(value =>
            '<option value="' + value + '"' + (value === step.state ? ' selected' : '') + '>' +
            stateLabel(value) + '</option>').join('') + '</select></div>' +
          '<div><label>Esito / note</label><textarea name="note" rows="2" maxlength="3000" placeholder="Annota il risultato del passaggio…">' +
          escape(step.note || '') + '</textarea></div>' +
          '<button type="submit" class="btn">Salva passo</button></form>') +
        '</section>';
    }).join('');
    const timeline = data.events.map(event =>
      '<li><span>' + escape(event.message) + '</span><small>' + date(event.created_at) + '</small></li>'
    ).join('');

    root.innerHTML = '<a class="back no-print" href="#home">← Torna alla panoramica</a>' +
      header('Intervento #' + record.id, record.title,
        closed ? 'Intervento terminato · report pronto per la stampa' : 'Checklist operativa · gli esiti sono registrati solo dal tecnico',
        '<span class="pill ' + (closed ? 'closed' : '') + '">' + (closed ? '✓ Concluso' : '● In corso') + '</span>') +
      report +
      '<div class="section-row"><h2>Checklist operativa</h2><span class="badge">' + done + ' / ' + total + ' completati</span></div>' +
      '<div class="no-print"><div class="progress-label"><span>Avanzamento</span><span>' +
      percentage + '%</span></div><div class="progress-track"><div class="progress-fill" style="width:' +
      percentage + '%"></div></div></div>' +
      '<div style="margin-top:23px">' + steps + '</div>' +
      '<div class="section-row no-print"><div class="hint">' + remaining +
      ' attività ancora da completare o saltare con motivazione.</div>' +
      (closed ? '<button class="btn primary" type="button" class="print-report">⎙ Stampa / Salva PDF</button>' :
        '<button class="btn primary" type="button" id="close-record" ' +
        (remaining ? 'disabled title="Completa i passaggi prima di chiudere"' : '') + '>✓ Chiudi intervento</button>') + '</div>' +
      '<hr><h2>Registro attività</h2><ul class="timeline">' + timeline + '</ul>' +
      '<div class="actions no-print"><a class="btn ghost" href="#home">Torna alla panoramica</a>' +
      '<button class="btn" class="print-report" type="button">⎙ Stampa / Salva PDF</button></div>';
  }

  function renderDenied(error) {
    const unauthorized = error.status === 401;
    root.innerHTML = '<div class="card" style="max-width:650px"><div class="eyebrow">SysPilot · Accesso protetto</div>' +
      '<h1>' + (unauthorized ? 'Accedi a PitMetric' : 'Accesso non autorizzato') + '</h1>' +
      '<p class="subtitle">' + (unauthorized
        ? 'Per utilizzare SysPilot devi accedere con un account PitMetric verificato.'
        : 'La beta è riservata agli account inclusi nella variabile SYSPILOT_ALLOWED_EMAILS di Coolify.') +
      '</p><div class="actions"><a class="btn primary" href="/login">Accedi a PitMetric ↗</a>' +
      '<button class="btn ghost" id="retry" type="button">Riprova</button></div></div>';
  }

  async function navigate() {
    if (!boot) return;
    clearFlash();
    const hash = decodeURIComponent(location.hash.replace(/^#/, '')) || 'home';
    document.querySelectorAll('[data-nav]').forEach(link =>
      link.classList.toggle('active', link.dataset.nav === (hash === 'new' ? 'new' : 'home')));
    try {
      if (hash === 'new') renderNew();
      else if (/^work\/[1-9][0-9]*$/.test(hash)) await renderWork(Number(hash.slice(5)));
      else renderHome();
    } catch (e) {
      root.innerHTML = '<div class="card"><h2>Impossibile aprire l’intervento</h2><p class="muted">' +
        escape(e.message) + '</p><a href="#home" class="btn">Torna alla panoramica</a></div>';
    }
  }

  document.addEventListener('submit', async event => {
    const form = event.target;
    if (form.id !== 'create-form' && !form.classList.contains('step-form')) return;
    event.preventDefault();
    if (busy) return;
    busy = true;
    const submit = form.querySelector('button[type=submit]');
    if (submit) submit.disabled = true;
    try {
      if (form.id === 'create-form') {
        const fd = new FormData(form);
        flash('Generazione in corso… attendi qualche secondo.');
        const result = await api('/interventions', 'POST', {
          request: fd.get('request'),
          client: fd.get('client'),
          asset: fd.get('asset')
        });
        await refresh();
        location.hash = 'work/' + Number(result.id);
        await navigate();
        flash('Checklist generata. Verifica i passaggi prima di iniziare.');
      } else {
        const fd = new FormData(form);
        const id = Number(location.hash.replace('#work/', ''));
        await api('/interventions/' + id + '/steps/' + Number(form.dataset.step), 'PATCH', {
          state: fd.get('state'), note: fd.get('note')
        });
        await refresh();
        await renderWork(id);
        flash('Passaggio aggiornato e registrato.');
      }
    } catch (e) {
      flash(e.message, true);
    } finally {
      busy = false;
      if (submit && submit.isConnected) submit.disabled = false;
    }
  });

  document.addEventListener('click', async event => {
    if (event.target.closest('.print-report')) {
      window.print();
      return;
    }
    if (event.target.closest('#retry')) {
      location.reload();
      return;
    }
    const button = event.target.closest('#close-record');
    if (!button || busy) return;
    if (!confirm('Chiudere l’intervento? I passaggi non saranno più modificabili.')) return;
    busy = true;
    button.disabled = true;
    try {
      const id = Number(location.hash.replace('#work/', ''));
      await api('/interventions/' + id + '/close', 'POST', {});
      await refresh();
      await renderWork(id);
      flash('Intervento chiuso. Puoi salvarne il report in PDF.');
    } catch (e) {
      flash(e.message, true);
      if (button.isConnected) button.disabled = false;
    } finally {
      busy = false;
    }
  });

  window.addEventListener('hashchange', navigate);
  (async () => {
    try {
      await refresh();
      await navigate();
    } catch (e) {
      renderDenied(e);
    }
  })();
})();
