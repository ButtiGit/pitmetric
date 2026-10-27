import { createApp } from 'vue';
import { IonicVue } from '@ionic/vue';
import { Capacitor } from '@capacitor/core';
import App from './App.vue';
import { installFirstRunOnboarding } from './onboarding';
import '@ionic/vue/css/core.css';
import '@ionic/vue/css/normalize.css';
import '@ionic/vue/css/structure.css';
import '@ionic/vue/css/typography.css';
import './theme.css';
import './trackside.css';
import './ios-polish.css';
import './ios-glance.css';
import './onboarding.css';

createApp(App).use(IonicVue).mount('#app');
if (import.meta.env.MODE !== 'pwa') {
  // Native onboarding describes cloud sync, which is not part of the browser beta yet.
  installFirstRunOnboarding();
}

if (import.meta.env.MODE === 'pwa' && !Capacitor.isNativePlatform() && 'serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    void navigator.serviceWorker.register(`${import.meta.env.BASE_URL}sw.js`, {
      scope: import.meta.env.BASE_URL
    }).catch(() => {
      // The browser preview remains available online even without an offline worker.
    });
  });
}

