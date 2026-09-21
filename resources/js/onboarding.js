const TOUR_VERSION = 'v1';

let activeTour = null;
let documentListenersBound = false;

const isVisible = (element) => {
    if (!(element instanceof HTMLElement)) {
        return false;
    }

    const style = window.getComputedStyle(element);
    const rect = element.getBoundingClientRect();

    return style.display !== 'none'
        && style.visibility !== 'hidden'
        && Number(style.opacity) !== 0
        && rect.width > 0
        && rect.height > 0;
};

const firstVisible = (...selectors) => {
    for (const selector of selectors) {
        const match = Array.from(document.querySelectorAll(selector)).find(isVisible);

        if (match) {
            return match;
        }
    }

    return null;
};

const readCopy = (guide) => {
    const node = guide.querySelector('[data-pm-tour-copy]');

    if (!node) {
        return null;
    }

    try {
        return JSON.parse(node.textContent || '{}');
    } catch {
        return null;
    }
};

const storageKey = (guide) => `pitmetric:onboarding:${TOUR_VERSION}:${guide.dataset.pmUserId || 'anonymous'}`;

const rememberFullTour = (guide) => {
    try {
        window.localStorage.setItem(storageKey(guide), 'complete');
    } catch {
        // Storage can be unavailable in strict/private browser modes. The tour still works.
    }
};

const fullTourCompleted = (guide) => {
    try {
        return window.localStorage.getItem(storageKey(guide)) === 'complete';
    } catch {
        return false;
    }
};

const closeGuidePanel = (guide) => {
    const panel = guide.querySelector('[data-pm-guide-panel]');
    const toggle = guide.querySelector('[data-pm-guide-toggle]');

    if (!panel || !toggle) {
        return;
    }

    panel.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
};

const openGuidePanel = (guide) => {
    const panel = guide.querySelector('[data-pm-guide-panel]');
    const toggle = guide.querySelector('[data-pm-guide-toggle]');

    if (!panel || !toggle) {
        return;
    }

    panel.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
};

const uniqueSteps = (steps) => {
    const targets = new Set();

    return steps.filter((step) => {
        if (!step.target || !isVisible(step.target)) {
            return false;
        }

        if (targets.has(step.target)) {
            return false;
        }

        targets.add(step.target);
        return true;
    });
};

const buildPageTour = (guide, copy) => {
    const pageTitle = firstVisible('[data-pm-page-header]', 'main h1', '[data-pm-workspace] h1');
    const workflow = firstVisible('[data-pm-workflow-nav]');
    const primaryAction = firstVisible(
        '[data-pm-primary-action]',
        'main .pm-race-button',
        'main button.pm-race-button',
        'main button:not([disabled])',
        'main a[href]',
    );
    const pageContent = firstVisible(
        '[data-pm-page-content]',
        '[data-pm-workspace] .pm-panel',
        'main section',
        'main article',
    );
    const help = guide.querySelector('[data-pm-guide-toggle]');

    return uniqueSteps([
        {
            target: pageTitle || pageContent,
            title: copy.page.title,
            body: copy.page.description,
        },
        {
            target: workflow,
            title: copy.page.workflow_title,
            body: copy.page.workflow_body,
        },
        {
            target: primaryAction,
            title: copy.page.action_title,
            body: copy.page.action_body,
        },
        {
            target: pageContent,
            title: copy.page.detail_title,
            body: copy.page.detail_body,
        },
        {
            target: help,
            title: copy.page.help_title,
            body: copy.page.help_body,
        },
    ]);
};

const buildFullTour = (guide, copy) => {
    const sidebar = firstVisible('[data-pm-tour-sidebar]', '[data-pm-tour-mobile-header]');
    const nextAction = firstVisible('[data-pm-dashboard-next]', '[data-demo-dashboard] h1');
    const workflow = firstVisible('[data-pm-dashboard-workflow]', '[data-pm-workflow-nav]');
    const stats = firstVisible('[data-pm-dashboard-stats]');
    const event = firstVisible('[data-pm-dashboard-event]');
    const help = guide.querySelector('[data-pm-guide-toggle]');

    return uniqueSteps([
        {
            target: nextAction,
            title: copy.full.next_title,
            body: copy.full.next_body,
        },
        {
            target: workflow,
            title: copy.full.workflow_title,
            body: copy.full.workflow_body,
        },
        {
            target: sidebar,
            title: copy.full.navigation_title,
            body: copy.full.navigation_body,
        },
        {
            target: stats,
            title: copy.full.status_title,
            body: copy.full.status_body,
        },
        {
            target: event,
            title: copy.full.event_title,
            body: copy.full.event_body,
        },
        {
            target: help,
            title: copy.full.help_title,
            body: copy.full.help_body,
        },
    ]);
};

