function initializeInPista() {
    const root = document.querySelector('[data-in-pista]');

    if (!root || root.dataset.inPistaInitialized) return;
    root.dataset.inPistaInitialized = 'true';

    const toast = root.querySelector('[data-in-pista-toast]');
    let toastTimer;

    const showMessage = (message, isError = false) => {
        if (!toast) return;
        toast.textContent = message;
        toast.style.borderColor = isError ? '#ba5b68' : '#428f78';
        toast.style.background = isError ? '#4f2630' : '#17392e';
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 4400);
    };

    const picker = root.querySelector('[data-in-pista-kart-select]');
    picker?.querySelector('select')?.addEventListener('change', () => {
        if (picker.querySelector('select').value) picker.requestSubmit();
    });

    for (const opener of root.querySelectorAll('[data-in-pista-dialog-open]')) {
        opener.addEventListener('click', () => {
            if (opener.disabled) return;
            const dialog = document.getElementById(opener.dataset.inPistaDialogOpen);
            if (dialog instanceof HTMLDialogElement) dialog.showModal();
        });
    }

    for (const dialog of root.querySelectorAll('dialog.in-pista-dialog')) {
        dialog.querySelector('[data-in-pista-dialog-close]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => {
            if (event.target === dialog) dialog.close();
        });
    }

    const applyLast = root.querySelector('[data-in-pista-apply-last]');
    applyLast?.addEventListener('click', () => {
        const form = applyLast.closest('form');
        if (!form) return;

        const suggestions = [...form.querySelectorAll('[data-in-pista-recommended-field]')]
            .map(source => [form.elements.namedItem(source.dataset.inPistaRecommendedField), source.value])
            .filter(([field]) => field instanceof HTMLInputElement);
        if (!suggestions.length) return;

        const hasChanges = suggestions.some(([field, value]) => field.value !== '' && field.value !== value);
        if (hasChanges && !window.confirm('Sostituire i valori già inseriti con quelli dell’ultima sessione conclusa?')) return;

        for (const [field, value] of suggestions) field.value = value;
        showMessage('Ultimi valori inseriti. Controllali e premi Registra regolazioni per salvarli.');
    });

    root.addEventListener('click', event => {
        const button = event.target instanceof Element ? event.target.closest('[data-in-pista-edit-lap]') : null;
        if (!button) return;

        const dialog = root.querySelector('#in-pista-lap-correction');
        const form = dialog?.querySelector('form');
        if (!(dialog instanceof HTMLDialogElement) || !form) return;

        form.action = button.dataset.inPistaLapAction;
        form.elements.namedItem('lap_time').value = button.dataset.inPistaLapLabel;
        form.elements.namedItem('expected_lap_time_ms').value = button.dataset.inPistaLapMs;
        form.elements.namedItem('reason').value = '';
        form.querySelector('[data-in-pista-form-error]')?.remove();
        dialog.querySelector('[data-in-pista-edit-lap-title]').textContent = `#${button.dataset.inPistaLapNumber}`;
        dialog.showModal();
    });

    root.querySelector('[data-in-pista-finish]')?.addEventListener('submit', event => {
        if (!window.confirm('Concludere la sessione? I dati saranno consolidati e lo storico resterà disponibile.')) event.preventDefault();
    });

    const addActivity = entry => {
        const list = root.querySelector('[data-in-pista-activity]');
        if (!list || !entry || !entry.kind) return;

        list.querySelector('[data-in-pista-empty-activity]')?.remove();
        const article = document.createElement('article');
        const time = document.createElement('span');
        const detail = document.createElement('div');
        const title = document.createElement('strong');
        const summary = document.createElement('p');
        time.textContent = entry.time || '';
        title.textContent = entry.kind;
        summary.textContent = entry.summary || '';
        detail.append(title, summary);
        article.append(time, detail);
        list.prepend(article);
    };

    const addLapRow = lap => {
        const list = root.querySelector('[data-in-pista-laps]');
        if (!list || !lap) return;
        list.querySelector('[data-in-pista-empty-laps]')?.remove();

        const row = document.createElement('article');
        row.className = 'in-pista-lap-row';
        row.dataset.inPistaLapRow = String(lap.id);
        const main = document.createElement('div');
        main.className = 'in-pista-lap-row-main';
        const number = document.createElement('span');
        number.className = 'in-pista-lap-number';
        number.textContent = `#${lap.number}`;
        const timeWrap = document.createElement('div');
        timeWrap.className = 'in-pista-lap-time-wrap';
        const time = document.createElement('strong');
        time.dataset.inPistaLapTime = '';
        time.textContent = lap.time;
        const source = document.createElement('small');
        source.textContent = 'Manuale';
        timeWrap.append(time, source);
        const edited = document.createElement('span');
        edited.className = 'in-pista-lap-edited';
        edited.dataset.inPistaLapEdited = '';
        edited.textContent = 'Corretto';
        edited.hidden = true;
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.inPistaEditLap = '';
        button.dataset.inPistaLapAction = lap.edit_url;
        button.dataset.inPistaLapNumber = String(lap.number);
        button.dataset.inPistaLapMs = String(lap.milliseconds);
        button.dataset.inPistaLapLabel = lap.time;
        button.textContent = 'Correggi';
        main.append(number, timeWrap, edited, button);
        row.append(main);
        list.prepend(row);

        while (list.querySelectorAll('[data-in-pista-lap-row]').length > 20) {
            list.querySelector('[data-in-pista-lap-row]:last-of-type')?.remove();
        }
    };

    const updateBestLap = lap => {
        const time = root.querySelector('[data-in-pista-best-lap]');
        const detail = root.querySelector('[data-in-pista-best-lap-detail]');
        if (time) time.textContent = lap?.time ?? '—';
        if (detail) detail.textContent = lap
            ? `Giro #${lap.number} · ${lap.source === 'manual' ? 'Manuale' : 'Importato'}`
            : 'Nessun tempo valido registrato';

        for (const row of root.querySelectorAll('[data-in-pista-lap-row]')) {
            row.classList.toggle('is-best', String(lap?.id ?? '') === row.dataset.inPistaLapRow);
        }
    };

    const applyLapCorrection = correction => {
        const row = root.querySelector(`[data-in-pista-lap-row="${correction.lap_id}"]`);
        if (!row) return;

        row.querySelector('[data-in-pista-lap-time]').textContent = correction.corrected_time;
        row.querySelector('[data-in-pista-lap-edited]').hidden = false;
        const button = row.querySelector('[data-in-pista-edit-lap]');
        button.dataset.inPistaLapMs = String(correction.lap_time_ms);
        button.dataset.inPistaLapLabel = correction.corrected_time;

        let history = row.querySelector('[data-in-pista-lap-history]');
        if (!history) {
            history = document.createElement('details');
            history.className = 'in-pista-lap-history';
            history.dataset.inPistaLapHistory = '';
            history.append(document.createElement('summary'), document.createElement('ul'));
            row.append(history);
        }

        const item = document.createElement('li');
        const times = document.createElement('strong');
        times.textContent = `${correction.previous_time} a ${correction.corrected_time}`;
        const operator = document.createElement('span');
        operator.textContent = `${correction.operator} · ${correction.corrected_at}`;
        item.append(times, operator);
        if (correction.reason) {
            const reason = document.createElement('p');
            reason.textContent = correction.reason;
            item.append(reason);
        }
        history.querySelector('ul').prepend(item);
        history.querySelector('summary').textContent = `Storico correzioni (${history.querySelectorAll('li').length})`;
    };

    const updateSummary = (form, response) => {
        const entry = response.entry;
        if (!entry) return;

        if (response.correction) {
            applyLapCorrection(response.correction);
            if ('best_lap' in response) updateBestLap(response.best_lap);
            form.reset();
            form.closest('dialog')?.close();
            addActivity(entry);
            return;
        }

        const lapInput = form.querySelector('input[name="lap_time"]');
        if (lapInput) {
            const count = root.querySelector('[data-in-pista-lap-count]');
            if (count) count.textContent = String(Number(count.textContent || 0) + 1);
            addLapRow(response.lap);
            if ('best_lap' in response) updateBestLap(response.best_lap);
            lapInput.value = '';
            lapInput.focus({ preventScroll: true });
        }

        const phase = form.querySelector('input[name="phase"]')?.value;
        if (phase) {
            const reading = root.querySelector(`[data-in-pista-reading-${phase}]`);
            if (reading) reading.textContent = entry.summary;
        }

        if (form.querySelector('input[name="camber_front_deg"]')) {
            const fields = new FormData(form);
            for (const [key, value] of fields.entries()) {
                if (!value || key === '_token' || key === 'notes') continue;
                for (const field of root.querySelectorAll(`[data-in-pista-setup-field="${key}"]`)) {
                    field.textContent = `${value}${key.endsWith('_deg') ? ' °' : key.endsWith('_mm') ? ' mm' : ''}`;
                }
            }
            const pinion = fields.get('pinion_teeth');
            const crown = fields.get('crown_teeth');
            if (pinion && crown) {
                for (const ratio of root.querySelectorAll('[data-in-pista-setup-field="final_drive"]')) ratio.textContent = `${pinion}/${crown}`;
            }
            const adjustmentCount = root.querySelector('[data-in-pista-adjustment-count]');
            if (adjustmentCount) {
                const next = Number(adjustmentCount.dataset.count || 0) + 1;
                adjustmentCount.dataset.count = String(next);
                adjustmentCount.textContent = `${next} ${next === 1 ? 'regolazione registrata' : 'regolazioni registrate'}`;
                const adjustmentStat = root.querySelector('[data-in-pista-adjustment-stat]');
                if (adjustmentStat) adjustmentStat.textContent = String(next);
            }
            form.reset();
        }

        if (form.querySelector('[name="kind"]')) form.reset();

        if (response.weather) {
            const weather = response.weather;
            const parts = {
                '[data-in-pista-weather-temp]': `${Math.round(weather.air_temperature_c)}°`,
                '[data-in-pista-weather-condition]': 'Previsioni aggiornate ora',
                '[data-in-pista-weather-wind]': weather.wind_speed_kmh == null ? '—' : `${weather.wind_speed_kmh} km/h`,
                '[data-in-pista-weather-humidity]': weather.humidity_pct == null ? '—' : `${weather.humidity_pct}%`,
                '[data-in-pista-weather-rain]': weather.precipitation_next_hour_mm == null ? '—' : `${weather.precipitation_next_hour_mm} mm`,
            };
            for (const [selector, text] of Object.entries(parts)) {
                const element = root.querySelector(selector);
                if (element) element.textContent = text;
            }
            const weatherStatus = form.querySelector('[data-pm-weather-status]');
            if (weatherStatus) weatherStatus.textContent = 'Aggiornato e associato alla sessione';
        }

        form.closest('dialog')?.close();
        addActivity(entry);
    };

    for (const form of root.querySelectorAll('[data-in-pista-form]')) {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const submit = form.querySelector('button[type="submit"]');
            const oldError = form.querySelector('[data-in-pista-form-error]');
            oldError?.remove();
            if (submit) submit.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) throw new Error('Sessione di accesso scaduta. Ricarica la pagina ed effettua di nuovo il login.');
                const data = await response.json();

                if (!response.ok) {
                    const firstError = data.errors && Object.values(data.errors).flat()[0];
                    throw new Error(firstError || data.message || 'Non è stato possibile salvare.');
                }

                updateSummary(form, data);
                showMessage(data.message || 'Salvato nella sessione.');
            } catch (error) {
                const message = error instanceof Error ? error.message : 'Connessione non disponibile. I campi compilati sono rimasti invariati.';
                const inlineError = document.createElement('p');
                inlineError.className = 'in-pista-form-error';
                inlineError.dataset.inPistaFormError = 'true';
                inlineError.setAttribute('role', 'alert');
                inlineError.textContent = message;
                form.prepend(inlineError);
                showMessage(message, true);
            } finally {
                if (submit) submit.disabled = false;
                const gpsButton = form.querySelector('[data-pm-weather-gps]');
                if (gpsButton) gpsButton.disabled = false;
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', initializeInPista);
document.addEventListener('livewire:navigated', initializeInPista);
if (document.readyState !== 'loading') initializeInPista();
