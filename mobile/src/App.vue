<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
  IonApp, IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButton,
  IonInput, IonTextarea, IonItem, IonLabel, IonList, IonIcon, IonBadge,
  IonTabBar, IonTabButton, IonTabs, IonRouterOutlet, IonCard, IonCardContent,
  IonCardHeader, IonCardTitle, IonSpinner, IonToggle
} from '@ionic/vue';
import { cameraOutline, cloudOfflineOutline, cloudDoneOutline, fingerPrintOutline, imagesOutline, speedometerOutline, syncOutline, logOutOutline } from 'ionicons/icons';
import {
  addGalleryPhoto, biometricAvailable, biometricUnlock, bootLocalStore, galleryItems,
  login, logout, pendingCount, register, restoreSession, startAutoSync, syncNow,
  type GalleryItem, type SessionState
} from './lib';

const ready = ref(false);
const session = ref<SessionState | null>(null);
const authMode = ref<'login' | 'register'>('login');
const name = ref('');
const email = ref('');
const password = ref('');
const authError = ref('');
const biometric = ref(false);
const active = ref<'home' | 'gallery' | 'account'>('home');
const gallery = ref<GalleryItem[]>([]);
const photoTitle = ref('');
const photoDescription = ref('');
const pending = ref(0);
const syncing = ref(false);
let stopAutoSync: (() => Promise<void>) | null = null;

const cloudLabel = computed(() => session.value?.cloudEnabled ? 'Cloud attivo' : 'Solo dispositivo');

async function refreshLocal() {
  gallery.value = await galleryItems();
  pending.value = await pendingCount();
}

async function submitAuth() {
  authError.value = '';
  try {
    session.value = authMode.value === 'login'
      ? await login(email.value, password.value)
      : await register(name.value, email.value, password.value);
    await refreshLocal();
  } catch (error) {
    authError.value = error instanceof Error ? error.message : 'Errore di accesso.';
  }
}

async function unlock() {
  try {
    const restored = await biometricUnlock();
    if (restored) session.value = restored;
  } catch {
    authError.value = 'Sblocco biometrico annullato o non riuscito.';
  }
}

async function addPhoto() {
  if (!photoTitle.value.trim()) return;
  await addGalleryPhoto(photoTitle.value.trim(), photoDescription.value.trim());
  photoTitle.value = '';
  photoDescription.value = '';
  await refreshLocal();
}

async function forceSync() {
  syncing.value = true;
  try { await syncNow(); await refreshLocal(); } finally { syncing.value = false; }
}

async function signOut() {
  await logout();
  session.value = null;
}

onMounted(async () => {
  await bootLocalStore();
  session.value = await restoreSession();
  biometric.value = await biometricAvailable().catch(() => false);
  await refreshLocal();
  stopAutoSync = await startAutoSync(refreshLocal);
  ready.value = true;
});

onUnmounted(async () => { await stopAutoSync?.(); });
</script>

