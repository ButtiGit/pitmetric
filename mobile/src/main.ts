import { createApp } from 'vue';
import { IonicVue } from '@ionic/vue';
import App from './App.vue';
import router from './router';
import { startConnectivity, syncNow } from './services/sync';
import '@ionic/vue/css/core.css';
import '@ionic/vue/css/normalize.css';
import '@ionic/vue/css/structure.css';
import '@ionic/vue/css/typography.css';
import '@ionic/vue/css/padding.css';
import '@ionic/vue/css/flex-utils.css';
import './theme.css';

const app = createApp(App).use(IonicVue).use(router);

void startConnectivity();
router.isReady().then(() => {
  app.mount('#app');
  void syncNow();
});
