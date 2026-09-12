const appSelector = '.pitmetric-app';
const rehomeSelector = [
    'select[data-pm-enhanced="true"]',
    'input[type="date"][data-pm-enhanced="true"]',
    'input[type="time"][data-pm-enhanced="true"]',
    'input[type="datetime-local"][data-pm-enhanced="true"]',
    'input[type="month"][data-pm-enhanced="true"]',
    'input[type="week"][data-pm-enhanced="true"]',
].join(',');

let scanQueued = false;

function owningCustomControl(source) {
    const label = source.closest('label');
    if (!label) return null;

    const customControl = Array.from(label.querySelectorAll('.pm-custom-control'))
        .find(control => control.contains(source));
    if (!customControl) return null;

    return { label, customControl };
}

function rehomeNativeSource(source) {
    if (!(source instanceof HTMLElement) || source.dataset.pmRehomed === 'true') return;

    const ownership = owningCustomControl(source);
    if (!ownership) return;

    const { label } = ownership;
    const parent = label.parentNode;
    if (!parent) return;

    // Native date/select/time controls nested in a <label> can still receive the
    // label's activation behaviour in Chromium even after a custom button was
    // drawn over them. Moving the data-bearing source outside the label makes
    // the custom button the label's only interactive target, so the OS/browser
    // picker cannot open behind the PitMetric popover.
    parent.insertBefore(source, label.nextSibling);
    source.dataset.pmRehomed = 'true';
    source.setAttribute('aria-hidden', 'true');
    source.tabIndex = -1;
}

function scan() {
    document.querySelectorAll(appSelector).forEach(scope => {
        scope.querySelectorAll(rehomeSelector).forEach(rehomeNativeSource);
    });
}

function queueScan() {
    if (scanQueued) return;
    scanQueued = true;

    queueMicrotask(() => {
        scanQueued = false;
        scan();
    });
}

// Last line of defence: an enhanced native source must never be the element
// that receives a pointer activation. This is intentionally capture-phase so
// Chromium cannot open a native picker before the custom control handles it.
function blockNativeActivation(event) {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const source = target.closest(rehomeSelector);
    if (!source || !source.closest(appSelector)) return;

    event.preventDefault();
    event.stopImmediatePropagation();

    const trigger = source.previousElementSibling?.querySelector?.('.pm-control-trigger')
        ?? source.parentElement?.querySelector('.pm-control-trigger');

    if (trigger instanceof HTMLButtonElement) trigger.focus();
}

document.addEventListener('pointerdown', blockNativeActivation, true);
document.addEventListener('mousedown', blockNativeActivation, true);
document.addEventListener('click', blockNativeActivation, true);

document.addEventListener('DOMContentLoaded', () => queueScan());
document.addEventListener('livewire:initialized', () => queueScan());
document.addEventListener('livewire:navigated', () => queueScan());
document.addEventListener('pitmetric:rendered', () => queueScan());

const observer = new MutationObserver(records => {
    for (const record of records) {
        for (const node of record.addedNodes) {
            if (node instanceof Element) queueScan();
        }
    }
});

observer.observe(document.documentElement, { childList: true, subtree: true });

scan();
requestAnimationFrame(() => scan());
