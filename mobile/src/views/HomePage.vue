<template>
  <ion-page>
    <ion-header translucent><ion-toolbar><ion-title><span class="pm-brand"><img src="/pitmetric-mark.svg" alt=""/>PitMetric</span></ion-title><ion-buttons slot="end"><ion-button @click="syncNow"><ion-icon :icon="syncOutline"/></ion-button></ion-buttons></ion-toolbar></ion-header>
    <ion-content :fullscreen="true">
      <div class="pm-page">
        <div class="pm-eyebrow">Mobile cockpit</div>
        <h1 class="pm-title">Ciao {{ firstName }}</h1>
        <p class="pm-muted">{{ workspaceName }}</p>
        <CloudStatus style="margin-top:16px" />

        <div class="pm-section-title">Azioni rapide</div>
        <div class="pm-grid">
          <button class="pm-action" @click="go('/app/pit')"><ion-icon :icon="flashOutline"/><div><strong>Pit Mode</strong><div class="pm-muted">Giri, note, pressioni</div></div></button>
          <button class="pm-action" @click="go('/app/gallery')"><ion-icon :icon="cameraOutline"/><div><strong>Foto</strong><div class="pm-muted">Galleria tecnica</div></div></button>
          <button class="pm-action" @click="syncNow"><ion-icon :icon="cloudUploadOutline"/><div><strong>Sync</strong><div class="pm-muted">{{ appState.pendingCount }} in coda</div></div></button>
          <button class="pm-action" @click="go('/app/settings')"><ion-icon :icon="fingerPrintOutline"/><div><strong>Sicurezza</strong><div class="pm-muted">Viso / impronta</div></div></button>
        </div>

        <div class="pm-section-title">Stato</div>
        <div class="pm-card">
          <div style="display:flex;justify-content:space-between;gap:16px"><span class="pm-muted">Connessione</span><strong>{{ appState.online ? 'Online' : 'Offline' }}</strong></div>
          <div style="display:flex;justify-content:space-between;gap:16px;margin-top:10px"><span class="pm-muted">Dati da sincronizzare</span><strong>{{ appState.pendingCount }}</strong></div>
          <div style="display:flex;justify-content:space-between;gap:16px;margin-top:10px"><span class="pm-muted">Cloud DB</span><strong>{{ appState.session?.user.cloud_enabled ? 'Attivo' : 'Non attivo' }}</strong></div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonButton, IonButtons, IonContent, IonHeader, IonIcon, IonPage, IonTitle, IonToolbar } from '@ionic/vue';
import { cameraOutline, cloudUploadOutline, fingerPrintOutline, flashOutline, syncOutline } from 'ionicons/icons';
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import CloudStatus from '../components/CloudStatus.vue';
import { appState } from '../state';
import { refreshBootstrap, refreshPendingCount, syncNow } from '../services/sync';

const router = useRouter();
const firstName = computed(() => appState.session?.user.name.split(' ')[0] || 'pilota');
const workspaceName = computed(() => appState.bootstrap?.workspace?.name || 'Dati salvati sul dispositivo');
const go = (path: string) => router.push(path);
onMounted(() => { void refreshPendingCount(); void refreshBootstrap(); });
</script>
