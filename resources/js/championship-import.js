function initializeChampionshipImports() {
    for (const root of document.querySelectorAll('[data-pm-championship-import]')) {
        if (root.dataset.bound) continue;
        root.dataset.bound = 'true';
        const config = JSON.parse(root.querySelector('[data-import-config]').textContent);
        const uploadForm = root.querySelector('[data-import-upload]');
        const mappingForm = root.querySelector('[data-import-map]');
        const error = root.querySelector('[data-import-error]');
        const confirm = root.querySelector('[data-import-confirm]');
        let draft = null;
        let kind = null;
        let busy = false;
        let previewIsValid = false;

        const element = (tag, text = '', classes = '') => {
            const node = document.createElement(tag);
            node.textContent = text;
            node.className = classes;
            return node;
        };
        const step = (number) => {
            error.hidden = true;
            for (const panel of root.querySelectorAll('[data-import-step]')) panel.hidden = Number(panel.dataset.importStep) !== number;
            for (const item of root.querySelectorAll('[data-import-progress]')) {
                if (Number(item.dataset.importProgress) === number) item.setAttribute('aria-current', 'step');
                else item.removeAttribute('aria-current');
                item.classList.toggle('text-pm-accent', Number(item.dataset.importProgress) === number);
            }
            root.querySelector(`[data-import-step="${number}"] h3`)?.focus();
        };
        const request = async (url, body) => {
            const isForm = body instanceof FormData;
            const response = await fetch(url, {
                method: 'POST', credentials: 'same-origin',
                headers: {
                    Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf,
                    ...(isForm ? {} : { 'Content-Type': 'application/json' }),
                },
                body: isForm ? body : JSON.stringify(body),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.errors ? Object.values(payload.errors).flat().join('\n') : (payload.message || config.labels.network_error));
            return payload;
        };
        const run = async (button, callback) => {
            if (busy) return;
            busy = true;
            error.hidden = true;
            const text = button.textContent;
            button.textContent = config.labels.busy;
            button.disabled = true;
            root.setAttribute('aria-busy', 'true');
            try {
                await callback();
            } catch (exception) {
                error.textContent = exception.message || config.labels.network_error;
                error.hidden = false;
                error.focus();
            } finally {
                busy = false;
                button.textContent = text;
                button.disabled = button === confirm ? !previewIsValid : false;
                root.removeAttribute('aria-busy');
            }
        };
        const normalize = (value) => value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]/g, '');
        const aliases = {
            name: ['name', 'nome', 'nomesurname', 'nomecognome', 'nomepilota', 'fullname', 'driver', 'pilot', 'pilota', 'tappa', 'roundname'],
            number: ['number', 'numero', 'race', 'racenumber', 'racingnumber', 'numerogara', 'roundnumber', 'numerotappa'],
            licence: ['licence', 'license', 'licenza', 'licencereference'], team_name: ['team', 'teamname', 'scuderia'],
            starts_on: ['date', 'data', 'startdate', 'startson', 'datainiziale', 'datainizio'],
            ends_on: ['enddate', 'endson', 'datafinale', 'datafine'], venue: ['venue', 'circuit', 'circuito', 'localita', 'pista'],
            round_number: ['round', 'roundnumber', 'numerotappa', 'tappa'], position: ['position', 'posizione', 'pos', 'classifica'],
            status: ['status', 'stato'], pole: ['pole', 'poleposition'], fastest_lap: ['fastestlap', 'giroveloce'],
            adjustment: ['adjustment', 'rettifica', 'rettificapunti', 'penalty'], points: ['points', 'punti', 'score', 'punteggio'],
        };
        const fields = () => {
            if (!draft) return;
            previewIsValid = false;
            const sheet = draft.sheets[Number(mappingForm.elements.sheet.value)];
            const headerRow = Math.max(1, Number(mappingForm.elements.header_row.value) || 1);
            const headers = sheet.rows[headerRow - 1] || [];
            const used = new Set();
            const controls = root.querySelector('[data-import-fields]');
            controls.replaceChildren();
            for (const field of config.fields[kind]) {
                const label = element('label', '', 'grid min-w-0 gap-2');
                label.append(element('span', config.fieldLabels[field], 'pm-label'));
                const select = element('select', '', 'pm-input w-full min-w-0');
                select.name = `mapping[${field}]`;
                select.dataset.field = field;
                select.append(new Option(config.labels.ignore, ''));
                headers.forEach((header, index) => select.append(new Option(`${index + 1}. ${header || '—'}`, String(index))));
                const guessed = headers.findIndex((header, index) => !used.has(index) && (aliases[field] || []).includes(normalize(String(header))));
                if (guessed >= 0) {
                    used.add(guessed);
                    select.value = String(guessed);
                }
                select.required = ['participants:name', 'rounds:number', 'rounds:name', 'results:round_number', 'scoring:position', 'scoring:points'].includes(`${kind}:${field}`);
                if (select.required) label.firstElementChild.textContent += ' *';
                label.append(select);
                controls.append(label);
            }
            const sample = root.querySelector('[data-import-sample]');
            sample.replaceChildren();
            for (const cells of sheet.rows.slice(headerRow, headerRow + 3)) {
                const row = element('dl', '', 'rounded-lg border border-pm-border bg-pm-subtle p-3 text-xs');
                cells.forEach((value, index) => {
                    const pair = element('div', '', 'grid gap-1 py-1 sm:grid-cols-2');
                    pair.append(element('dt', String(headers[index] || index + 1), 'font-bold text-pm-muted'), element('dd', String(value), 'break-words text-pm-text-secondary'));
                    row.append(pair);
                });
                sample.append(row);
            }
        };
        uploadForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const selectedKind = uploadForm.elements.kind.value;
            run(uploadForm.querySelector('[type="submit"]'), async () => {
                draft = await request(config.urls.upload, new FormData(uploadForm));
                kind = selectedKind;
                uploadForm.elements.kind.value = kind;
                mappingForm.elements.sheet.replaceChildren(...draft.sheets.map((sheet, index) => new Option(sheet.name, String(index))));
                mappingForm.elements.header_row.value = '1';
                fields();
                step(1);
            });
        });
        mappingForm.elements.sheet.addEventListener('change', fields);
        mappingForm.elements.header_row.addEventListener('change', fields);
        mappingForm.addEventListener('change', () => { previewIsValid = false; });
        mappingForm.addEventListener('submit', (event) => {
            event.preventDefault();
            run(mappingForm.querySelector('[type="submit"]'), async () => {
                const mapping = {};
                for (const control of mappingForm.querySelectorAll('[data-field]')) mapping[control.dataset.field] = control.value === '' ? null : Number(control.value);
                const plan = await request(config.urls.preview, { token: draft.token, sheet: Number(mappingForm.elements.sheet.value), header_row: Number(mappingForm.elements.header_row.value), mapping });
                root.querySelector('[data-import-summary]').textContent = `${config.labels.summary}: ${plan.new} / ${plan.skipped} / ${plan.errors}`;
                root.querySelector('[data-import-score-warning]').hidden = kind !== 'scoring';
                const list = root.querySelector('[data-import-rows]');
                list.replaceChildren();
                for (const item of plan.rows) {
                    const row = element('details', '', 'rounded-lg border border-pm-border bg-pm-subtle p-3 text-xs');
                    row.dataset.importRowState = item.state;
                    const color = item.state === 'error' ? 'text-pm-danger' : item.state === 'skip' ? 'text-pm-muted' : 'text-pm-success';
                    row.append(element('summary', `${config.labels.line} ${item.line} / ${item.label} / ${config.labels[item.state]}`, `cursor-pointer break-words font-bold ${color}`));
                    const values = element('dl', '', 'mt-2 space-y-1');
                    for (const field of config.fields[kind]) {
                        if (item.data[field] == null || item.data[field] === '') continue;
                        const pair = element('div', '', 'grid gap-1 sm:grid-cols-2');
                        pair.append(element('dt', config.fieldLabels[field], 'text-pm-muted'), element('dd', String(item.data[field]), 'break-words text-pm-text-secondary'));
                        values.append(pair);
                    }
                    row.append(values);
                    if (item.errors.length) {
                        row.open = true;
                        row.append(element('p', item.errors.join('\n'), 'mt-2 whitespace-pre-line break-words text-pm-danger'));
                    }
                    list.append(row);
                }
                previewIsValid = plan.errors === 0 && plan.new > 0;
                confirm.disabled = !previewIsValid;
                step(2);
            });
        });
        for (const button of root.querySelectorAll('[data-import-go]')) button.addEventListener('click', () => { if (!busy) step(Number(button.dataset.importGo)); });
        confirm.addEventListener('click', () => {
            if (!previewIsValid) return;
            run(confirm, async () => {
                await request(config.urls.confirm, { token: draft.token });
                window.location.reload();
            });
        });
        step(0);
    }
}

document.addEventListener('DOMContentLoaded', initializeChampionshipImports);
document.addEventListener('livewire:navigated', initializeChampionshipImports);
