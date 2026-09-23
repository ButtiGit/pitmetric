<template>
  <ion-page>
    <ion-header translucent><ion-toolbar><ion-title>Altro</ion-title></ion-toolbar></ion-header>
    <ion-content :fullscreen="true">
      <div class="pm-page">
        <div class="pm-card">
          <div class="pm-brand"><img src="/pitmetric-mark.svg" alt=""/><div><strong>{{ appState.session?.user.name }}</strong><div class="pm-muted" style="font-size:12px">{{ appState.session?.user.email }}</div></div></div>
        </div>
        <div class="pm-section-title">Sicurezza</div>
        <div class="pm-card">
          <ion-item lines="none"><ion-icon slot="start" :icon="fingerPrintOutline"/><ion-toggle :checked="appState.biometricEnabled" @ion-change="toggleBiometric">Login con viso / impronta</ion-toggle></ion-item>
          <ion-note v-if="biometricMessage" :color="biometricError ? 'danger' : 'success'">{{ biometricMessage }}</ion-note>
        </div>
        <div class="pm-section-title">Sincronizzazione</div>
        <div class="pm-card">
          <CloudStatus />
          <div style="display:flex;justify-content:space-between;margin-top:14px"><span class="pm-muted">Elementi in coda</span><strong>{{ appState.pendingCount }}</strong></div>
          <ion-button expand="block" fill="outline" style="margin-top:14px" :disabled="appState.syncing" @click="syncNow">{{ appState.syncing ? 'Sincronizzazione…' : 'Sincronizza ora' }}</ion-button>
        </div>
        <div class="pm-section-title">Account</div>
        <ion-button expand="block" color="danger" fill="outline" @click="logout">Esci</ion-button>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { Capacitor } from '@capacitor/core';
import { IonButton, IonContent, IonHeader, IonIcon, IonItem, IonNote, IonPage, IonTitle, IonToggle, IonToolbar } from '@ionic/vue';
import { fingerPrintOutline } from 'ionicons/icons';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import CloudStatus from '../components/CloudStatus.vue';
import { logoutRemote } from '../services/api';
import { clearSession, disableBiometric, enableBiometric } from '../services/auth';
import { syncNow } from '../services/sync';
import { appState } from '../state';

const router = useRouter(); const biometricMessage = ref(''); const biometricError = ref(false);
async function toggleBiometric(event: CustomEvent) {
  const enabled = Boolean(event.detail.checked); biometricMessage.value = ''; biometricError.value = false;
  if (!Capacitor.isNativePlatform()) { biometricMessage.value = 'La biometria reale è disponibile nell’app Android/iOS.'; biometricError.value = true; return; }
  try { if (enabled) await enableBiometric(); else await disableBiometric(); biometricMessage.value = enabled ? 'Login biometrico attivato.' : 'Login biometrico disattivato.'; }
  catch (error) { biometricMessage.value = error instanceof Error ? error.message : 'Impossibile modificare la biometria.'; biometricError.value = true; }
}
async function logout() { await logoutRemote(); await clearSession(); await router.replace('/login'); }
</script>
