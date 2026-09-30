<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
  IonApp, IonPage, IonContent, IonButton, IonInput, IonTextarea, IonIcon, IonBadge,
  IonSelect, IonSelectOption, IonLabel, IonSpinner, alertController
} from '@ionic/vue';
import {
  addOutline, analyticsOutline, buildOutline, cameraOutline, carSportOutline,
  checkmarkCircleOutline, chevronForwardOutline, cloudDoneOutline, cloudOfflineOutline,
  ellipsisHorizontalOutline, flagOutline, homeOutline, imagesOutline, logOutOutline,
  pauseOutline, personCircleOutline, playOutline, settingsOutline, speedometerOutline,
  stopwatchOutline, syncOutline, timeOutline, trashOutline, videocamOutline
} from 'ionicons/icons';
import {
  addGalleryMedia, addGalleryPhoto, biometricAvailable, biometricUnlock, bootLocalStore,
  cachedSnapshot, galleryItems, login, logout, pendingCount, pendingOperations, queueOperation,
  refreshSnapshotFromCloud, register, restoreSession, startAutoSync, syncNow,
  type EventItem, type GalleryItem, type MobileSnapshot, type PendingOperation,
  type SessionState
} from './lib';
import {
  addQuickRecord, deleteQuickRecord, formatLapTime, parseLapTime, quickRecords,
  type QuickRecord
} from './quicklog';

type Screen = 'home' | 'timer' | 'sessions' | 'garage' | 'more' | 'pit' | 'weekend' | 'maintenance' | 'setup' | 'gallery' | 'account';

const ready = ref(false);
const session = ref<SessionState | null>(null);
const active = ref<Screen>('home');
const actionMessage = ref('');
const syncing = ref(false);
const biometric = ref(false);
let stopAutoSync: (() => Promise<void>) | null = null;

const authMode = ref<'login' | 'register'>('login');
const name = ref('');
const email = ref('');
const password = ref('');
const authError = ref('');

const gallery = ref<GalleryItem[]>([]);
const quick = ref<QuickRecord[]>([]);
const pendingOps = ref<PendingOperation[]>([]);
const pending = ref(0);
const snapshot = ref<MobileSnapshot>({ events: [], vehicles: [], sessions: [], configurations: [], maintenance_schedules: [], work_orders: [], setups: [], setup_fields: {} });

const timerRunning = ref(false);
const timerElapsed = ref(0);
let timerStartedAt = 0;
let timerInterval: ReturnType<typeof setInterval> | null = null;
const timingLabel = ref('');
const timingVehicle = ref('');
const timingNotes = ref('');
const manualLap = ref('');

const sessionConfigurationId = ref<number | null>(null);
const sessionEventId = ref<number | null>(null);
const sessionEntryId = ref<number | null>(null);
const sessionType = ref('practice');
const localNow = new Date();
const sessionStartedAt = ref(new Date(localNow.getTime() - localNow.getTimezoneOffset() * 60_000).toISOString().slice(0, 16));
const sessionLaps = ref<number | null>(null);
const sessionDuration = ref<number | null>(null);
const sessionBestLap = ref('');
const sessionNotes = ref('');

const selectedEventId = ref<number | null>(null);
const noteBody = ref('');
const noteKind = ref('technical');

const vehicleName = ref('');
const vehicleCategory = ref('kart');
const vehicleManufacturer = ref('');
const vehicleModel = ref('');
const vehicleIdentifier = ref('');

const workOrderTitle = ref('');
const workOrderPriority = ref('normal');
const workOrderScheduleId = ref<number | null>(null);
const workOrderNotes = ref('');

const setupVehicleId = ref<number | null>(null);
const setupName = ref('');
const setupDescription = ref('');
const setupTyreFl = ref<number | null>(null);
const setupTyreFr = ref<number | null>(null);
const setupTyreRl = ref<number | null>(null);
const setupTyreRr = ref<number | null>(null);
const setupRideFront = ref<number | null>(null);
const setupRideRear = ref<number | null>(null);
const setupBrakeBias = ref<number | null>(null);

const mediaTitle = ref('');
const mediaDescription = ref('');

const lapRecords = computed(() => quick.value.filter(item => item.kind === 'lap_time' && item.value_ms != null));
const sessionRecords = computed(() => quick.value.filter(item => item.kind === 'session'));
const recentQuick = computed(() => quick.value.slice(0, 8));
const firstName = computed(() => session.value?.user?.name?.split(' ')[0] || 'pilota');
const cloudLabel = computed(() => session.value?.cloudEnabled ? 'Cloud attivo' : 'Locale');
const currentEvent = computed<EventItem | null>(() => snapshot.value.events.find(event => event.id === selectedEventId.value) || null);
const selectedSessionEvent = computed<EventItem | null>(() => snapshot.value.events.find(event => event.id === sessionEventId.value) || null);
const moreSelected = computed(() => !['home', 'timer', 'sessions', 'garage'].includes(active.value));

