import { createRouter, createWebHistory } from '@ionic/vue-router';
import { appState } from './state';
import { restoreSession } from './services/auth';
import GalleryPage from './views/GalleryPage.vue';
import HomePage from './views/HomePage.vue';
import LoginPage from './views/LoginPage.vue';
import PitPage from './views/PitPage.vue';
import SettingsPage from './views/SettingsPage.vue';
import TabsPage from './views/TabsPage.vue';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: '/app/home' },
    { path: '/login', component: LoginPage },
    {
      path: '/app',
      component: TabsPage,
      children: [
        { path: '', redirect: '/app/home' },
        { path: 'home', component: HomePage },
        { path: 'pit', component: PitPage },
        { path: 'gallery', component: GalleryPage },
        { path: 'settings', component: SettingsPage },
      ],
    },
  ],
});

router.beforeEach(async (to) => {
  if (!appState.ready) {
    await restoreSession();
    appState.ready = true;
  }
  if (!appState.session && to.path !== '/login') return '/login';
  if (appState.session && to.path === '/login') return '/app/home';
  return true;
});

export default router;