const removeTour = ({ remember = false } = {}) => {
    if (!activeTour) {
        return;
    }

    const { guide, target, backdrop, card, kind, restoreFocus } = activeTour;

    target?.classList.remove('pm-tour-target');
    backdrop?.remove();
    card?.remove();
    document.documentElement.classList.remove('pm-tour-running');

    if (remember && kind === 'full') {
        rememberFullTour(guide);
    }

    activeTour = null;

    if (restoreFocus && document.contains(restoreFocus)) {
        restoreFocus.focus({ preventScroll: true });
    }
};

const positionCard = (card, target) => {
    if (window.matchMedia('(max-width: 640px)').matches) {
        card.style.removeProperty('top');
        card.style.removeProperty('left');
        card.style.removeProperty('right');
        return;
    }

    const rect = target.getBoundingClientRect();
    const cardRect = card.getBoundingClientRect();
    const gap = 16;
    const viewportPadding = 16;

    let top = rect.bottom + gap;

    if (top + cardRect.height > window.innerHeight - viewportPadding) {
        top = Math.max(viewportPadding, rect.top - cardRect.height - gap);
    }

    let left = rect.left;
    left = Math.min(left, window.innerWidth - cardRect.width - viewportPadding);
    left = Math.max(viewportPadding, left);

    card.style.top = `${Math.round(top)}px`;
    card.style.left = `${Math.round(left)}px`;
};

const renderStep = () => {
    if (!activeTour) {
        return;
    }

    const { steps, card, backdrop, copy } = activeTour;
    const step = steps[activeTour.index];

    if (!step) {
        removeTour({ remember: activeTour.kind === 'full' });
        return;
    }

    activeTour.target?.classList.remove('pm-tour-target');
    activeTour.target = step.target;
    step.target.classList.add('pm-tour-target');
    backdrop.hidden = false;

    step.target.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });

    card.querySelector('[data-pm-tour-step]').textContent = copy.labels.step
        .replace(':current', String(activeTour.index + 1))
        .replace(':total', String(steps.length));
    card.querySelector('[data-pm-tour-title]').textContent = step.title;
    card.querySelector('[data-pm-tour-body]').textContent = step.body;

    const back = card.querySelector('[data-pm-tour-back]');
    const next = card.querySelector('[data-pm-tour-next]');

    back.disabled = activeTour.index === 0;
    next.textContent = activeTour.index === steps.length - 1 ? copy.labels.done : copy.labels.next;

    window.setTimeout(() => {
        if (!activeTour) {
            return;
        }

        positionCard(card, step.target);
        next.focus({ preventScroll: true });
    }, 180);
};

const createTourCard = (copy) => {
    const card = document.createElement('section');
    card.className = 'pm-tour-card';
    card.setAttribute('role', 'dialog');
    card.setAttribute('aria-modal', 'true');
    card.setAttribute('aria-label', copy.labels.dialog);
    card.innerHTML = `
        <div class="pm-tour-card__topline">
            <span data-pm-tour-step></span>
            <button type="button" class="pm-tour-card__close" data-pm-tour-close aria-label="${copy.labels.close}">×</button>
        </div>
        <h2 class="pm-tour-card__title" data-pm-tour-title></h2>
        <p class="pm-tour-card__body" data-pm-tour-body></p>
        <div class="pm-tour-card__actions">
            <button type="button" class="pm-tour-button pm-tour-button--ghost" data-pm-tour-skip>${copy.labels.skip}</button>
            <div class="pm-tour-card__nav">
                <button type="button" class="pm-tour-button pm-tour-button--ghost" data-pm-tour-back>${copy.labels.back}</button>
                <button type="button" class="pm-tour-button pm-tour-button--primary" data-pm-tour-next>${copy.labels.next}</button>
            </div>
        </div>
    `;

    card.querySelector('[data-pm-tour-close]').addEventListener('click', () => removeTour({ remember: activeTour?.kind === 'full' }));
    card.querySelector('[data-pm-tour-skip]').addEventListener('click', () => removeTour({ remember: activeTour?.kind === 'full' }));
    card.querySelector('[data-pm-tour-back]').addEventListener('click', () => {
        if (!activeTour || activeTour.index === 0) {
            return;
        }

        activeTour.index -= 1;
        renderStep();
    });
    card.querySelector('[data-pm-tour-next]').addEventListener('click', () => {
        if (!activeTour) {
            return;
        }

        if (activeTour.index >= activeTour.steps.length - 1) {
            removeTour({ remember: activeTour.kind === 'full' });
            return;
        }

        activeTour.index += 1;
        renderStep();
    });

    return card;
};