const bestLap = computed(() => {
  const values = lapRecords.value.map(item => Number(item.value_ms));
  return values.length ? Math.min(...values) : null;
});
const lastLap = computed(() => lapRecords.value[0]?.value_ms ?? null);
const averageLap = computed(() => {
  const values = lapRecords.value.map(item => Number(item.value_ms));
  return values.length ? Math.round(values.reduce((sum, value) => sum + value, 0) / values.length) : null;
});
const lastDelta = computed(() => lastLap.value != null && bestLap.value != null ? Number(lastLap.value) - Number(bestLap.value) : null);
const improvement = computed(() => {
  const values = [...lapRecords.value].reverse().map(item => Number(item.value_ms));
  if (values.length < 2) return null;
  return values[values.length - 1] - values[0];
});
const chartLaps = computed(() => [...lapRecords.value].slice(0, 12).reverse());
const chartPoints = computed(() => {
  const values = chartLaps.value.map(item => Number(item.value_ms));
  if (!values.length) return '';
  const min = Math.min(...values);
  const max = Math.max(...values);
  const span = Math.max(1, max - min);
  return values.map((value, index) => {
    const x = values.length === 1 ? 50 : (index / (values.length - 1)) * 100;
    const y = 84 - ((value - min) / span) * 64;
    return `${x.toFixed(2)},${y.toFixed(2)}`;
  }).join(' ');
});
const chartAreaPoints = computed(() => chartPoints.value ? `0,100 ${chartPoints.value} 100,100` : '');

function formatDate(value?: string | null): string {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('it-IT', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }).format(date);
}

function deltaLabel(value: number | null): string {
  if (value === null) return '—';
  if (Math.abs(value) < 1) return '+0.000';
  return `${value >= 0 ? '+' : '−'}${(Math.abs(value) / 1000).toFixed(3)}`;
}

function quickMeta(item: QuickRecord): string {
  if (item.kind === 'lap_time') return `${formatLapTime(item.value_ms)} · ${formatDate(item.occurred_at)}`;
  if (item.kind === 'session') return `${String(item.payload.session_type || 'sessione')} · ${formatDate(item.occurred_at)}`;
  return formatDate(item.occurred_at);
}

async function refreshLocal(preferCloud = false) {
  [gallery.value, quick.value, pending.value, pendingOps.value, snapshot.value] = await Promise.all([
    galleryItems(), quickRecords(), pendingCount(), pendingOperations(), cachedSnapshot()
  ]);
  if (preferCloud && session.value?.cloudEnabled) snapshot.value = await refreshSnapshotFromCloud();
}

async function submitAuth() {
  authError.value = '';
  try {
    session.value = authMode.value === 'login'
      ? await login(email.value, password.value)
      : await register(name.value, email.value, password.value);
    await syncNow();
    await refreshLocal(true);
    actionMessage.value = 'Account collegato. I dati locali restano disponibili.';
  } catch (error) {
    authError.value = error instanceof Error ? error.message : 'Accesso non riuscito.';
  }
}

async function unlock() {
  try {
    const restored = await biometricUnlock();
    if (restored) {
      session.value = restored;
      await refreshLocal(true);
    }
  } catch {
    authError.value = 'Sblocco biometrico annullato o non riuscito.';
  }
}

function startTimer() {
  if (timerRunning.value) return;
  timerStartedAt = Date.now() - timerElapsed.value;
  timerRunning.value = true;
  timerInterval = setInterval(() => { timerElapsed.value = Date.now() - timerStartedAt; }, 31);
}
function pauseTimer() {
  timerRunning.value = false;
  if (timerInterval) clearInterval(timerInterval);
  timerInterval = null;
}
function resetTimer() { pauseTimer(); timerElapsed.value = 0; }

async function storeLap(value: number, source: 'stopwatch' | 'manual') {
  await addQuickRecord('lap_time', timingLabel.value || 'Tempo sul giro', {
    vehicle: timingVehicle.value || null,
    notes: timingNotes.value || null,
    source,
  }, value);
  quick.value = await quickRecords();
  actionMessage.value = `Tempo salvato: ${formatLapTime(value)}. Nessuna configurazione necessaria.`;
}

async function saveTimedLap(continueTiming = false) {
  if (timerElapsed.value < 100) return;
  const value = timerElapsed.value;
  await storeLap(value, 'stopwatch');
  if (continueTiming) {
    timerStartedAt = Date.now();
    timerElapsed.value = 0;
  } else resetTimer();
}

async function saveManualLap() {
  const value = parseLapTime(manualLap.value);
  if (value === null) {
    actionMessage.value = 'Inserisci un tempo come 1:23.456 oppure 83.456.';
    return;
  }
  await storeLap(value, 'manual');
  manualLap.value = '';
}

