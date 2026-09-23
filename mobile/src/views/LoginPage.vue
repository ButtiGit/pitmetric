<template>
  <ion-page>
    <ion-content :fullscreen="true">
      <div class="pm-login">
        <div class="pm-login-card">
          <img class="pm-logo-large" src="/pitmetric-mark.svg" alt="PitMetric" />
          <div class="pm-eyebrow">PitMetric Mobile</div>
          <h1 class="pm-title">{{ registerMode ? 'Crea il tuo account' : 'Bentornato ai box' }}</h1>
          <p class="pm-muted">Trackside-first, offline-first. I dati restano disponibili anche senza campo.</p>

          <div class="pm-card" style="margin-top:22px">
            <ion-item v-if="registerMode"><ion-input v-model="name" label="Nome" label-placement="stacked" autocomplete="name" /></ion-item>
            <ion-item><ion-input v-model="email" label="Email" label-placement="stacked" type="email" autocomplete="email" /></ion-item>
            <ion-item><ion-input v-model="password" label="Password" label-placement="stacked" type="password" :autocomplete="registerMode ? 'new-password' : 'current-password'" /></ion-item>
            <ion-note v-if="error" color="danger">{{ error }}</ion-note>
            <ion-button expand="block" size="large" style="margin-top:16px" :disabled="busy" @click="submit">
              {{ busy ? 'Accesso…' : (registerMode ? 'Registrati' : 'Accedi') }}
            </ion-button>
            <ion-button fill="clear" expand="block" @click="registerMode = !registerMode">
              {{ registerMode ? 'Ho già un account' : 'Non hai un account? Registrati' }}
            </ion-button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { IonButton, IonContent, IonInput, IonItem, IonNote, IonPage } from '@ionic/vue';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { login, register } from '../services/api';
import { saveSession } from '../services/auth';
import { refreshBootstrap, syncNow } from '../services/sync';

const router = useRouter();
const registerMode = ref(false);
const name = ref('');
const email = ref('');
const password = ref('');
const busy = ref(false);
const error = ref('');

async function submit() {
  if (!email.value || !password.value || (registerMode.value && !name.value)) return;
  busy.value = true;
  error.value = '';
  try {
    const session = registerMode.value
      ? await register(name.value, email.value, password.value)
      : await login(email.value, password.value);
    await saveSession(session);
    await refreshBootstrap();
    void syncNow();
    await router.replace('/app/home');
  } catch (reason) {
    error.value = reason instanceof Error ? reason.message : 'Accesso non riuscito.';
  } finally {
    busy.value = false;
  }
}
</script>
