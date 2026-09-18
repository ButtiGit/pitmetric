function inputValue(input, name) {
    return name.replace(/\[([^\]]*)\]/g, '.$1').replace(/\.$/, '').split('.')
        .reduce((value, key) => value?.[key], input);
}

function syncVehicleOptions(form) {
    if (form.dataset.pmSyncing) return;
    form.dataset.pmSyncing = 'true';
    const vehicle = form.querySelector('select[name="vehicle_id"]');
    const version = form.querySelector('select[name="configuration_version_id"]');
    const selectedVehicle = vehicle?.value || version?.selectedOptions[0]?.dataset.vehicleId;
    for (const select of form.querySelectorAll('select[data-pm-vehicle-options]')) {
        if (select === version && !vehicle) continue;
        for (const option of select.options) {
            const unavailable = Boolean(option.dataset.vehicleId && selectedVehicle && option.dataset.vehicleId !== selectedVehicle);
            option.hidden = unavailable;
            option.disabled = unavailable;
        }
        if (select.selectedOptions[0]?.disabled) select.value = '';
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }
    delete form.dataset.pmSyncing;
}

function initializeWorkspaceForms() {
    if (!document.querySelector('[data-pm-workspace]')) return;
    for (const table of document.querySelectorAll('[data-pm-responsive-table]')) {
        const headers = Array.from(table.tHead?.rows[0]?.cells || [], cell => cell.textContent.trim());
        for (const row of table.tBodies[0]?.rows || []) {
            Array.from(row.cells).forEach((cell, index) => {
                cell.dataset.label = headers[index] || '';
                cell.toggleAttribute('data-pm-empty-cell', !cell.textContent.trim() && !cell.children.length);
            });
        }
    }
    for (const field of document.querySelectorAll('[data-pm-workspace] input, [data-pm-workspace] select, [data-pm-workspace] textarea')) {
        const label = field.closest('label')?.querySelector('.pm-label')?.textContent.trim();
        if (label && !field.hasAttribute('aria-label') && !field.hasAttribute('aria-labelledby')) field.setAttribute('aria-label', label);
    }
    for (const dialog of document.querySelectorAll('dialog[data-pm-crud-dialog]')) {
        if (dialog.dataset.pmInitialized) continue;
        dialog.dataset.pmInitialized = 'true';
        const forms = dialog.querySelectorAll('form');
        for (const form of forms) {
            const marker = document.createElement('input');
            marker.type = 'hidden';
            marker.name = '_pm_dialog';
            marker.value = dialog.id;
            form.append(marker);
        }
        const stateElement = dialog.querySelector('[data-pm-form-state]');
        if (stateElement) {
            const { input, errors } = JSON.parse(stateElement.textContent);
            for (const field of dialog.querySelectorAll('input[name], select[name], textarea[name]')) {
                if (['_token', '_method', '_pm_dialog'].includes(field.name) || field.type === 'file') continue;
                const value = inputValue(input, field.name);
                if (['checkbox', 'radio'].includes(field.type)) {
                    field.checked = Array.isArray(value) ? value.map(String).includes(field.value) : String(value ?? '') === field.value;
                } else if (value !== undefined) field.value = value ?? '';
                const errorKey = field.name.replace(/\[([^\]]+)\]/g, '.$1').replace(/\[\]$/, '');
                const messages = errors[errorKey] || Object.entries(errors).find(([key]) => key.startsWith(`${errorKey}.`))?.[1];
                if (messages) {
                    const error = document.createElement('p');
                    error.id = `${dialog.id}-error-${errorKey.replace(/[^a-z\d]/gi, '-')}`;
                    error.className = 'text-xs text-pm-danger';
                    error.textContent = messages.join(' ');
                    field.setAttribute('aria-invalid', 'true');
                    field.setAttribute('aria-describedby', error.id);
                    field.closest('label')?.append(error);
                }
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            }
            dialog.showModal();
            dialog.querySelector('[data-pm-form-errors]')?.focus();
        }
        for (const form of forms) {
            syncVehicleOptions(form);
            form.addEventListener('change', event => {
                if (['vehicle_id', 'configuration_version_id'].includes(event.target.name)) syncVehicleOptions(form);
            });
        }
    }

    // Restore direct links to the next operation after a normal or Livewire navigation.
    const target = document.getElementById(location.hash.slice(1));
    if (target?.matches('dialog[data-pm-crud-dialog]') && !target.open && !document.querySelector('dialog[open]')) target.showModal();
}

document.addEventListener('submit', event => {
    const form = event.target;
    if (!form.closest('[data-pm-workspace]') || event.defaultPrevented || form.method.toLowerCase() !== 'post') return;
    if (form.dataset.pmSubmitting) {
        event.preventDefault();
        return;
    }
    form.dataset.pmSubmitting = 'true';
    form.setAttribute('aria-busy', 'true');
});
window.addEventListener('pageshow', () => {
    for (const form of document.querySelectorAll('[data-pm-submitting]')) {
        delete form.dataset.pmSubmitting;
        form.removeAttribute('aria-busy');
    }
});
document.addEventListener('DOMContentLoaded', initializeWorkspaceForms);
document.addEventListener('livewire:navigated', initializeWorkspaceForms);
window.addEventListener('hashchange', initializeWorkspaceForms);
if (document.readyState !== 'loading') initializeWorkspaceForms();
