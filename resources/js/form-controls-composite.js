const appSelector = '.pitmetric-app';
const enhancedSelector = '[data-pm-enhanced="true"]';
const locale = document.documentElement.lang?.toLowerCase().startsWith('it') ? 'it-IT' : 'en-GB';
const isItalian = locale === 'it-IT';

const labels = {
    month: isItalian ? 'Mese' : 'Month',
    year: isItalian ? 'Anno' : 'Year',
    week: isItalian ? 'Settimana' : 'Week',
    date: isItalian ? 'Data' : 'Date',
    time: isItalian ? 'Ora' : 'Time',
};

function dispatchValue(source) {
    source.dispatchEvent(new Event('input', { bubbles: true }));
    source.dispatchEvent(new Event('change', { bubbles: true }));
}

function createComposite(source, kind) {
    const wrapper = document.createElement('div');
    wrapper.className = `pm-composite-control pm-composite-${kind}`;
    source.parentNode.insertBefore(wrapper, source);
    wrapper.appendChild(source);

    source.dataset.pmEnhanced = 'true';
    source.classList.add('pm-native-control-source');
    source.tabIndex = -1;
    source.setAttribute('aria-hidden', 'true');

    return wrapper;
}

function proxyInput(type, label) {
    const input = document.createElement('input');
    input.type = type;
    input.setAttribute('aria-label', label);
    return input;
}

function proxySelect(label, options) {
    const select = document.createElement('select');
    select.setAttribute('aria-label', label);
    options.forEach(({ value, label: text }) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = text;
        select.appendChild(option);
    });
    return select;
}

function mirrorState(source, controls) {
    controls.forEach(control => {
        control.disabled = source.disabled;
        control.required = source.required;
    });

    source.addEventListener('invalid', event => {
        event.preventDefault();
        controls.find(control => !control.disabled)?.focus();
    });
}

function clampInteger(value, fallback, min, max) {
    const parsed = Number.parseInt(value, 10);
    if (!Number.isFinite(parsed)) return fallback;
    return Math.min(max, Math.max(min, parsed));
}

function enhanceMonth(source) {
    if (source.matches(enhancedSelector)) return;

    const wrapper = createComposite(source, 'month');
    const monthFormatter = new Intl.DateTimeFormat(locale, { month: 'short' });
    const month = proxySelect(labels.month, Array.from({ length: 12 }, (_, index) => ({
        value: String(index + 1).padStart(2, '0'),
        label: monthFormatter.format(new Date(2026, index, 1)),
    })));
    const year = proxyInput('number', labels.year);
    year.step = '1';
    year.min = source.min?.slice(0, 4) || '1900';
    year.max = source.max?.slice(0, 4) || '2100';
    wrapper.append(month, year);
    mirrorState(source, [month, year]);

    function syncFromSource() {
        const match = /^(\d{4})-(\d{2})$/.exec(source.value || '');
        const now = new Date();
        year.value = match?.[1] || String(now.getFullYear());
        month.value = match?.[2] || String(now.getMonth() + 1).padStart(2, '0');
    }

    function syncToSource() {
        const currentYear = clampInteger(year.value, new Date().getFullYear(), 1, 9999);
        const raw = `${String(currentYear).padStart(4, '0')}-${month.value}`;
        source.value = raw;
        dispatchValue(source);
    }

    month.addEventListener('change', syncToSource);
    year.addEventListener('input', syncToSource);
    source.addEventListener('change', syncFromSource);
    syncFromSource();
}

function enhanceWeek(source) {
    if (source.matches(enhancedSelector)) return;

    const wrapper = createComposite(source, 'week');
    const week = proxyInput('number', labels.week);
    week.min = '1';
    week.max = '53';
    week.step = '1';
    const year = proxyInput('number', labels.year);
    year.step = '1';
    year.min = source.min?.slice(0, 4) || '1900';
    year.max = source.max?.slice(0, 4) || '2100';
    wrapper.append(week, year);
    mirrorState(source, [week, year]);

    function currentIsoWeek() {
        const now = new Date();
        const target = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()));
        const day = target.getUTCDay() || 7;
        target.setUTCDate(target.getUTCDate() + 4 - day);
        const isoYear = target.getUTCFullYear();
        const start = new Date(Date.UTC(isoYear, 0, 1));
        const isoWeek = Math.ceil((((target - start) / 86400000) + 1) / 7);
        return { year: isoYear, week: isoWeek };
    }

    function syncFromSource() {
        const match = /^(\d{4})-W(\d{2})$/.exec(source.value || '');
        const fallback = currentIsoWeek();
        year.value = match?.[1] || String(fallback.year);
        week.value = match ? String(Number(match[2])) : String(fallback.week);
    }

    function syncToSource() {
        const currentYear = clampInteger(year.value, currentIsoWeek().year, 1, 9999);
        const currentWeek = clampInteger(week.value, currentIsoWeek().week, 1, 53);
        source.value = `${String(currentYear).padStart(4, '0')}-W${String(currentWeek).padStart(2, '0')}`;
        dispatchValue(source);
    }

    week.addEventListener('input', syncToSource);
    year.addEventListener('input', syncToSource);
    source.addEventListener('change', syncFromSource);
    syncFromSource();
}

function enhanceDateTime(source) {
    if (source.matches(enhancedSelector)) return;

    const wrapper = createComposite(source, 'datetime');
    const date = proxyInput('date', labels.date);
    const time = proxyInput('time', labels.time);
    if (source.step) time.step = source.step;
    if (source.min) {
        date.min = source.min.slice(0, 10);
        if (source.min.includes('T')) time.min = source.min.slice(11, 16);
    }
    if (source.max) {
        date.max = source.max.slice(0, 10);
        if (source.max.includes('T')) time.max = source.max.slice(11, 16);
    }
    wrapper.append(date, time);
    mirrorState(source, [date, time]);

    function syncFromSource() {
        const match = /^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})/.exec(source.value || '');
        if (match) {
            date.value = match[1];
            time.value = match[2];
            return;
        }

        const now = new Date();
        date.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        time.value = `${String(now.getHours()).padStart(2, '0')}:00`;
    }

    function syncToSource() {
        source.value = date.value && time.value ? `${date.value}T${time.value}` : '';
        dispatchValue(source);
    }

    date.addEventListener('change', syncToSource);
    time.addEventListener('change', syncToSource);
    source.addEventListener('change', syncFromSource);
    syncFromSource();
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
        container.querySelectorAll('input[type="month"]:not([data-pm-enhanced="true"]):not([data-pm-skip])').forEach(enhanceMonth);
        container.querySelectorAll('input[type="week"]:not([data-pm-enhanced="true"]):not([data-pm-skip])').forEach(enhanceWeek);
        container.querySelectorAll('input[type="datetime-local"]:not([data-pm-enhanced="true"]):not([data-pm-skip])').forEach(enhanceDateTime);
    });
}

enhanceWithin(document);

const observer = new MutationObserver(records => {
    records.forEach(record => record.addedNodes.forEach(node => {
        if (node instanceof Element) enhanceWithin(node);
    }));
});

observer.observe(document.documentElement, { childList: true, subtree: true });
