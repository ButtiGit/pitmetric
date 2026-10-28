function initializeSessionWeather() {
    for (const button of document.querySelectorAll('[data-pm-weather-gps]')) {
        if (button.dataset.pmWeatherInitialized) continue;
        button.dataset.pmWeatherInitialized = 'true';

        button.addEventListener('click', () => {
            const form = button.closest('form[data-pm-weather-location]');
            const status = form?.querySelector('[data-pm-weather-status]');

            if (!form || !status) return;

            const showError = message => {
                button.disabled = false;
                status.textContent = message;
                status.setAttribute('role', 'alert');
            };

            if (!window.isSecureContext || !navigator.geolocation) {
                showError(button.dataset.weatherUnsupported);
                return;
            }

            button.disabled = true;
            status.setAttribute('role', 'status');
            status.textContent = button.dataset.weatherLocating;

            navigator.geolocation.getCurrentPosition(position => {
                form.elements.latitude.value = position.coords.latitude.toFixed(4);
                form.elements.longitude.value = position.coords.longitude.toFixed(4);
                status.textContent = button.dataset.weatherSaving;
                form.requestSubmit();
            }, error => {
                const message = error.code === 1
                    ? button.dataset.weatherDenied
                    : error.code === 3
                        ? button.dataset.weatherTimeout
                        : button.dataset.weatherUnavailable;
                showError(message);
            }, {
                enableHighAccuracy: true,
                maximumAge: 60000,
                timeout: 12000,
            });
        });
    }
}

document.addEventListener('DOMContentLoaded', initializeSessionWeather);
document.addEventListener('livewire:navigated', initializeSessionWeather);
if (document.readyState !== 'loading') initializeSessionWeather();
