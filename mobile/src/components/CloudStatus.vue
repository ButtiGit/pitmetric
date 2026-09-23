<template>
  <div class="pm-status" :class="cloudActive ? 'online' : 'local'">
    <span class="pm-dot" />
    <div>
      <strong>{{ cloudActive ? 'Cloud DB attivo' : 'Modalità solo dispositivo' }}</strong>
      <div class="pm-muted" style="font-size:12px;margin-top:2px">
        <template v-if="cloudActive">{{ appState.online ? 'Sincronizzazione automatica attiva.' : 'Offline: salviamo tutto qui e sincronizziamo appena torna la rete.' }}</template>
        <template v-else-if="reason === 'email_verification_required'">Verifica l’email: fino ad allora i dati restano sul telefono.</template>
        <template v-else>Il tuo account non ha ancora la versione DB attiva. Nessun dato viene perso: resta salvato sul dispositivo.</template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { appState } from '../state';
const cloudActive = computed(() => Boolean(appState.session?.user.cloud_enabled));
const reason = computed(() => appState.session?.user.cloud_reason);
</script>
