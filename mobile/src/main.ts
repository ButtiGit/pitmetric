import { createApp } from 'vue';
import { IonicVue } from '@ionic/vue';
import App from './App.vue';
import '@ionic/vue/css/core.css';
import '@ionic/vue/css/normalize.css';
import '@ionic/vue/css/structure.css';
import '@ionic/vue/css/typography.css';
import './theme.css';
import './trackside.css';
import './ios-polish.css';
import './ios-glance.css';

createApp(App).use(IonicVue).mount('#app');
