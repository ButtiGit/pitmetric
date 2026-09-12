const appSelector = '.pitmetric-app';
const enhancedSelector = '[data-pm-enhanced="true"]';
const locale = document.documentElement.lang?.toLowerCase().startsWith('it') ? 'it-IT' : 'en-GB';
const isItalian = locale === 'it-IT';

const copy = {
    choose: isItalian ? 'Seleziona' : 'Select',
    selected: isItalian ? 'selezionati' : 'selected',
    today: isItalian ? 'Oggi' : 'Today',
    clear: isItalian ? 'Cancella' : 'Clear',
    apply: isItalian ? 'Conferma' : 'Apply',
    previous: isItalian ? 'Precedente' : 'Previous',
    next: isItalian ? 'Successivo' : 'Next',
    hours: isItalian ? 'Ore' : 'Hours',
    minutes: isItalian ? 'Minuti' : 'Minutes',
    week: isItalian ? 'Settimana' : 'Week',
    chooseDate: isItalian ? 'gg/mm/aaaa' : 'dd/mm/yyyy',
    chooseTime: isItalian ? 'Seleziona ora' : 'Select time',
    chooseMonth: isItalian ? 'Seleziona mese' : 'Select month',
    chooseWeek: isItalian ? 'Seleziona settimana' : 'Select week',
    chooseDateTime: isItalian ? 'Seleziona data e ora' : 'Select date and time',
    noOptions: isItalian ? 'Nessuna opzione' : 'No options',
};

let activeWidget = null;

function element(tag, className = '', text = '') {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== '') node.textContent = text;
    return node;
}

function iconButton(label, path) {
    const button = element('button', 'pm-picker-icon-button');
    button.type = 'button';
    button.setAttribute('aria-label', label);
    button.innerHTML = `<svg viewBox="0 0 20 20" aria-hidden="true"><path d="${path}" /></svg>`;
    return button;
}

function dispatchValue(source) {
    source.dispatchEvent(new Event('input', { bubbles: true }));
    source.dispatchEvent(new Event('change', { bubbles: true }));
}

function closeActive(except = null) {
    if (activeWidget && activeWidget !== except) activeWidget.close();
}

function setupPopup(wrapper, trigger, panel, beforeOpen = null) {
    const widget = {
        wrapper,
        trigger,
        panel,
        open() {
            closeActive(widget);
            beforeOpen?.();
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            wrapper.classList.add('is-open');
            activeWidget = widget;
        },
        close() {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            wrapper.classList.remove('is-open');
            if (activeWidget === widget) activeWidget = null;
        },
        toggle() {
            if (panel.hidden) widget.open();
            else widget.close();
        },
    };

    trigger.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        widget.toggle();
    });

    trigger.addEventListener('keydown', event => {
        if (['ArrowDown', 'ArrowUp'].includes(event.key) && panel.hidden) {
            event.preventDefault();
            widget.open();
            requestAnimationFrame(() => panel.querySelector('button:not(:disabled)')?.focus());
        }
    });

    panel.addEventListener('click', event => event.stopPropagation());
    return widget;
}

document.addEventListener('pointerdown', event => {
    if (activeWidget && !activeWidget.wrapper.contains(event.target)) activeWidget.close();
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && activeWidget) {
        const trigger = activeWidget.trigger;
        activeWidget.close();
        trigger.focus();
    }
});

function hideNativeSource(source, trigger) {
    source.dataset.pmEnhanced = 'true';
    source.classList.add('pm-native-control-source');
    source.tabIndex = -1;
    trigger.disabled = source.disabled;

    source.addEventListener('invalid', event => {
        event.preventDefault();
        trigger.classList.add('is-invalid');
        trigger.focus();
    });

    source.addEventListener('change', () => trigger.classList.remove('is-invalid'));
}

function createShell(source, kind) {
    const wrapper = element('div', `pm-custom-control pm-custom-${kind}`);
    source.parentNode.insertBefore(wrapper, source);
    wrapper.appendChild(source);
    return wrapper;
}

