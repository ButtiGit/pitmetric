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

    const updateSummary = (form, response) => {
        const entry = response.entry;
        if (!entry) return;

        const lapInput = form.querySelector('input[name="lap_time"]');
        if (lapInput) {
            const count = root.querySelector('[data-in-pista-lap-count]');
            if (count) count.textContent = String(Number(count.textContent || 0) + 1);

            const best = root.querySelector('[data-in-pista-best-lap]');
            const toMilliseconds = value => {
                const pieces = value.split(':');
                const seconds = Number(pieces.pop());
                const minutes = pieces.length ? Number(pieces.pop()) : 0;
                return (Number.isFinite(seconds) && Number.isFinite(minutes)) ? Math.round((minutes * 60 + seconds) * 1000) : Infinity;
            };
            if (best && toMilliseconds(entry.summary) < toMilliseconds(best.textContent.trim())) best.textContent = entry.summary;
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
                const field = root.querySelector(`[data-in-pista-setup-field="${key}"]`);
                if (field) field.textContent = `${value}${key.endsWith('_deg') ? ' °' : key.endsWith('_mm') ? ' mm' : ''}`;
            }
            const pinion = fields.get('pinion_teeth');
            const crown = fields.get('crown_teeth');
            const ratio = root.querySelector('[data-in-pista-setup-field="final_drive"]');
            if (ratio && pinion && crown) ratio.textContent = `${pinion}/${crown}`;
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
