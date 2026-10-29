function initializeCircuitGeolocation() {
    for (const button of document.querySelectorAll('[data-pm-circuit-gps]')) {
        if (button.dataset.pmCircuitGpsInitialized) continue;
        button.dataset.pmCircuitGpsInitialized = 'true';

        button.addEventListener('click', () => {
            const fieldset = button.closest('[data-pm-circuit-coordinates]');
            const latitude = fieldset?.querySelector('input[name="latitude"]');
            const longitude = fieldset?.querySelector('input[name="longitude"]');
            const status = fieldset?.querySelector('[data-pm-circuit-gps-status]');

            if (!(latitude instanceof HTMLInputElement) || !(longitude instanceof HTMLInputElement) || !status) return;

            const report = (message, isError = false) => {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                status.textContent = message;
                status.setAttribute('role', isError ? 'alert' : 'status');
                status.classList.toggle('text-pm-danger', isError);
                status.classList.toggle('text-pm-muted', !isError);
            };

            if (!window.isSecureContext || !navigator.geolocation) {
                report(button.dataset.gpsUnsupported, true);
                return;
            }

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            status.setAttribute('role', 'status');
            status.classList.remove('text-pm-danger');
            status.classList.add('text-pm-muted');
            status.textContent = button.dataset.gpsLocating;

            navigator.geolocation.getCurrentPosition(position => {
                const { latitude: lat, longitude: lon } = position.coords;

                if (!Number.isFinite(lat) || !Number.isFinite(lon) || Math.abs(lat) > 90 || Math.abs(lon) > 180) {
                    report(button.dataset.gpsUnavailable, true);
                    return;
                }

                latitude.value = lat.toFixed(8);
                longitude.value = lon.toFixed(8);
                latitude.dispatchEvent(new Event('input', { bubbles: true }));
                longitude.dispatchEvent(new Event('input', { bubbles: true }));
                report(button.dataset.gpsReady);
            }, error => {
                const message = error.code === 1
                    ? button.dataset.gpsDenied
                    : error.code === 3
                        ? button.dataset.gpsTimeout
                        : button.dataset.gpsUnavailable;
                report(message, true);
            }, {
                enableHighAccuracy: true,
                maximumAge: 60000,
                timeout: 15000,
            });
        });
    }
}

document.addEventListener('DOMContentLoaded', initializeCircuitGeolocation);
document.addEventListener('livewire:navigated', initializeCircuitGeolocation);
if (document.readyState !== 'loading') initializeCircuitGeolocation();