async function saveSession() {
  const startedAt = new Date(sessionStartedAt.value);
  if (!sessionStartedAt.value || Number.isNaN(startedAt.getTime())) {
    actionMessage.value = 'Inserisci una data e un orario validi per la sessione.';
    return;
  }
  const bestLapMs = parseLapTime(sessionBestLap.value);
  if (sessionBestLap.value.trim() && bestLapMs === null) {
    actionMessage.value = 'Inserisci un tempo valido, ad esempio 1:23.456.';
    return;
  }
  const common = {
    session_type: sessionType.value,
    started_at: startedAt.toISOString(),
    completed_laps: sessionLaps.value,
    duration_minutes: sessionDuration.value,
    best_lap_ms: bestLapMs,
    notes: sessionNotes.value || null,
    event_id: sessionEventId.value,
    event_entry_id: sessionEntryId.value,
    configuration_version_id: sessionConfigurationId.value,
  };

  await addQuickRecord('session', 'Sessione', common, bestLapMs, common.started_at);
  if (bestLapMs !== null) {
    await addQuickRecord('lap_time', 'Best lap sessione', { source: 'session', notes: sessionNotes.value || null }, bestLapMs, common.started_at);
  }

  if (sessionConfigurationId.value) {
    const payload: Record<string, unknown> = {
      configuration_version_id: sessionConfigurationId.value,
      session_type: sessionType.value,
      started_at: common.started_at,
      completed_laps: sessionLaps.value,
      duration_minutes: sessionDuration.value,
      notes: sessionNotes.value || null,
    };
    if (sessionEventId.value) payload.event_id = sessionEventId.value;
    if (sessionEntryId.value) payload.event_entry_id = sessionEntryId.value;
    await queueOperation('session.create', payload);
  }

  quick.value = await quickRecords();
  pending.value = await pendingCount();
  sessionLaps.value = null;
  sessionDuration.value = null;
  sessionBestLap.value = '';
  sessionNotes.value = '';
  actionMessage.value = 'Sessione salvata sul dispositivo. Configurazione, weekend e account sono opzionali.';
}

async function savePitNote() {
  if (!noteBody.value.trim()) return;
  await addQuickRecord('note', noteBody.value.trim().slice(0, 52), {
    kind: noteKind.value,
    body: noteBody.value.trim(),
    event_id: selectedEventId.value,
  });
  quick.value = await quickRecords();
  noteBody.value = '';
  actionMessage.value = 'Nota salvata localmente.';
}

async function saveVehicle() {
  if (!vehicleName.value.trim()) return;
  await queueOperation('vehicle.create', {
    name: vehicleName.value.trim(), category: vehicleCategory.value,
    manufacturer: vehicleManufacturer.value || null, model: vehicleModel.value || null,
    identifier: vehicleIdentifier.value || null
  });
  pending.value = await pendingCount();
  vehicleName.value = ''; vehicleManufacturer.value = ''; vehicleModel.value = ''; vehicleIdentifier.value = '';
  actionMessage.value = 'Mezzo salvato in coda locale.';
}

async function saveWorkOrder() {
  if (!workOrderTitle.value.trim()) return;
  await addQuickRecord('maintenance', workOrderTitle.value.trim(), {
    maintenance_schedule_id: workOrderScheduleId.value,
    priority: workOrderPriority.value,
    notes: workOrderNotes.value || null,
  });
  quick.value = await quickRecords();
  workOrderTitle.value = ''; workOrderNotes.value = '';
  actionMessage.value = 'Promemoria manutenzione salvato.';
}

async function saveSetup() {
  if (!setupName.value.trim()) return;
  const values: Record<string, number> = {};
  const map: Array<[string, number | null]> = [
    ['tyre_pressure_fl', setupTyreFl.value], ['tyre_pressure_fr', setupTyreFr.value],
    ['tyre_pressure_rl', setupTyreRl.value], ['tyre_pressure_rr', setupTyreRr.value],
    ['ride_height_front_mm', setupRideFront.value], ['ride_height_rear_mm', setupRideRear.value],
    ['brake_bias_pct', setupBrakeBias.value]
  ];
  map.forEach(([key, value]) => { if (value !== null && value !== undefined) values[key] = Number(value); });
  await addQuickRecord('setup', setupName.value.trim(), {
    vehicle_id: setupVehicleId.value,
    description: setupDescription.value || null,
    values,
  });
  quick.value = await quickRecords();
  setupName.value = ''; setupDescription.value = '';
  actionMessage.value = 'Setup salvato localmente.';
}

async function addPhoto() {
  try {
    await addGalleryPhoto(mediaTitle.value.trim() || 'Foto trackside', mediaDescription.value.trim());
    mediaTitle.value = ''; mediaDescription.value = '';
    await refreshLocal();
  } catch (error) {
    actionMessage.value = error instanceof Error ? error.message : 'Fotocamera non disponibile.';
  }
}
async function addMedia() {
  try {
    await addGalleryMedia(mediaTitle.value.trim() || 'Media trackside', mediaDescription.value.trim());
    mediaTitle.value = ''; mediaDescription.value = '';
    await refreshLocal();
  } catch (error) {
    actionMessage.value = error instanceof Error ? error.message : 'File non disponibile.';
  }
}
async function removeQuick(id: string) {
  const alert = await alertController.create({
    header: 'Eliminare la registrazione?',
    message: 'La registrazione locale verrà eliminata. I dati già sincronizzati restano nel cloud.',
    buttons: [
      { text: 'Annulla', role: 'cancel' },
      { text: 'Elimina', role: 'destructive', handler: () => {
        void (async () => {
          try {
            await deleteQuickRecord(id);
            quick.value = await quickRecords();
            actionMessage.value = 'Registrazione eliminata.';
          } catch {
            actionMessage.value = 'Eliminazione non riuscita. Riprova.';
          }
        })();
      } },
    ],
  });
  await alert.present();
}
async function forceSync() {
  if (!session.value?.cloudEnabled) {
    active.value = 'account';
    actionMessage.value = 'Il cloud è opzionale: collega un account solo se vuoi sincronizzare.';
    return;
  }
  if (syncing.value) return;
  syncing.value = true;
  try {
    const result = await syncNow();
    await refreshLocal(true);
    actionMessage.value = result.synced > 0 ? `${result.synced} elementi sincronizzati.` : 'Dati già aggiornati.';
  } catch (error) {
    actionMessage.value = error instanceof Error ? error.message : 'Sincronizzazione non riuscita. Riprova quando sei online.';
  } finally { syncing.value = false; }
}
async function signOut() {
  await logout();
  session.value = null;
  actionMessage.value = 'Account scollegato. I dati locali restano sul dispositivo.';
  active.value = 'home';
}