function chevron() {
    const span = element('span', 'pm-control-chevron');
    span.innerHTML = '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="m6.5 8 3.5 3.5L13.5 8" /></svg>';
    return span;
}

function calendarIcon() {
    const span = element('span', 'pm-control-leading-icon');
    span.innerHTML = '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5.5 3.5v2M14.5 3.5v2M3.5 7.5h13M4.5 5h11a1 1 0 0 1 1 1v9.5a1 1 0 0 1-1 1h-11a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" /></svg>';
    return span;
}

function clockIcon() {
    const span = element('span', 'pm-control-leading-icon');
    span.innerHTML = '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="6.5" /><path d="M10 6.5V10l2.5 1.5" /></svg>';
    return span;
}

function createTrigger(placeholder, icon = null) {
    const trigger = element('button', 'pm-control-trigger');
    trigger.type = 'button';
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-expanded', 'false');
    if (icon) trigger.appendChild(icon);

    const value = element('span', 'pm-control-value');
    value.dataset.pmControlValue = '';
    value.textContent = placeholder;
    trigger.appendChild(value);
    trigger.appendChild(chevron());
    return { trigger, value };
}

function enhanceSelect(source) {
    if (source.matches(enhancedSelector)) return;

    const wrapper = createShell(source, source.multiple ? 'multiselect' : 'select');
    const { trigger, value } = createTrigger(copy.choose);
    trigger.setAttribute('aria-haspopup', 'listbox');
    wrapper.appendChild(trigger);

    const panel = element('div', 'pm-control-popover pm-select-popover');
    panel.hidden = true;
    panel.setAttribute('role', 'listbox');
    if (source.multiple) panel.setAttribute('aria-multiselectable', 'true');
    wrapper.appendChild(panel);
    hideNativeSource(source, trigger);

    function selectedLabel() {
        const selected = Array.from(source.selectedOptions).filter(option => option.value !== '');
        if (!selected.length) {
            const placeholder = Array.from(source.options).find(option => option.value === '');
            value.textContent = placeholder?.textContent?.trim() || copy.choose;
            value.classList.add('is-placeholder');
            return;
        }

        value.classList.remove('is-placeholder');
        if (!source.multiple) {
            value.textContent = selected[0].textContent.trim();
            return;
        }

        value.textContent = selected.length <= 2
            ? selected.map(option => option.textContent.trim()).join(', ')
            : `${selected.length} ${copy.selected}`;
    }

    function appendOption(option, target) {
        if (option.hidden) return;
        const button = element('button', 'pm-select-option');
        button.type = 'button';
        button.disabled = option.disabled;
        button.dataset.value = option.value;
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', option.selected ? 'true' : 'false');

        if (source.multiple) {
            const marker = element('span', 'pm-select-check');
            marker.innerHTML = '<svg viewBox="0 0 12 10" aria-hidden="true"><path d="m1.5 5 2.8 2.8L10.5 1.7" /></svg>';
            button.appendChild(marker);
        }

        button.appendChild(element('span', 'pm-select-option-label', option.textContent.trim()));
        if (option.selected) button.classList.add('is-selected');

        button.addEventListener('click', () => {
            if (source.multiple) {
                option.selected = !option.selected;
                button.classList.toggle('is-selected', option.selected);
                button.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                dispatchValue(source);
                selectedLabel();
                return;
            }

            source.value = option.value;
            dispatchValue(source);
            selectedLabel();
            renderOptions();
            widget.close();
            trigger.focus();
        });
        target.appendChild(button);
    }

    function renderOptions() {
        panel.replaceChildren();
        Array.from(source.children).forEach(child => {
            if (child.tagName === 'OPTGROUP') {
                const group = element('div', 'pm-select-group');
                group.appendChild(element('div', 'pm-select-group-label', child.label));
                panel.appendChild(group);
                Array.from(child.children).forEach(option => appendOption(option, group));
            } else if (child.tagName === 'OPTION') {
                appendOption(child, panel);
            }
        });

        if (!panel.querySelector('.pm-select-option')) panel.appendChild(element('div', 'pm-select-empty', copy.noOptions));
    }

    const widget = setupPopup(wrapper, trigger, panel, renderOptions);
    source.addEventListener('change', () => {
        selectedLabel();
        if (!panel.hidden) renderOptions();
    });
    selectedLabel();
}

function parseDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
    if (!match) return null;
    return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12);
}

function dateIso(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function formatDate(value) {
    const date = parseDate(value);
    return date ? new Intl.DateTimeFormat(locale, { day: '2-digit', month: '2-digit', year: 'numeric' }).format(date) : '';
}

function dateAllowed(iso, source) {
    const min = (source.min || '').slice(0, 10);
    const max = (source.max || '').slice(0, 10);
    return (!min || iso >= min) && (!max || iso <= max);
}

function weekdayLabels() {
    const formatter = new Intl.DateTimeFormat(locale, { weekday: 'short' });
    const monday = new Date(2026, 0, 5);
    return Array.from({ length: 7 }, (_, index) => {
        const date = new Date(monday);
        date.setDate(monday.getDate() + index);
        return formatter.format(date).replace('.', '').slice(0, 2);
    });
}

function buildCalendar(panel, state) {
    panel.replaceChildren();
    const header = element('div', 'pm-calendar-header');
    const previous = iconButton(copy.previous, 'm12.5 5-5 5 5 5');
    const next = iconButton(copy.next, 'm7.5 5 5 5-5 5');
    const title = element('div', 'pm-calendar-title');
    header.append(previous, title, next);
    panel.appendChild(header);

    const weekdays = element('div', 'pm-calendar-weekdays');
    weekdayLabels().forEach(day => weekdays.appendChild(element('span', '', day)));
    panel.appendChild(weekdays);
    const grid = element('div', 'pm-calendar-grid');
    panel.appendChild(grid);

    const footer = element('div', 'pm-picker-footer');
    const clear = element('button', 'pm-picker-secondary', copy.clear);
    clear.type = 'button';
    const today = element('button', 'pm-picker-secondary', copy.today);
    today.type = 'button';
    footer.append(clear, today);
    panel.appendChild(footer);

    function render() {
        title.textContent = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(state.view);
        grid.replaceChildren();
        const year = state.view.getFullYear();
        const month = state.view.getMonth();
        const first = new Date(year, month, 1, 12);
        const offset = (first.getDay() + 6) % 7;
        const days = new Date(year, month + 1, 0, 12).getDate();

        for (let i = 0; i < offset; i++) grid.appendChild(element('span', 'pm-calendar-blank'));
        for (let day = 1; day <= days; day++) {
            const date = new Date(year, month, day, 12);
            const iso = dateIso(date);
            const button = element('button', 'pm-calendar-day', String(day));
            button.type = 'button';
            button.disabled = !dateAllowed(iso, state.source);
            if (iso === state.value) button.classList.add('is-selected');
            if (iso === dateIso(new Date())) button.classList.add('is-today');
            button.addEventListener('click', () => state.select(iso));
            grid.appendChild(button);
        }
    }

    previous.addEventListener('click', () => {
        state.view = new Date(state.view.getFullYear(), state.view.getMonth() - 1, 1, 12);
        render();
    });
    next.addEventListener('click', () => {
        state.view = new Date(state.view.getFullYear(), state.view.getMonth() + 1, 1, 12);
        render();
    });
    clear.addEventListener('click', () => state.clear());
    today.addEventListener('click', () => {
        const iso = dateIso(new Date());
        if (dateAllowed(iso, state.source)) state.select(iso);
    });
    render();
}

function enhanceDate(source) {
    if (source.matches(enhancedSelector)) return;
    const wrapper = createShell(source, 'date');
    const { trigger, value } = createTrigger(copy.chooseDate, calendarIcon());
    wrapper.appendChild(trigger);
    const panel = element('div', 'pm-control-popover pm-calendar-popover');
    panel.hidden = true;
    wrapper.appendChild(panel);
    hideNativeSource(source, trigger);

    function syncLabel() {
        const formatted = formatDate(source.value);
        value.textContent = formatted || copy.chooseDate;
        value.classList.toggle('is-placeholder', !formatted);
    }

    const widget = setupPopup(wrapper, trigger, panel, () => {
        const current = parseDate(source.value) || new Date();
        buildCalendar(panel, {
            source,
            value: source.value,
            view: new Date(current.getFullYear(), current.getMonth(), 1, 12),
            select(iso) {
                source.value = iso;
                dispatchValue(source);
                syncLabel();
                widget.close();
                trigger.focus();
            },
            clear() {
                source.value = '';
                dispatchValue(source);
                syncLabel();
                widget.close();
                trigger.focus();
            },
        });
    });
    source.addEventListener('change', syncLabel);
    syncLabel();
}

function parseTime(value) {
    const match = /^(\d{2}):(\d{2})/.exec(value || '');
    return match ? { hour: Number(match[1]), minute: Number(match[2]) } : null;
}

function formatTime(value) {
    const parsed = parseTime(value);
    return parsed ? `${String(parsed.hour).padStart(2, '0')}:${String(parsed.minute).padStart(2, '0')}` : '';
}

function buildTimePanel(panel, state, includeFooter = true) {
    panel.replaceChildren();
    const columns = element('div', 'pm-time-columns');
    const hourColumn = element('div', 'pm-time-column');
    const minuteColumn = element('div', 'pm-time-column');
    hourColumn.appendChild(element('div', 'pm-time-column-label', copy.hours));
    minuteColumn.appendChild(element('div', 'pm-time-column-label', copy.minutes));
    const hours = element('div', 'pm-time-scroll');
    const minutes = element('div', 'pm-time-scroll');

    for (let hour = 0; hour < 24; hour++) {
        const button = element('button', 'pm-time-option', String(hour).padStart(2, '0'));
        button.type = 'button';
        button.classList.toggle('is-selected', hour === state.hour);
        button.addEventListener('click', () => {
            state.hour = hour;
            buildTimePanel(panel, state, includeFooter);
        });
        hours.appendChild(button);
    }

    const stepSeconds = Number(state.source.step || 60);
    const minuteStep = Number.isFinite(stepSeconds) && stepSeconds >= 60 ? Math.max(1, Math.min(60, Math.round(stepSeconds / 60))) : 1;
    const minuteValues = [];
    for (let minute = 0; minute < 60; minute += minuteStep) minuteValues.push(minute);
    if (!minuteValues.includes(state.minute)) minuteValues.push(state.minute);
    minuteValues.sort((a, b) => a - b);

    minuteValues.forEach(minute => {
        const button = element('button', 'pm-time-option', String(minute).padStart(2, '0'));
        button.type = 'button';
        button.classList.toggle('is-selected', minute === state.minute);
        button.addEventListener('click', () => {
            state.minute = minute;
            buildTimePanel(panel, state, includeFooter);
        });
        minutes.appendChild(button);
    });

    hourColumn.appendChild(hours);
    minuteColumn.appendChild(minutes);
    columns.append(hourColumn, minuteColumn);
    panel.appendChild(columns);

    requestAnimationFrame(() => panel.querySelectorAll('.pm-time-option.is-selected').forEach(button => button.scrollIntoView({ block: 'nearest' })));

    if (includeFooter) {
        const footer = element('div', 'pm-picker-footer');
        const clear = element('button', 'pm-picker-secondary', copy.clear);
        clear.type = 'button';
        clear.addEventListener('click', () => state.clear());
        const apply = element('button', 'pm-picker-primary', copy.apply);
        apply.type = 'button';
        apply.addEventListener('click', () => state.apply());
        footer.append(clear, apply);
        panel.appendChild(footer);
    }
}

function enhanceTime(source) {
    if (source.matches(enhancedSelector)) return;
    const wrapper = createShell(source, 'time');
    const { trigger, value } = createTrigger(copy.chooseTime, clockIcon());
    wrapper.appendChild(trigger);
    const panel = element('div', 'pm-control-popover pm-time-popover');
    panel.hidden = true;
    wrapper.appendChild(panel);
    hideNativeSource(source, trigger);

    function syncLabel() {
        const formatted = formatTime(source.value);
        value.textContent = formatted || copy.chooseTime;
        value.classList.toggle('is-placeholder', !formatted);
    }

    const widget = setupPopup(wrapper, trigger, panel, () => {
        const parsed = parseTime(source.value) || { hour: new Date().getHours(), minute: 0 };
        const state = {
            source,
            hour: parsed.hour,
            minute: parsed.minute,
            apply() {
                source.value = `${String(state.hour).padStart(2, '0')}:${String(state.minute).padStart(2, '0')}`;
                dispatchValue(source);
                syncLabel();
                widget.close();
                trigger.focus();
            },
            clear() {
                source.value = '';
                dispatchValue(source);
                syncLabel();
                widget.close();
                trigger.focus();
            },
        };
        buildTimePanel(panel, state);
    });
    source.addEventListener('change', syncLabel);
    syncLabel();
}

function enhanceNumber(source) {
    if (source.matches(enhancedSelector)) return;
    source.dataset.pmEnhanced = 'true';
    const wrapper = element('div', 'pm-number-control');
    source.parentNode.insertBefore(wrapper, source);
    wrapper.appendChild(source);
    source.classList.add('pm-number-input');

    const minus = element('button', 'pm-number-stepper', '−');
    minus.type = 'button';
    minus.setAttribute('aria-label', isItalian ? 'Diminuisci' : 'Decrease');
    const plus = element('button', 'pm-number-stepper', '+');
    plus.type = 'button';
    plus.setAttribute('aria-label', isItalian ? 'Aumenta' : 'Increase');
    wrapper.insertBefore(minus, source);
    wrapper.appendChild(plus);

    function step(direction) {
        if (source.disabled || source.readOnly) return;
        try {
            if (direction < 0) source.stepDown();
            else source.stepUp();
        } catch {
            const stepValue = Number(source.step || 1) || 1;
            const current = Number(source.value || 0);
            source.value = String(current + direction * stepValue);
        }
        dispatchValue(source);
        source.focus();
    }
    minus.addEventListener('click', () => step(-1));
    plus.addEventListener('click', () => step(1));
}

function enhanceWithin(root = document) {
    const scopes = [];
    if (root === document) {
        document.querySelectorAll(appSelector).forEach(scope => scopes.push(scope));
    } else if (root instanceof Element) {
        if (root.matches(appSelector)) scopes.push(root);
        const ancestor = root.closest(appSelector);
        if (ancestor && !scopes.includes(ancestor)) scopes.push(ancestor);
        root.querySelectorAll?.(appSelector).forEach(scope => {
            if (!scopes.includes(scope)) scopes.push(scope);
        });
    }

    scopes.forEach(container => {
        container.querySelectorAll('select:not([data-pm-enhanced="true"]):not([data-pm-skip])').forEach(enhanceSelect);
        container.querySelectorAll('input[type="date"]:not([data-pm-enhanced="true"]):not([data-pm-skip])').forEach(enhanceDate);
        container.querySelectorAll('input[type="time"]:not([data-pm-enhanced="true"]):not([data-pm-skip])').forEach(enhanceTime);
        container.querySelectorAll('input[type="number"]:not([data-pm-enhanced="true"]):not([data-pm-skip])').forEach(enhanceNumber);
    });
}

enhanceWithin(document);

const observer = new MutationObserver(records => {
    records.forEach(record => record.addedNodes.forEach(node => {
        if (node instanceof Element) enhanceWithin(node);
    }));
});

observer.observe(document.documentElement, { childList: true, subtree: true });