<template>
  <ion-app>
    <div v-if="!ready" class="splash"><div class="mark">PM</div><ion-spinner name="crescent" /></div>

    <ion-page v-else-if="!session" class="auth-page">
      <ion-content :fullscreen="true">
        <section class="auth-wrap">
          <div class="brand"><div class="mark">PM</div><div><strong>PitMetric</strong><span>Trackside, anche offline.</span></div></div>
          <h1>{{ authMode === 'login' ? 'Bentornato' : 'Crea il tuo account' }}</h1>
          <p class="muted">Accedi una volta online. Da quel momento l’app può essere sbloccata con viso, impronta o codice dispositivo.</p>

          <ion-list inset>
            <ion-item v-if="authMode === 'register'"><ion-input v-model="name" label="Nome" label-placement="stacked" autocomplete="name" /></ion-item>
            <ion-item><ion-input v-model="email" label="Email" label-placement="stacked" type="email" autocomplete="email" /></ion-item>
            <ion-item><ion-input v-model="password" label="Password" label-placement="stacked" type="password" autocomplete="current-password" /></ion-item>
          </ion-list>
          <p v-if="authError" class="error">{{ authError }}</p>
          <ion-button expand="block" class="primary" @click="submitAuth">{{ authMode === 'login' ? 'Accedi' : 'Registrati' }}</ion-button>
          <ion-button v-if="biometric && authMode === 'login'" expand="block" fill="outline" @click="unlock"><ion-icon slot="start" :icon="fingerPrintOutline" />Sblocca con biometria</ion-button>
          <ion-button expand="block" fill="clear" @click="authMode = authMode === 'login' ? 'register' : 'login'">{{ authMode === 'login' ? 'Non hai un account? Registrati' : 'Hai già un account? Accedi' }}</ion-button>
        </section>
      </ion-content>
    </ion-page>

    <ion-page v-else>
      <ion-header translucent>
        <ion-toolbar>
          <ion-title><div class="toolbar-title"><span>PitMetric</span><ion-badge :color="session.cloudEnabled ? 'success' : 'medium'">{{ cloudLabel }}</ion-badge></div></ion-title>
          <ion-button slot="end" fill="clear" :disabled="syncing || !session.cloudEnabled" @click="forceSync"><ion-icon :icon="syncOutline" /></ion-button>
        </ion-toolbar>
      </ion-header>

      <ion-content :fullscreen="true" class="app-content">
        <main v-if="active === 'home'" class="screen">
          <section class="hero">
            <p class="eyebrow">TRACKSIDE</p>
            <h1>Ciao, {{ session.user?.name?.split(' ')[0] }}</h1>
            <p v-if="session.cloudEnabled">Le modifiche vengono salvate prima sul dispositivo e sincronizzate appena torna la rete.</p>
            <p v-else>Il database PitMetric non è attivo per questo account. Puoi continuare a usare l’app: i dati restano su questo dispositivo finché il cloud non viene abilitato.</p>
          </section>

          <div class="status-grid">
            <ion-card><ion-card-content><ion-icon :icon="session.cloudEnabled ? cloudDoneOutline : cloudOfflineOutline" /><strong>{{ cloudLabel }}</strong><span>{{ pending }} in attesa</span></ion-card-content></ion-card>
            <ion-card><ion-card-content><ion-icon :icon="imagesOutline" /><strong>{{ gallery.length }} foto</strong><span>Galleria locale</span></ion-card-content></ion-card>
          </div>

          <ion-card class="action-card" button @click="active = 'gallery'"><ion-card-header><ion-card-title>Galleria paddock</ion-card-title></ion-card-header><ion-card-content>Aggiungi foto, titolo e descrizione anche senza connessione.</ion-card-content></ion-card>
        </main>

        <main v-else-if="active === 'gallery'" class="screen">
          <div class="section-head"><div><p class="eyebrow">MEDIA</p><h1>Galleria</h1></div><ion-badge>{{ pending }} pending</ion-badge></div>
          <ion-card class="composer">
            <ion-card-content>
              <ion-input v-model="photoTitle" label="Titolo" label-placement="stacked" placeholder="Es. Setup pre-qualifica" />
              <ion-textarea v-model="photoDescription" label="Descrizione" label-placement="stacked" :auto-grow="true" placeholder="Note sulla foto…" />
              <ion-button expand="block" @click="addPhoto"><ion-icon slot="start" :icon="cameraOutline" />Scatta o scegli foto</ion-button>
            </ion-card-content>
          </ion-card>

          <div v-if="gallery.length" class="gallery-grid">
            <article v-for="item in gallery" :key="item.local_id" class="photo-card">
              <img v-if="item.preview_uri || item.local_uri.startsWith('http')" :src="item.preview_uri || item.local_uri" :alt="item.title" />
              <div class="photo-placeholder" v-else><ion-icon :icon="imagesOutline" /></div>
              <div class="photo-body"><div class="photo-title"><strong>{{ item.title }}</strong><ion-badge :color="item.sync_state === 'synced' ? 'success' : item.sync_state === 'error' ? 'danger' : 'warning'">{{ item.sync_state }}</ion-badge></div><p>{{ item.description }}</p></div>
            </article>
          </div>
          <div v-else class="empty">Nessuna foto ancora. La prima può essere aggiunta anche completamente offline.</div>
        </main>

        <main v-else class="screen">
          <p class="eyebrow">ACCOUNT</p><h1>{{ session.user?.name }}</h1><p class="muted">{{ session.user?.email }}</p>
          <ion-card><ion-card-content><strong>Sincronizzazione</strong><p>{{ session.cloudEnabled ? 'Database cloud abilitato. Tutte le operazioni offline vengono accodate e sincronizzate automaticamente.' : 'Database cloud non abilitato. I dati vengono mantenuti localmente sul dispositivo.' }}</p></ion-card-content></ion-card>
          <ion-button expand="block" fill="outline" color="danger" @click="signOut"><ion-icon slot="start" :icon="logOutOutline" />Esci</ion-button>
        </main>
      </ion-content>

      <ion-tab-bar slot="bottom" class="bottom-nav">
        <ion-tab-button :selected="active === 'home'" @click="active = 'home'"><ion-icon :icon="speedometerOutline" /><ion-label>Home</ion-label></ion-tab-button>
        <ion-tab-button :selected="active === 'gallery'" @click="active = 'gallery'"><ion-icon :icon="imagesOutline" /><ion-label>Galleria</ion-label></ion-tab-button>
        <ion-tab-button :selected="active === 'account'" @click="active = 'account'"><ion-icon :icon="fingerPrintOutline" /><ion-label>Account</ion-label></ion-tab-button>
      </ion-tab-bar>
    </ion-page>
  </ion-app>
</template>