onMounted(async () => {
  await bootLocalStore();
  session.value = await restoreSession();
  biometric.value = await biometricAvailable().catch(() => false);
  await refreshLocal(Boolean(session.value?.cloudEnabled));
  stopAutoSync = await startAutoSync(() => refreshLocal(false));
  ready.value = true;
});
onUnmounted(async () => { pauseTimer(); await stopAutoSync?.(); });
</script>

<template>
  <ion-app>
    <div v-if="!ready" class="splash"><div class="mark" /><ion-spinner name="crescent" /></div>

    <ion-page v-else>
      <ion-content :fullscreen="true" class="app-content">
        <header class="app-topbar">
          <div class="brand-mini"><div class="mark" /><span class="wordmark">PitMetric</span></div>
          <button class="sync-pill" type="button" :disabled="syncing" :aria-label="syncing ? 'Sincronizzazione in corso' : session?.cloudEnabled ? 'Sincronizza dati' : 'Collega account cloud'" @click="forceSync">
            <ion-icon :icon="session?.cloudEnabled ? cloudDoneOutline : cloudOfflineOutline" />
            <span>{{ syncing ? 'Sincronizzo…' : cloudLabel }}</span>
          </button>
        </header>

        <button v-if="actionMessage" class="toast" role="status" type="button" @click="actionMessage = ''">{{ actionMessage }}</button>

        <main v-if="active === 'home'" class="screen dashboard-screen">
          <section class="dashboard-hero">
            <div>
              <p class="eyebrow">RACE DASHBOARD</p>
              <h1>{{ `Ciao, ${firstName}.` }}</h1>
              <p>Il prossimo giro comincia qui.</p>
            </div>
            <button class="hero-start" type="button" @click="active = 'timer'"><ion-icon :icon="playOutline" />In pista</button>
          </section>

          <section class="stat-grid">
            <article class="stat-card featured"><span>BEST LAP</span><strong>{{ bestLap !== null ? formatLapTime(bestLap) : '—' }}</strong><small>{{ lapRecords.length }} giri registrati</small></article>
            <article class="stat-card"><span>ULTIMO GIRO</span><strong>{{ lastLap !== null ? formatLapTime(lastLap) : '—' }}</strong><small :class="lastDelta !== null && lastDelta <= 0 ? 'positive' : ''">{{ deltaLabel(lastDelta) }} dal best</small></article>
            <article class="stat-card"><span>MEDIA</span><strong>{{ averageLap !== null ? formatLapTime(averageLap) : '—' }}</strong><small>ultimi {{ lapRecords.length }} giri</small></article>
            <article class="stat-card"><span>TREND</span><strong>{{ improvement !== null ? deltaLabel(improvement) : '—' }}</strong><small>{{ improvement !== null && improvement < 0 ? 'più veloce' : 'dal primo giro' }}</small></article>
          </section>

          <section class="panel chart-panel">
            <div class="panel-head"><div><p class="eyebrow">PACE TREND</p><h2>Andamento giri</h2></div><span>{{ chartLaps.length }} lap</span></div>
            <div v-if="chartLaps.length >= 2" class="lap-chart">
              <svg viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-label="Andamento degli ultimi tempi sul giro">
                <defs><linearGradient id="paceFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#E10600" stop-opacity=".28"/><stop offset="100%" stop-color="#E10600" stop-opacity="0"/></linearGradient></defs>
                <line x1="0" y1="25" x2="100" y2="25"/><line x1="0" y1="50" x2="100" y2="50"/><line x1="0" y1="75" x2="100" y2="75"/>
                <polygon :points="chartAreaPoints" fill="url(#paceFill)" stroke="none"/>
                <polyline :points="chartPoints" fill="none" vector-effect="non-scaling-stroke"/>
              </svg>
              <div class="chart-labels"><span>{{ formatLapTime(chartLaps[0]?.value_ms) }}</span><span>{{ formatLapTime(chartLaps[chartLaps.length - 1]?.value_ms) }}</span></div>
            </div>
            <div v-else class="chart-empty">Registra almeno 2 giri: il grafico si costruirà automaticamente.</div>
          </section>

          <section class="quick-grid">
            <button class="quick-card primary" type="button" @click="active = 'timer'"><span class="quick-icon"><ion-icon :icon="stopwatchOutline" /></span><strong>Nuovo tempo</strong><small>Cronometro o manuale</small><ion-icon class="chev" :icon="chevronForwardOutline" /></button>
            <button class="quick-card" type="button" @click="active = 'sessions'"><span class="quick-icon"><ion-icon :icon="flagOutline" /></span><strong>Sessione</strong><small>Registra la tua uscita</small><ion-icon class="chev" :icon="chevronForwardOutline" /></button>
            <button class="quick-card" type="button" @click="active = 'pit'"><span class="quick-icon"><ion-icon :icon="speedometerOutline" /></span><strong>Nota</strong><small>Annota ciò che conta</small><ion-icon class="chev" :icon="chevronForwardOutline" /></button>
            <button class="quick-card" type="button" @click="active = 'garage'"><span class="quick-icon"><ion-icon :icon="carSportOutline" /></span><strong>Garage</strong><small>{{ snapshot.vehicles.length }} mezzi cloud</small><ion-icon class="chev" :icon="chevronForwardOutline" /></button>
          </section>

          <section class="panel activity-panel">
            <div class="panel-head"><div><p class="eyebrow">ACTIVITY</p><h2>Ultime registrazioni</h2></div><span>{{ quick.length }}</span></div>
            <div v-if="recentQuick.length" class="activity-list">
              <article v-for="item in recentQuick" :key="item.id" class="activity-row">
                <div class="activity-dot"><ion-icon :icon="item.kind === 'lap_time' ? stopwatchOutline : item.kind === 'session' ? flagOutline : item.kind === 'setup' ? settingsOutline : timeOutline" /></div>
                <div><strong>{{ item.kind === 'lap_time' ? formatLapTime(item.value_ms) : item.title }}</strong><span>{{ item.kind === 'lap_time' ? item.title : quickMeta(item) }}</span></div>
                <button type="button" class="icon-button" :aria-label="`Elimina ${item.title}`" @click="removeQuick(item.id)"><ion-icon :icon="trashOutline" /></button>
              </article>
            </div>
            <div v-else class="empty-state"><ion-icon :icon="analyticsOutline"/><strong>Nessun dato ancora</strong><span>Il primo tempo apparirà qui e nei grafici.</span></div>
          </section>
        </main>

        <main v-else-if="active === 'timer'" class="screen timer-screen">
          <section class="screen-title"><div><p class="eyebrow">LIVE TIMING</p><h1>Cronometro</h1></div><span class="status-dot"><i/>LOCAL</span></section>
          <section class="timer-panel">
            <span class="timer-kicker">CURRENT LAP</span>
            <strong class="timer-value">{{ formatLapTime(timerElapsed) }}</strong>
            <div class="timer-metrics"><span>BEST <b>{{ bestLap !== null ? formatLapTime(bestLap) : '—' }}</b></span><span>LAST <b>{{ lastLap !== null ? formatLapTime(lastLap) : '—' }}</b></span></div>
            <div class="timer-actions">
              <button v-if="!timerRunning" class="big-action start" type="button" @click="startTimer"><ion-icon :icon="playOutline"/>Avvia</button>
              <button v-else class="big-action lap" type="button" @click="saveTimedLap(true)"><ion-icon :icon="flagOutline"/>Giro</button>
              <button v-if="timerRunning" class="square-action" type="button" @click="pauseTimer"><ion-icon :icon="pauseOutline"/></button>
              <button v-else-if="timerElapsed > 0" class="square-action" type="button" @click="saveTimedLap(false)"><ion-icon :icon="checkmarkCircleOutline"/></button>
            </div>
            <button v-if="timerElapsed > 0" class="text-action" type="button" @click="resetTimer">Azzera</button>
          </section>

          <section class="panel form-panel">
            <div class="panel-head"><div><p class="eyebrow">CONTESTO OPZIONALE</p><h2>Dettagli giro</h2></div></div>
            <div class="form-grid two"><ion-input v-model="timingLabel" label="Pista / etichetta" label-placement="stacked" placeholder="Es. Busca"/><ion-input v-model="timingVehicle" label="Mezzo" label-placement="stacked" placeholder="Opzionale"/></div>
            <ion-textarea v-model="timingNotes" label="Note" label-placement="stacked" :auto-grow="true" placeholder="Gomme, meteo, traffico…"/>
          </section>

          <section class="manual-card">
            <div><span>INSERIMENTO MANUALE</span><strong>Hai già il tempo?</strong></div>
            <div class="manual-row"><ion-input v-model="manualLap" label="Tempo sul giro" label-placement="stacked" inputmode="decimal" placeholder="1:23.456"/><button type="button" @click="saveManualLap">Salva</button></div>
            <small>Nessuna configurazione, vettura o sessione necessaria.</small>
          </section>

          <section class="panel"><div class="panel-head"><div><p class="eyebrow">LAP HISTORY</p><h2>Ultimi giri</h2></div><span>{{ lapRecords.length }}</span></div>
            <div class="lap-list"><article v-for="(item, index) in lapRecords.slice(0, 12)" :key="item.id" class="lap-list-row"><span class="lap-index">{{ String(lapRecords.length - index).padStart(2,'0') }}</span><strong>{{ formatLapTime(item.value_ms) }}</strong><span>{{ item.title }}</span><button type="button" class="icon-button" :aria-label="`Elimina ${item.title}`" @click="removeQuick(item.id)"><ion-icon :icon="trashOutline"/></button></article></div>
          </section>
        </main>

        <main v-else-if="active === 'sessions'" class="screen">
          <section class="screen-title"><div><p class="eyebrow">SESSION LOGGER</p><h1>Nuova sessione</h1><p>Salva prima. Collega configurazione e cloud solo se ti servono.</p></div></section>
          <section class="panel form-panel">
            <div class="form-grid two"><ion-select v-model="sessionType" label="Tipo" label-placement="stacked"><ion-select-option v-for="type in ['practice','qualifying','heat','prefinal','final','race','test']" :key="type" :value="type">{{ type }}</ion-select-option></ion-select><ion-input v-model="sessionStartedAt" type="datetime-local" label="Inizio" label-placement="stacked"/></div>
            <div class="form-grid three"><ion-input v-model.number="sessionLaps" type="number" label="Giri" label-placement="stacked"/><ion-input v-model.number="sessionDuration" type="number" label="Minuti" label-placement="stacked"/><ion-input v-model="sessionBestLap" inputmode="decimal" label="Best lap" label-placement="stacked" placeholder="1:23.456"/></div>
            <ion-textarea v-model="sessionNotes" label="Note" label-placement="stacked" :auto-grow="true"/>
            <details class="advanced"><summary>Collegamenti avanzati opzionali</summary><div class="advanced-body"><ion-select v-model="sessionConfigurationId" label="Configurazione" label-placement="stacked"><ion-select-option :value="null">Nessuna</ion-select-option><ion-select-option v-for="config in snapshot.configurations" :key="config.id" :value="config.id">{{ config.vehicle }} · {{ config.configuration }} v{{ config.version_number }}</ion-select-option></ion-select><ion-select v-model="sessionEventId" label="Weekend" label-placement="stacked" @ion-change="sessionEntryId = null"><ion-select-option :value="null">Nessuno</ion-select-option><ion-select-option v-for="event in snapshot.events" :key="event.id" :value="event.id">{{ event.name }}</ion-select-option></ion-select><ion-select v-if="selectedSessionEvent" v-model="sessionEntryId" label="Iscrizione" label-placement="stacked"><ion-select-option :value="null">Nessuna</ion-select-option><ion-select-option v-for="entry in selectedSessionEvent.entries" :key="entry.id" :value="entry.id">#{{ entry.entry_number || '—' }} · {{ entry.driver || entry.vehicle }}</ion-select-option></ion-select></div></details>
            <button class="save-button" type="button" @click="saveSession"><ion-icon :icon="addOutline"/>Salva sessione</button>
          </section>
          <section class="panel"><div class="panel-head"><div><p class="eyebrow">LOCAL SESSIONS</p><h2>Storico</h2></div><span>{{ sessionRecords.length }}</span></div><div class="activity-list"><article v-for="item in sessionRecords.slice(0,12)" :key="item.id" class="activity-row"><div class="activity-dot"><ion-icon :icon="flagOutline"/></div><div><strong>{{ String(item.payload.session_type || 'Sessione') }}</strong><span>{{ quickMeta(item) }} · {{ item.payload.completed_laps || '—' }} giri</span></div><button type="button" class="icon-button" :aria-label="`Elimina ${item.title}`" @click="removeQuick(item.id)"><ion-icon :icon="trashOutline"/></button></article></div></section>
        </main>

        <main v-else-if="active === 'garage'" class="screen">
          <section class="screen-title"><div><p class="eyebrow">GARAGE</p><h1>Mezzi</h1><p>Puoi registrare tempi anche senza aggiungere un mezzo.</p></div><ion-badge>{{ snapshot.vehicles.length }}</ion-badge></section>
          <div class="vehicle-grid"><article v-for="vehicle in snapshot.vehicles" :key="vehicle.id" class="vehicle-card"><ion-icon :icon="carSportOutline"/><div><strong>{{ vehicle.name }}</strong><span>{{ [vehicle.manufacturer, vehicle.model].filter(Boolean).join(' ') || vehicle.category }}</span></div><small>{{ vehicle.status }}</small></article></div>
          <section class="panel form-panel"><div class="panel-head"><div><p class="eyebrow">NEW VEHICLE</p><h2>Aggiungi mezzo</h2></div></div><ion-input v-model="vehicleName" label="Nome" label-placement="stacked"/><ion-select v-model="vehicleCategory" label="Categoria" label-placement="stacked"><ion-select-option v-for="category in ['kart','formula','gt','touring','rally','prototype','hypercar','road_car','motorcycle','other']" :key="category" :value="category">{{ category }}</ion-select-option></ion-select><div class="form-grid two"><ion-input v-model="vehicleManufacturer" label="Costruttore" label-placement="stacked"/><ion-input v-model="vehicleModel" label="Modello" label-placement="stacked"/></div><ion-input v-model="vehicleIdentifier" label="Identificativo" label-placement="stacked"/><button class="save-button" type="button" @click="saveVehicle">Salva in locale</button></section>
        </main>

        <main v-else-if="active === 'pit'" class="screen">
          <section class="screen-title"><div><p class="eyebrow">QUICK NOTE</p><h1>Nota trackside</h1><p>Non serve un weekend esistente.</p></div></section>
          <section class="panel form-panel"><ion-select v-model="noteKind" label="Tipo" label-placement="stacked"><ion-select-option value="technical">Tecnica</ion-select-option><ion-select-option value="driver">Pilota</ion-select-option><ion-select-option value="strategy">Strategia</ion-select-option><ion-select-option value="weather">Meteo</ion-select-option><ion-select-option value="incident">Incidente</ion-select-option></ion-select><ion-select v-model="selectedEventId" label="Weekend opzionale" label-placement="stacked"><ion-select-option :value="null">Nessuno</ion-select-option><ion-select-option v-for="event in snapshot.events" :key="event.id" :value="event.id">{{ event.name }}</ion-select-option></ion-select><ion-textarea v-model="noteBody" label="Nota" label-placement="stacked" :auto-grow="true" placeholder="Pressioni, comportamento, pista…"/><button class="save-button" type="button" @click="savePitNote">Salva nota</button></section>
        </main>

        <main v-else-if="active === 'weekend'" class="screen"><section class="screen-title"><div><p class="eyebrow">WEEKENDS</p><h1>Eventi sincronizzati</h1></div></section><div class="event-list"><article v-for="event in snapshot.events" :key="event.id" class="event-card"><div><strong>{{ event.name }}</strong><span>{{ event.championship }} {{ event.round_label ? `· ${event.round_label}` : '' }}</span></div><ion-badge>{{ event.status }}</ion-badge></article></div><div v-if="!snapshot.events.length" class="empty-state"><ion-icon :icon="flagOutline"/><strong>Nessun evento</strong><span>Non è un problema: timer e sessioni funzionano comunque.</span></div></main>

        <main v-else-if="active === 'maintenance'" class="screen"><section class="screen-title"><div><p class="eyebrow">MAINTENANCE</p><h1>Promemoria</h1></div></section><section class="panel form-panel"><ion-input v-model="workOrderTitle" label="Cosa va fatto?" label-placement="stacked"/><ion-select v-model="workOrderPriority" label="Priorità" label-placement="stacked"><ion-select-option v-for="priority in ['low','normal','high','critical']" :key="priority" :value="priority">{{ priority }}</ion-select-option></ion-select><ion-select v-model="workOrderScheduleId" label="Piano opzionale" label-placement="stacked"><ion-select-option :value="null">Nessuno</ion-select-option><ion-select-option v-for="item in snapshot.maintenance_schedules" :key="item.id" :value="item.id">{{ item.name }}</ion-select-option></ion-select><ion-textarea v-model="workOrderNotes" label="Note" label-placement="stacked" :auto-grow="true"/><button class="save-button" type="button" @click="saveWorkOrder">Salva promemoria</button></section></main>

        <main v-else-if="active === 'setup'" class="screen"><section class="screen-title"><div><p class="eyebrow">SETUP</p><h1>Setup libero</h1><p>Anche senza mezzo collegato.</p></div></section><section class="panel form-panel"><ion-select v-model="setupVehicleId" label="Mezzo opzionale" label-placement="stacked"><ion-select-option :value="null">Nessuno</ion-select-option><ion-select-option v-for="vehicle in snapshot.vehicles" :key="vehicle.id" :value="vehicle.id">{{ vehicle.name }}</ion-select-option></ion-select><ion-input v-model="setupName" label="Nome setup" label-placement="stacked"/><ion-textarea v-model="setupDescription" label="Descrizione" label-placement="stacked" :auto-grow="true"/><div class="form-grid four"><ion-input v-model.number="setupTyreFl" type="number" label="FL bar" label-placement="stacked"/><ion-input v-model.number="setupTyreFr" type="number" label="FR bar" label-placement="stacked"/><ion-input v-model.number="setupTyreRl" type="number" label="RL bar" label-placement="stacked"/><ion-input v-model.number="setupTyreRr" type="number" label="RR bar" label-placement="stacked"/></div><div class="form-grid two"><ion-input v-model.number="setupRideFront" type="number" label="Altezza ant." label-placement="stacked"/><ion-input v-model.number="setupRideRear" type="number" label="Altezza post." label-placement="stacked"/></div><ion-input v-model.number="setupBrakeBias" type="number" label="Brake bias %" label-placement="stacked"/><button class="save-button" type="button" @click="saveSetup">Salva setup</button></section></main>

        <main v-else-if="active === 'gallery'" class="screen"><section class="screen-title"><div><p class="eyebrow">MEDIA</p><h1>Galleria</h1></div></section><section class="panel form-panel"><ion-input v-model="mediaTitle" label="Titolo opzionale" label-placement="stacked"/><ion-textarea v-model="mediaDescription" label="Descrizione" label-placement="stacked" :auto-grow="true"/><div class="media-actions"><button type="button" @click="addPhoto"><ion-icon :icon="cameraOutline"/>Fotocamera</button><button type="button" @click="addMedia"><ion-icon :icon="videocamOutline"/>Foto / video</button></div></section><div class="gallery-grid"><article v-for="item in gallery" :key="item.local_id" class="media-card"><video v-if="item.media_type === 'video'" :src="item.preview_uri || item.local_uri" controls playsinline preload="metadata"/><img v-else-if="item.preview_uri || item.local_uri.startsWith('http')" :src="item.preview_uri || item.local_uri" :alt="item.title"/><div v-else class="media-placeholder"><ion-icon :icon="imagesOutline"/></div><div><strong>{{ item.title }}</strong><span>{{ item.sync_state }}</span></div></article></div></main>

        <main v-else-if="active === 'account'" class="screen"><section class="screen-title"><div><p class="eyebrow">ACCOUNT & CLOUD</p><h1>{{ session ? session.user?.name : 'Opzionale' }}</h1><p>{{ session ? session.user?.email : 'Usa PitMetric in locale quanto vuoi. Accedi solo per sincronizzare.' }}</p></div></section><section v-if="!session" class="panel auth-panel"><div class="segmented"><button :class="{active: authMode === 'login'}" type="button" @click="authMode='login'">Accedi</button><button :class="{active: authMode === 'register'}" type="button" @click="authMode='register'">Registrati</button></div><ion-input v-if="authMode === 'register'" v-model="name" label="Nome" label-placement="stacked"/><ion-input v-model="email" type="email" label="Email" label-placement="stacked"/><ion-input v-model="password" type="password" label="Password" label-placement="stacked"/><p v-if="authError" class="error">{{ authError }}</p><button class="save-button" type="button" @click="submitAuth">{{ authMode === 'login' ? 'Accedi' : 'Crea account' }}</button><button v-if="biometric && authMode === 'login'" class="secondary-button" type="button" @click="unlock"><ion-icon :icon="personCircleOutline"/>Sblocco biometrico</button></section><section v-else class="panel account-panel"><div class="account-status"><ion-icon :icon="session.cloudEnabled ? cloudDoneOutline : cloudOfflineOutline"/><div><strong>{{ session.cloudEnabled ? 'Cloud attivo' : 'Account collegato' }}</strong><span>{{ pending }} operazioni in coda</span></div></div><button class="secondary-button" type="button" @click="forceSync"><ion-icon :icon="syncOutline"/>Sincronizza ora</button><button class="danger-button" type="button" @click="signOut"><ion-icon :icon="logOutOutline"/>Scollega account</button><div v-if="pendingOps.length" class="queue"><span v-for="item in pendingOps.slice(0,8)" :key="item.local_id">{{ item.operation }} · {{ item.attempts }} retry</span></div></section></main>

        <main v-else class="screen">
          <section class="screen-title"><div><p class="eyebrow">TOOLS</p><h1>Altro</h1></div></section>
          <section class="tools-grid"><button type="button" @click="active='pit'"><ion-icon :icon="speedometerOutline"/><strong>Nota rapida</strong><span>Trackside</span></button><button type="button" @click="active='setup'"><ion-icon :icon="settingsOutline"/><strong>Setup</strong><span>Assetto libero</span></button><button type="button" @click="active='maintenance'"><ion-icon :icon="buildOutline"/><strong>Manutenzione</strong><span>Promemoria</span></button><button type="button" @click="active='gallery'"><ion-icon :icon="imagesOutline"/><strong>Galleria</strong><span>Foto + video</span></button><button type="button" @click="active='weekend'"><ion-icon :icon="flagOutline"/><strong>Weekend</strong><span>Cloud opzionale</span></button><button type="button" @click="active='account'"><ion-icon :icon="personCircleOutline"/><strong>Account</strong><span>{{ session ? 'Collegato' : 'Opzionale' }}</span></button></section>
        </main>
      </ion-content>

      <nav class="bottom-nav" aria-label="Navigazione principale">
        <button type="button" :aria-current="active === 'home' ? 'page' : undefined" @click="active='home'"><ion-icon :icon="homeOutline"/><span>Home</span></button>
        <button type="button" :aria-current="active === 'timer' ? 'page' : undefined" @click="active='timer'"><ion-icon :icon="stopwatchOutline"/><span>Timer</span></button>
        <button type="button" :aria-current="active === 'sessions' ? 'page' : undefined" @click="active='sessions'"><ion-icon :icon="flagOutline"/><span>Sessioni</span></button>
        <button type="button" :aria-current="active === 'garage' ? 'page' : undefined" @click="active='garage'"><ion-icon :icon="carSportOutline"/><span>Garage</span></button>
        <button type="button" :aria-current="moreSelected ? 'page' : undefined" @click="active='more'"><ion-icon :icon="ellipsisHorizontalOutline"/><span>Altro</span></button>
      </nav>
    </ion-page>
  </ion-app>
</template>
