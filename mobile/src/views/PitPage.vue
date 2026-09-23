<template>
  <ion-page>
    <ion-header translucent><ion-toolbar><ion-title>Pit Mode</ion-title></ion-toolbar></ion-header>
    <ion-content :fullscreen="true">
      <div class="pm-page">
        <CloudStatus />
        <div class="pm-section-title">Contesto</div>
        <div class="pm-card">
          <ion-item><ion-input v-model="circuit" label="Circuito" label-placement="stacked" placeholder="Es. Monza" /></ion-item>
          <ion-item><ion-input v-model="driver" label="Pilota" label-placement="stacked" /></ion-item>
          <ion-item><ion-input v-model="vehicle" label="Veicolo" label-placement="stacked" /></ion-item>
        </div>

        <div class="pm-section-title">Cattura</div>
        <ion-segment v-model="mode" scrollable>
          <ion-segment-button value="note"><ion-label>Nota</ion-label></ion-segment-button>
          <ion-segment-button value="issue"><ion-label>Problema</ion-label></ion-segment-button>
          <ion-segment-button value="lap"><ion-label>Giro</ion-label></ion-segment-button>
          <ion-segment-button value="pressure"><ion-label>Pressioni</ion-label></ion-segment-button>
          <ion-segment-button value="component"><ion-label>Componente</ion-label></ion-segment-button>
        </ion-segment>

        <div class="pm-card" style="margin-top:12px">
          <template v-if="mode === 'note'">
            <ion-item><ion-textarea v-model="note" label="Nota tecnica" label-placement="stacked" auto-grow placeholder="Cosa è successo?" /></ion-item>
          </template>
          <template v-else-if="mode === 'issue'">
            <ion-item><ion-select v-model="severity" label="Severità" label-placement="stacked"><ion-select-option value="info">Info</ion-select-option><ion-select-option value="warning">Attenzione</ion-select-option><ion-select-option value="critical">Critico</ion-select-option></ion-select></ion-item>
            <ion-item><ion-textarea v-model="note" label="Problema" label-placement="stacked" auto-grow /></ion-item>
          </template>
          <template v-else-if="mode === 'lap'">
            <ion-item><ion-input v-model="lapTime" label="Tempo giro" label-placement="stacked" placeholder="1:42.315" inputmode="decimal" /></ion-item>
            <ion-item><ion-textarea v-model="note" label="Nota opzionale" label-placement="stacked" auto-grow /></ion-item>
          </template>
          <template v-else-if="mode === 'pressure'">
            <div class="pm-grid">
              <ion-item><ion-input v-model="fl" label="FL" label-placement="stacked" inputmode="decimal" /></ion-item>
              <ion-item><ion-input v-model="fr" label="FR" label-placement="stacked" inputmode="decimal" /></ion-item>
              <ion-item><ion-input v-model="rl" label="RL" label-placement="stacked" inputmode="decimal" /></ion-item>
              <ion-item><ion-input v-model="rr" label="RR" label-placement="stacked" inputmode="decimal" /></ion-item>
            </div>
            <ion-item><ion-select v-model="unit" label="Unità" label-placement="stacked"><ion-select-option value="bar">bar</ion-select-option><ion-select-option value="psi">psi</ion-select-option><ion-select-option value="kpa">kPa</ion-select-option></ion-select></ion-item>
          </template>
          <template v-else>
            <ion-item><ion-input v-model="removedComponent" label="Componente rimosso" label-placement="stacked" /></ion-item>
            <ion-item><ion-input v-model="installedComponent" label="Componente installato" label-placement="stacked" /></ion-item>
            <ion-item><ion-textarea v-model="note" label="Nota" label-placement="stacked" auto-grow /></ion-item>
          </template>

          <ion-note v-if="message" color="success">{{ message }}</ion-note>
          <ion-note v-if="error" color="danger">{{ error }}</ion-note>
          <ion-button expand="block" size="large" style="margin-top:14px" @click="save">Salva subito</ion-button>
          <p class="pm-muted" style="font-size:12px;margin-bottom:0">Il salvataggio è locale e immediato; la rete non è necessaria.</p>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { Haptics, ImpactStyle } from '@capacitor/haptics';
import { IonButton, IonContent, IonHeader, IonInput, IonItem, IonLabel, IonNote, IonPage, IonSegment, IonSegmentButton, IonSelect, IonSelectOption, IonTextarea, IonTitle, IonToolbar } from '@ionic/vue';
import { ref } from 'vue';
import CloudStatus from '../components/CloudStatus.vue';
import { queueCapture } from '../services/sync';
import type { CapturePayload } from '../types';

const mode = ref<'note'|'issue'|'lap'|'pressure'|'component'>('note');
const circuit = ref(''); const driver = ref(''); const vehicle = ref(''); const note = ref('');
const severity = ref('warning'); const lapTime = ref('');
const fl = ref(''); const fr = ref(''); const rl = ref(''); const rr = ref(''); const unit = ref('bar');
const removedComponent = ref(''); const installedComponent = ref('');
const message = ref(''); const error = ref('');

function parseLap(value: string): number | null {
  const match = value.trim().match(/^(?:(\d+):)?(\d{1,2})(?:[.,](\d{1,3}))?$/);
  if (!match) return null;
  const minutes = Number(match[1] || 0); const seconds = Number(match[2]); const milliseconds = Number((match[3] || '0').padEnd(3, '0'));
  return minutes * 60000 + seconds * 1000 + milliseconds;
}

async function save() {
  error.value = ''; message.value = '';
  const context = { driver_name: driver.value || null, vehicle_name: vehicle.value || null, configuration_name: null, technical_setup_name: null };
  const base = { circuit_name: circuit.value || null, occurred_at: new Date().toISOString(), context };
  let payload: CapturePayload;

  if (mode.value === 'note') {
    if (!note.value.trim()) return void (error.value = 'Scrivi una nota.');
    payload = { ...base, kind: 'note', notes: note.value.trim() };
  } else if (mode.value === 'issue') {
    if (!note.value.trim()) return void (error.value = 'Descrivi il problema.');
    payload = { ...base, kind: 'issue', notes: note.value.trim(), payload: { severity: severity.value } };
  } else if (mode.value === 'lap') {
    const ms = parseLap(lapTime.value);
    if (!ms) return void (error.value = 'Tempo giro non valido. Usa es. 1:42.315');
    payload = { ...base, kind: 'lap', lap_time_ms: ms, notes: note.value.trim() || null };
  } else if (mode.value === 'pressure') {
    const values = [fl.value, fr.value, rl.value, rr.value].map(Number);
    if (values.some((value) => !Number.isFinite(value) || value <= 0)) return void (error.value = 'Inserisci tutte e quattro le pressioni.');
    payload = { ...base, kind: 'tyre_pressure', payload: { unit: unit.value, fl: values[0], fr: values[1], rl: values[2], rr: values[3] } };
  } else {
    if (!removedComponent.value.trim() && !installedComponent.value.trim()) return void (error.value = 'Indica almeno un componente.');
    payload = { ...base, kind: 'component_change', notes: note.value.trim() || null, payload: { removed_component: removedComponent.value || null, installed_component: installedComponent.value || null }, context: { ...context, component_names: [removedComponent.value, installedComponent.value].filter(Boolean).join(', ') || null } };
  }

  await queueCapture(payload);
  await Haptics.impact({ style: ImpactStyle.Light }).catch(() => undefined);
  message.value = 'Salvato sul dispositivo.';
  note.value = ''; lapTime.value = ''; removedComponent.value = ''; installedComponent.value = '';
}
</script>