const startTour = (guide, kind) => {
    const copy = readCopy(guide);

    if (!copy) {
        return;
    }

    if (kind === 'full' && guide.dataset.pmContext !== 'dashboard') {
        const url = new URL(guide.dataset.pmDashboardUrl, window.location.origin);
        url.searchParams.set('pm-tour', 'full');
        window.location.assign(url.toString());
        return;
    }

    removeTour();
    closeGuidePanel(guide);

    const steps = kind === 'full'
        ? buildFullTour(guide, copy)
        : buildPageTour(guide, copy);

    if (steps.length === 0) {
        return;
    }

    const backdrop = document.createElement('div');
    backdrop.className = 'pm-tour-backdrop';
    backdrop.setAttribute('aria-hidden', 'true');

    const card = createTourCard(copy);
    const restoreFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;

    document.body.append(backdrop, card);
    document.documentElement.classList.add('pm-tour-running');

    activeTour = {
        guide,
        kind,
        steps,
        index: 0,
        target: null,
        backdrop,
        card,
        copy,
        restoreFocus,
    };

    renderStep();
};

const bindDocumentListeners = () => {
    if (documentListenersBound) {
        return;
    }

    documentListenersBound = true;

    document.addEventListener('keydown', (event) => {
        if (!activeTour) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            removeTour({ remember: activeTour.kind === 'full' });
            return;
        }

        if (event.key === 'ArrowRight') {
            event.preventDefault();
            activeTour.card.querySelector('[data-pm-tour-next]')?.click();
        }

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            activeTour.card.querySelector('[data-pm-tour-back]')?.click();
        }
    });

    window.addEventListener('resize', () => {
        if (activeTour?.target) {
            positionCard(activeTour.card, activeTour.target);
        }
    });

    document.addEventListener('click', (event) => {
        const guide = document.querySelector('[data-pm-guide]');

        if (!guide || guide.contains(event.target)) {
            return;
        }

        closeGuidePanel(guide);
    });
};

const cleanTourQuery = () => {
    const url = new URL(window.location.href);

    if (!url.searchParams.has('pm-tour')) {
        return false;
    }

    const shouldStart = url.searchParams.get('pm-tour') === 'full';
    url.searchParams.delete('pm-tour');
    window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);

    return shouldStart;
};

const initializeOnboarding = () => {
    bindDocumentListeners();
    removeTour();

    const guide = document.querySelector('[data-pm-guide]');

    if (!guide || guide.dataset.pmGuideBound === 'true') {
        return;
    }

    guide.dataset.pmGuideBound = 'true';

    const toggle = guide.querySelector('[data-pm-guide-toggle]');
    const pageTour = guide.querySelector('[data-pm-start-page-tour]');
    const fullTour = guide.querySelector('[data-pm-start-full-tour]');

    toggle?.addEventListener('click', () => {
        const panel = guide.querySelector('[data-pm-guide-panel]');

        if (panel?.hidden) {
            openGuidePanel(guide);
        } else {
            closeGuidePanel(guide);
        }
    });

    pageTour?.addEventListener('click', () => startTour(guide, 'page'));
    fullTour?.addEventListener('click', () => startTour(guide, 'full'));

    const forcedFullTour = cleanTourQuery();
    const shouldAutoStart = guide.dataset.pmContext === 'dashboard' && !fullTourCompleted(guide);

    if (forcedFullTour || shouldAutoStart) {
        window.setTimeout(() => startTour(guide, 'full'), forcedFullTour ? 200 : 700);
    }
};

document.addEventListener('DOMContentLoaded', initializeOnboarding);
document.addEventListener('livewire:navigated', initializeOnboarding);

if (document.readyState !== 'loading') {
    initializeOnboarding();
}
