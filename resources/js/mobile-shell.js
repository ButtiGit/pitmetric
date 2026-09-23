const presetState = {
    trackside: {
        order: ['trackside', 'event', 'performance', 'garage', 'maintenance', 'management'],
        hidden: ['maintenance', 'management'],
        labels: { it: 'Pista', en: 'Trackside' },
    },
    technical: {
        order: ['garage', 'maintenance', 'trackside', 'event', 'performance', 'management'],
        hidden: ['performance', 'management'],
        labels: { it: 'Tecnico', en: 'Technical' },
    },
    performance: {
        order: ['performance', 'trackside', 'event', 'garage', 'maintenance', 'management'],
        hidden: ['maintenance', 'management'],
        labels: { it: 'Performance', en: 'Performance' },
    },
    complete: {
        order: ['trackside', 'event', 'garage', 'maintenance', 'performance', 'management'],
        hidden: [],
        labels: { it: 'Completo', en: 'Complete' },
    },
};

const activeLocale = () => document.documentElement.lang?.toLowerCase().startsWith('it') ? 'it' : 'en';

const closeDialogOnBackdrop = (dialog) => {
    if (!dialog || dialog.dataset.pmBackdropBound === '1') {
        return;
    }

    dialog.dataset.pmBackdropBound = '1';
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
};

const initMobileNavigation = () => {
    const dialog = document.querySelector('[data-pm-mobile-more]');
    const trigger = document.querySelector('[data-pm-mobile-more-trigger]');

    if (!(dialog instanceof HTMLDialogElement) || !trigger || trigger.dataset.pmBound === '1') {
        return;
    }

    trigger.dataset.pmBound = '1';
    trigger.addEventListener('click', () => dialog.showModal());
    dialog.querySelectorAll('[data-pm-mobile-more-close]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
    });
    dialog.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => dialog.close());
    });
    closeDialogOnBackdrop(dialog);
};

const normalizeState = (rawState, availableWidgets) => {
    const fallback = presetState.trackside;
    const rawOrder = Array.isArray(rawState?.order) ? rawState.order : fallback.order;
    const order = [
        ...rawOrder.filter((key) => availableWidgets.includes(key)),
        ...availableWidgets.filter((key) => !rawOrder.includes(key)),
    ];

    const hidden = Array.isArray(rawState?.hidden)
        ? rawState.hidden.filter((key) => availableWidgets.includes(key))
        : fallback.hidden.filter((key) => availableWidgets.includes(key));

    return {
        preset: typeof rawState?.preset === 'string' ? rawState.preset : 'trackside',
        order,
        hidden,
    };
};

const initMobileDashboard = () => {
    const root = document.querySelector('[data-pm-mobile-cockpit]');

    if (!root || root.dataset.pmBound === '1') {
        return;
    }

    root.dataset.pmBound = '1';

    const grid = root.querySelector('[data-pm-cockpit-grid]');
    const cards = [...root.querySelectorAll('[data-pm-cockpit-card]')];
    const settingsDialog = root.querySelector('[data-pm-dashboard-settings]');
    const focusLabel = root.querySelector('[data-pm-dashboard-focus]');
    const availableWidgets = cards.map((card) => card.dataset.pmCockpitCard).filter(Boolean);
    const storageKey = `pitmetric.mobile-dashboard.${root.dataset.user || 'guest'}`;

    const readState = () => {
        try {
            return normalizeState(JSON.parse(localStorage.getItem(storageKey) || 'null'), availableWidgets);
        } catch {
            return normalizeState(null, availableWidgets);
        }
    };

    let state = readState();

    const writeState = () => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(state));
        } catch {
            // Personalization remains functional for the current page even if storage is blocked.
        }
    };

    const labelForState = () => {
        const locale = activeLocale();
        if (presetState[state.preset]) {
            return presetState[state.preset].labels[locale];
        }

        return locale === 'it' ? 'Personalizzato' : 'Custom';
    };

    const applyState = () => {
        state.order.forEach((key) => {
            const card = cards.find((item) => item.dataset.pmCockpitCard === key);
            if (card && grid) {
                grid.appendChild(card);
            }
        });

        cards.forEach((card) => {
            const hidden = state.hidden.includes(card.dataset.pmCockpitCard);
            card.hidden = hidden;
            card.setAttribute('aria-hidden', hidden ? 'true' : 'false');
            if (hidden) {
                card.classList.remove('is-open');
                card.querySelector('[data-pm-widget-trigger]')?.setAttribute('aria-expanded', 'false');
            }
        });

        root.querySelectorAll('[data-pm-widget-toggle]').forEach((toggle) => {
            toggle.checked = !state.hidden.includes(toggle.value);
        });

        root.querySelectorAll('[data-pm-dashboard-preset]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.pmDashboardPreset === state.preset);
        });

        if (focusLabel) {
            focusLabel.textContent = labelForState();
        }
    };

    cards.forEach((card) => {
        const trigger = card.querySelector('[data-pm-widget-trigger]');
        if (!trigger) {
            return;
        }

        trigger.addEventListener('click', () => {
            const shouldOpen = !card.classList.contains('is-open');

            cards.forEach((otherCard) => {
                otherCard.classList.remove('is-open');
                otherCard.querySelector('[data-pm-widget-trigger]')?.setAttribute('aria-expanded', 'false');
            });

            if (shouldOpen) {
                card.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');
                window.setTimeout(() => {
                    card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 120);
            }
        });
    });

    if (settingsDialog instanceof HTMLDialogElement) {
        root.querySelector('[data-pm-dashboard-customize]')?.addEventListener('click', () => settingsDialog.showModal());
        root.querySelectorAll('[data-pm-dashboard-settings-close]').forEach((button) => {
            button.addEventListener('click', () => settingsDialog.close());
        });
        closeDialogOnBackdrop(settingsDialog);
    }

    root.querySelectorAll('[data-pm-dashboard-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            const preset = presetState[button.dataset.pmDashboardPreset];
            if (!preset) {
                return;
            }

            state = normalizeState({
                preset: button.dataset.pmDashboardPreset,
                order: preset.order,
                hidden: preset.hidden,
            }, availableWidgets);
            writeState();
            applyState();
        });
    });

    root.querySelectorAll('[data-pm-widget-toggle]').forEach((toggle) => {
        toggle.addEventListener('change', () => {
            const key = toggle.value;
            state.preset = 'custom';
            state.hidden = toggle.checked
                ? state.hidden.filter((item) => item !== key)
                : [...new Set([...state.hidden, key])];
            writeState();
            applyState();
        });
    });

    root.querySelector('[data-pm-dashboard-reset]')?.addEventListener('click', () => {
        state = normalizeState({
            preset: 'trackside',
            order: presetState.trackside.order,
            hidden: presetState.trackside.hidden,
        }, availableWidgets);
        writeState();
        applyState();
    });

    applyState();
};

const initPitModeLayout = () => {
    const lapAction = document.querySelector('#pit-lap');

    if (!(lapAction instanceof HTMLDetailsElement)) {
        return;
    }

    const root = lapAction.closest('.pitmetric-app');
    const actions = lapAction.closest('section');

    if (!root || !actions) {
        return;
    }

    root.dataset.pmPitPage = '1';
    root.querySelector('header')?.setAttribute('data-pm-pit-header', '1');
    actions.dataset.pmPitActions = '1';

    const context = actions.previousElementSibling;
    if (context instanceof HTMLDetailsElement) {
        context.dataset.pmPitContext = '1';

        if (window.matchMedia('(max-width: 639px)').matches && context.dataset.pmPitTouched !== '1') {
            context.removeAttribute('open');
        }

        if (context.dataset.pmPitBound !== '1') {
            context.dataset.pmPitBound = '1';
            context.addEventListener('toggle', () => {
                if (context.open) {
                    context.dataset.pmPitTouched = '1';
                }
            });
        }
    }

    const actionItems = [...actions.querySelectorAll(':scope > details')]
        .filter((item) => item instanceof HTMLDetailsElement);

    actionItems.forEach((item) => {
        item.dataset.pmPitAction = '1';
        item.setAttribute('name', 'pit-action');

        if (item.dataset.pmPitBound === '1') {
            return;
        }

        item.dataset.pmPitBound = '1';
        item.addEventListener('toggle', () => {
            if (!item.open) {
                return;
            }

            actionItems.forEach((otherItem) => {
                if (otherItem !== item) {
                    otherItem.removeAttribute('open');
                }
            });

            window.setTimeout(() => {
                item.scrollIntoView({
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                    block: 'nearest',
                });
            }, 80);
        });
    });
};

const initMobileShell = () => {
    initMobileNavigation();
    initMobileDashboard();
    initPitModeLayout();
};

document.addEventListener('DOMContentLoaded', initMobileShell);
document.addEventListener('livewire:navigated', initMobileShell);

if (document.readyState !== 'loading') {
    initMobileShell();
}
