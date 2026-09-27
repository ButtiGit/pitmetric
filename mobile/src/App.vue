<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
  IonApp, IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButton,
  IonInput, IonTextarea, IonIcon, IonBadge, IonTabBar, IonTabButton,
  IonCard, IonCardContent, IonCardHeader, IonCardTitle, IonSpinner,
  IonSelect, IonSelectOption, IonLabel, IonChip
} from '@ionic/vue';
import {
  addOutline, buildOutline, calendarOutline, cameraOutline, carSportOutline,
  cloudDoneOutline, cloudOfflineOutline, ellipsisHorizontalOutline, fingerPrintOutline,
  flagOutline, homeOutline, imagesOutline, logOutOutline, personCircleOutline,
  playOutline, pauseOutline, settingsOutline, speedometerOutline, stopwatchOutline,
  syncOutline, timeOutline, trashOutline, videocamOutline
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

type Screen = 'home' | 'timer' | 'sessions' | 'pit' | 'weekend' | 'garage' | 'more' | 'maintenance' | 'setup' | 'gallery' | 'account';

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

const sessionEventId = ref<number | null>(null);
const sessionEntryId = ref<number | null>(null);
const sessionConfigurationId = ref<number | null>(null);
const sessionType = ref('practice');
const sessionStartedAt = ref(new Date().toISOString().slice(0, 16));
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

const cloudLabel = computed(() => session.value?.cloudEnabled ? 'Cloud attivo' : session.value ? 'Account locale' : 'Solo dispositivo');
const firstName = computed(() => session.value?.user?.name?.split(' ')[0] || 'pilota');
const currentEvent = computed<EventItem | null>(() => snapshot.value.events.find(event => event.id === selectedEventId.value) || snapshot.value.events[0] || null);
const selectedSessionEvent = computed<EventItem | null>(() => snapshot.value.events.find(event => event.id === sessionEventId.value) || null);
const moreSelected = computed(() => !['home', 'timer', 'sessions', 'garage'].includes(active.value));
const recentQuick = computed(() => quick.value.slice(0, 8));
const bestLap = computed(() => {
  const values = quick.value.filter(item => item.kind === 'lap_time' && item.value_ms != null).map(item => Number(item.value_ms));
  return values.length ? Math.min(...values) : null;
});

function formatDate(value?: string | null): string {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('it-IT', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }).format(date);
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
  if (!selectedEventId.value && snapshot.value.events[0]) selectedEventId.value = snapshot.value.events[0].id;
  if (!sessionEventId.value && snapshot.value.events[0]) sessionEventId.value = snapshot.value.events[0].id;
  if (!sessionConfigurationId.value && snapshot.value.configurations[0]) sessionConfigurationId.value = snapshot.value.configurations[0].id;
  if (!setupVehicleId.value && snapshot.value.vehicles[0]) setupVehicleId.value = snapshot.value.vehicles[0].id;
}

async function submitAuth() {
  authError.value = '';
  try {
    session.value = authMode.value === 'login'
      ? await login(email.value, password.value)
      : await register(name.value, email.value, password.value);
    await syncNow();
    await refreshLocal(true);
    actionMessage.value = 'Account collegato. I dati compatibili in coda verranno sincronizzati.';
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

async function queue(operation: string, payload: Record<string, unknown>, message: string) {
  await queueOperation(operation, payload);
  actionMessage.value = message;
  await refreshLocal(false);
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

function resetTimer() {
  pauseTimer();
  timerElapsed.value = 0;
}

async function saveTimedLap(continueTiming = false) {
  if (timerElapsed.value < 100) return;
  const value = timerElapsed.value;
  await addQuickRecord('lap_time', timingLabel.value || 'Tempo sul giro', {
    vehicle: timingVehicle.value || null,
    notes: timingNotes.value || null,
    source: 'stopwatch'
  }, value);
  actionMessage.value = `Tempo salvato: ${formatLapTime(value)}`;
  quick.value = await quickRecords();
  if (continueTiming) {
    timerStartedAt = Date.now();
    timerElapsed.value = 0;
  } else {
    resetTimer();
  }
}

async function saveManualLap() {
  const value = parseLapTime(manualLap.value);
  if (value === null) {
    actionMessage.value = 'Inserisci un tempo come 1:23.456 oppure 83.456.';
    return;
  }
  await addQuickRecord('lap_time', timingLabel.value || 'Tempo manuale', {
    vehicle: timingVehicle.value || null,
    notes: timingNotes.value || null,
    source: 'manual'
  }, value);
  manualLap.value = '';
  quick.value = await quickRecords();
  actionMessage.value = `Tempo salvato: ${formatLapTime(value)}`;
}

async function saveSession() {
  const bestLapMs = parseLapTime(sessionBestLap.value);
  const common = {
    session_type: sessionType.value,
    started_at: new Date(sessionStartedAt.value).toISOString(),
    completed_laps: sessionLaps.value,
    duration_minutes: sessionDuration.value,
    best_lap_ms: bestLapMs,
    notes: sessionNotes.value || null,
  };

  if (sessionConfigurationId.value) {
    const payload: Record<string, unknown> = {
      configuration_version_id: sessionConfigurationId.value,
      ...common,
    };
    if (sessionEventId.value) payload.event_id = sessionEventId.value;
    if (sessionEntryId.value) payload.event_entry_id = sessionEntryId.value;
    await queue('session.create', payload, 'Sessione salvata offline e pronta per la sincronizzazione.');
  } else {
    await addQuickRecord('session', 'Sessione libera', common, bestLapMs, common.started_at);
    quick.value = await quickRecords();
    actionMessage.value = 'Sessione libera salvata. Non serve creare prima weekend, mezzo o configurazione.';
  }

  sessionLaps.value = null;
  sessionDuration.value = null;
  sessionBestLap.value = '';
  sessionNotes.value = '';
}

async function savePitNote() {
  if (!noteBody.value.trim()) return;
  const event = currentEvent.value;
  if (event) {
    await queue('event.note.create', { event_id: event.id, kind: noteKind.value, body: noteBody.value.trim(), occurred_at: new Date().toISOString() }, 'Nota salvata offline.');
  } else {
    await addQuickRecord('note', noteBody.value.trim().slice(0, 56), { kind: noteKind.value, body: noteBody.value.trim() });
    quick.value = await quickRecords();
    actionMessage.value = 'Nota libera salvata sul dispositivo.';
  }
  noteBody.value = '';
}

async function saveVehicle() {
  if (!vehicleName.value.trim()) return;
  await queue('vehicle.create', {
    name: vehicleName.value.trim(), category: vehicleCategory.value,
    manufacturer: vehicleManufacturer.value || null, model: vehicleModel.value || null,
    identifier: vehicleIdentifier.value || null
  }, session.value ? 'Mezzo salvato offline e pronto per il cloud.' : 'Mezzo salvato offline. Collega un account quando vuoi sincronizzarlo.');
  vehicleName.value = ''; vehicleManufacturer.value = ''; vehicleModel.value = ''; vehicleIdentifier.value = '';
}

async function saveWorkOrder() {
  if (!workOrderTitle.value.trim()) return;
  await queue('maintenance.work_order.create', {
    maintenance_schedule_id: workOrderScheduleId.value,
    title: workOrderTitle.value.trim(), priority: workOrderPriority.value,
    notes: workOrderNotes.value || null
  }, 'Intervento salvato offline.');
  workOrderTitle.value = ''; workOrderNotes.value = '';
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

  if (setupVehicleId.value) {
    await queue('setup.create', { vehicle_id: setupVehicleId.value, name: setupName.value.trim(), description: setupDescription.value || null, values }, 'Setup salvato offline.');
  } else {
    await addQuickRecord('setup', setupName.value.trim(), { description: setupDescription.value || null, values });
    quick.value = await quickRecords();
    actionMessage.value = 'Setup libero salvato senza richiedere un mezzo.';
  }
  setupName.value = ''; setupDescription.value = '';
}

async function addPhoto() {
  const title = mediaTitle.value.trim() || 'Foto trackside';
  await addGalleryPhoto(title, mediaDescription.value.trim());
  mediaTitle.value = ''; mediaDescription.value = '';
  await refreshLocal();
}

async function addMedia() {
  try {
    const title = mediaTitle.value.trim() || 'Media trackside';
    await addGalleryMedia(title, mediaDescription.value.trim());
    mediaTitle.value = ''; mediaDescription.value = '';
    await refreshLocal();
  } catch (error) {
    actionMessage.value = error instanceof Error ? error.message : 'File non disponibile.';
  }
}

async function removeQuick(id: string) {
  await deleteQuickRecord(id);
  quick.value = await quickRecords();
}

async function forceSync() {
  if (!session.value?.cloudEnabled) {
    active.value = 'account';
    actionMessage.value = session.value ? 'Il tuo account non ha il cloud attivo.' : 'Collega un account solo se vuoi sincronizzare i dati.';
    return;
  }
  syncing.value = true;
  try {
    const result = await syncNow();
    await refreshLocal(true);
    actionMessage.value = result.synced > 0 ? `${result.synced} elementi sincronizzati.` : 'Dati già aggiornati.';
  } finally {
    syncing.value = false;
  }
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

onUnmounted(async () => {
  pauseTimer();
  await stopAutoSync?.();
});
</script>

<template>
  <ion-app>
    <div v-if="!ready" class="splash"><div class="mark">PM</div><ion-spinner name="crescent" /></div>

    <ion-page v-else>
      <ion-header translucent>
        <ion-toolbar>
          <ion-title><div class="toolbar-title"><span>PitMetric</span><ion-badge :color="session?.cloudEnabled ? 'success' : 'medium'">{{ cloudLabel }}</ion-badge></div></ion-title>
          <ion-button slot="end" fill="clear" :disabled="syncing" @click="forceSync"><ion-icon :icon="session?.cloudEnabled ? syncOutline : cloudOfflineOutline" /></ion-button>
        </ion-toolbar>
      </ion-header>

      <ion-content :fullscreen="true" class="app-content">
        <p v-if="actionMessage" class="action-message" @click="actionMessage = ''">{{ actionMessage }}</p>

        <main v-if="active === 'home'" class="screen home-screen">
          <section class="hero-dashboard">
            <div><p class="eyebrow">TRACKSIDE LOGGER</p><h1>Pronto, {{ firstName }}.</h1></div>
            <p>Registra subito. Nessun account, weekend, vettura o configurazione obbligatori.</p>
          </section>

          <section class="quick-actions">
            <button class="quick-primary" type="button" @click="active = 'timer'"><ion-icon :icon="stopwatchOutline" /><div><strong>Cronometro</strong><span>Tempo sul giro in 1 tocco</span></div></button>
            <button type="button" @click="active = 'sessions'"><ion-icon :icon="flagOutline" /><div><strong>Sessione</strong><span>Libera o collegata</span></div></button>
            <button type="button" @click="active = 'pit'"><ion-icon :icon="speedometerOutline" /><div><strong>Nota rapida</strong><span>Setup, pista, pilota</span></div></button>
            <button type="button" @click="active = 'gallery'"><ion-icon :icon="cameraOutline" /><div><strong>Foto / video</strong><span>Anche senza titolo</span></div></button>
          </section>

          <section class="metric-strip">
            <div><span>BEST LOCALE</span><strong>{{ bestLap !== null ? formatLapTime(bestLap) : '—' }}</strong></div>
            <div><span>QUICK LOG</span><strong>{{ quick.length }}</strong></div>
            <div><span>IN CODA</span><strong>{{ pending }}</strong></div>
          </section>

          <div class="section-head compact"><div><p class="eyebrow">RECENTI</p><h2>Ultime registrazioni</h2></div><ion-button fill="clear" @click="active = 'timer'">Aggiungi</ion-button></div>
          <div v-if="recentQuick.length" class="timeline-list">
            <article v-for="item in recentQuick" :key="item.id" class="timeline-row">
              <div class="timeline-icon"><ion-icon :icon="item.kind === 'lap_time' ? stopwatchOutline : item.kind === 'session' ? flagOutline : item.kind === 'setup' ? settingsOutline : timeOutline" /></div>
              <div><strong>{{ item.title }}</strong><span>{{ quickMeta(item) }}</span></div>
              <button type="button" class="icon-button danger" @click="removeQuick(item.id)"><ion-icon :icon="trashOutline" /></button>
            </article>
          </div>
          <div v-else class="empty compact-empty">Premi Cronometro, Sessione o Nota rapida: il primo dato viene salvato subito sul telefono.</div>

          <button v-if="!session" class="cloud-invite" type="button" @click="active = 'account'">
            <ion-icon :icon="cloudOfflineOutline" /><div><strong>Account opzionale</strong><span>Usa tutto in locale. Accedi solo per collegare cloud e database.</span></div>
          </button>
        </main>

        <main v-else-if="active === 'timer'" class="screen timer-screen">
          <div class="section-head"><div><p class="eyebrow">QUICK TIMING</p><h1>Cronometro</h1></div><ion-badge color="medium">locale</ion-badge></div>

          <section class="stopwatch-panel">
            <span class="stopwatch-label">TEMPO CORRENTE</span>
            <strong class="stopwatch-value">{{ formatLapTime(timerElapsed) }}</strong>
            <div class="stopwatch-controls">
              <ion-button v-if="!timerRunning" expand="block" @click="startTimer"><ion-icon slot="start" :icon="playOutline" />Avvia</ion-button>
              <ion-button v-else expand="block" color="danger" @click="pauseTimer"><ion-icon slot="start" :icon="pauseOutline" />Pausa</ion-button>
              <ion-button v-if="timerElapsed > 0" expand="block" fill="outline" @click="saveTimedLap(timerRunning)">{{ timerRunning ? 'Giro' : 'Salva tempo' }}</ion-button>
            </div>
            <ion-button v-if="timerElapsed > 0" fill="clear" size="small" @click="resetTimer">Azzera</ion-button>
          </section>

          <ion-card class="composer compact-composer"><ion-card-content>
            <div class="two-col"><ion-input v-model="timingLabel" label="Pista / etichetta" label-placement="stacked" placeholder="Es. Kartodromo" /><ion-input v-model="timingVehicle" label="Mezzo" label-placement="stacked" placeholder="Opzionale" /></div>
            <ion-textarea v-model="timingNotes" label="Nota" label-placement="stacked" :auto-grow="true" placeholder="Gomme, meteo, traffico…" />
          </ion-card-content></ion-card>

          <section class="manual-time">
            <div><p class="eyebrow">INSERIMENTO MANUALE</p><h2>Hai già il tempo?</h2></div>
            <div class="manual-time-row"><ion-input v-model="manualLap" inputmode="decimal" placeholder="1:23.456" /><ion-button @click="saveManualLap">Salva</ion-button></div>
          </section>

          <div class="compact-list">
            <article v-for="item in quick.filter(record => record.kind === 'lap_time').slice(0, 12)" :key="item.id" class="compact-row lap-row">
              <ion-icon :icon="stopwatchOutline" /><div><strong>{{ formatLapTime(item.value_ms) }}</strong><span>{{ item.title }} · {{ formatDate(item.occurred_at) }}</span></div><button type="button" class="icon-button danger" @click="removeQuick(item.id)"><ion-icon :icon="trashOutline" /></button>
            </article>
          </div>
        </main>

        <main v-else-if="active === 'sessions'" class="screen">
          <div class="section-head"><div><p class="eyebrow">SESSIONS</p><h1>Nuova sessione</h1></div><ion-badge color="medium">gerarchia opzionale</ion-badge></div>
          <ion-card class="composer"><ion-card-content>
            <div class="form-intro"><strong>Registra prima, organizza dopo.</strong><span>Se lasci vuota la configurazione, PitMetric crea una sessione libera locale.</span></div>
            <ion-select v-model="sessionConfigurationId" label="Configurazione (opzionale)" label-placement="stacked"><ion-select-option :value="null">Nessuna — sessione libera</ion-select-option><ion-select-option v-for="config in snapshot.configurations" :key="config.id" :value="config.id">{{ config.vehicle }} · {{ config.configuration }} v{{ config.version_number }}</ion-select-option></ion-select>
            <ion-select v-model="sessionEventId" label="Weekend (opzionale)" label-placement="stacked" @ion-change="sessionEntryId = null"><ion-select-option :value="null">Nessuno</ion-select-option><ion-select-option v-for="event in snapshot.events" :key="event.id" :value="event.id">{{ event.name }}</ion-select-option></ion-select>
            <ion-select v-if="selectedSessionEvent" v-model="sessionEntryId" label="Iscrizione (opzionale)" label-placement="stacked"><ion-select-option :value="null">Nessuna</ion-select-option><ion-select-option v-for="entry in selectedSessionEvent.entries" :key="entry.id" :value="entry.id">#{{ entry.entry_number || '—' }} · {{ entry.driver || entry.vehicle }}</ion-select-option></ion-select>
            <div class="two-col"><ion-select v-model="sessionType" label="Tipo" label-placement="stacked"><ion-select-option v-for="type in ['practice','qualifying','heat','prefinal','final','race','test']" :key="type" :value="type">{{ type }}</ion-select-option></ion-select><ion-input v-model="sessionStartedAt" type="datetime-local" label="Inizio" label-placement="stacked" /></div>
            <div class="three-col"><ion-input v-model.number="sessionLaps" type="number" label="Giri" label-placement="stacked" /><ion-input v-model.number="sessionDuration" type="number" label="Minuti" label-placement="stacked" /><ion-input v-model="sessionBestLap" inputmode="decimal" label="Best lap" label-placement="stacked" placeholder="1:23.456" /></div>
            <ion-textarea v-model="sessionNotes" label="Note" label-placement="stacked" :auto-grow="true" />
            <ion-button expand="block" @click="saveSession"><ion-icon slot="start" :icon="addOutline" />Registra sessione</ion-button>
          </ion-card-content></ion-card>

          <div class="compact-list">
            <article v-for="item in quick.filter(record => record.kind === 'session').slice(0, 8)" :key="item.id" class="compact-row"><ion-icon :icon="flagOutline" /><div><strong>{{ item.title }}</strong><span>{{ quickMeta(item) }} · {{ item.payload.completed_laps ?? '—' }} giri</span></div><ion-badge color="medium">locale</ion-badge></article>
            <article v-for="item in snapshot.sessions.slice(0, 8)" :key="`cloud-${item.id}`" class="compact-row"><ion-icon :icon="cloudDoneOutline" /><div><strong>{{ item.vehicle || item.session_type }}</strong><span>{{ item.session_type }} · {{ formatDate(item.started_at) }} · {{ item.completed_laps ?? '—' }} giri</span></div><ion-badge>{{ item.status }}</ion-badge></article>
          </div>
        </main>

        <main v-else-if="active === 'pit'" class="screen">
          <div class="section-head"><div><p class="eyebrow">QUICK NOTE</p><h1>Nota rapida</h1></div><ion-badge color="medium">{{ currentEvent ? 'collegata' : 'libera' }}</ion-badge></div>
          <ion-card class="composer"><ion-card-content>
            <ion-select v-model="selectedEventId" label="Weekend (opzionale)" label-placement="stacked"><ion-select-option :value="null">Nessuno — nota libera</ion-select-option><ion-select-option v-for="event in snapshot.events" :key="event.id" :value="event.id">{{ event.name }}</ion-select-option></ion-select>
            <ion-select v-model="noteKind" label="Tipo" label-placement="stacked"><ion-select-option value="technical">Tecnica</ion-select-option><ion-select-option value="driver">Pilota</ion-select-option><ion-select-option value="strategy">Strategia</ion-select-option><ion-select-option value="weather">Meteo</ion-select-option><ion-select-option value="incident">Incidente</ion-select-option></ion-select>
            <ion-textarea v-model="noteBody" label="Nota" label-placement="stacked" :auto-grow="true" placeholder="Scrivi e salva. Non serve preparare nulla prima." />
            <ion-button expand="block" @click="savePitNote">Salva ora</ion-button>
          </ion-card-content></ion-card>
          <div class="compact-list"><article v-for="item in quick.filter(record => record.kind === 'note').slice(0, 12)" :key="item.id" class="compact-row"><ion-icon :icon="timeOutline" /><div><strong>{{ item.title }}</strong><span>{{ item.payload.kind }} · {{ formatDate(item.occurred_at) }}</span></div><button type="button" class="icon-button danger" @click="removeQuick(item.id)"><ion-icon :icon="trashOutline" /></button></article></div>
        </main>

        <main v-else-if="active === 'garage'" class="screen">
          <div class="section-head"><div><p class="eyebrow">GARAGE</p><h1>Mezzi</h1></div><ion-badge>{{ snapshot.vehicles.length }}</ion-badge></div>
          <div class="compact-list"><article v-for="vehicle in snapshot.vehicles" :key="vehicle.id" class="compact-row"><ion-icon :icon="carSportOutline" /><div><strong>{{ vehicle.name }}</strong><span>{{ vehicle.manufacturer }} {{ vehicle.model }} · {{ vehicle.category }}</span></div><ion-badge :color="vehicle.status === 'active' ? 'success' : 'medium'">{{ vehicle.status }}</ion-badge></article></div>
          <ion-card class="composer"><ion-card-header><ion-card-title>Aggiungi mezzo</ion-card-title></ion-card-header><ion-card-content>
            <ion-input v-model="vehicleName" label="Nome" label-placement="stacked" placeholder="È l’unico campo necessario" />
            <ion-select v-model="vehicleCategory" label="Categoria" label-placement="stacked"><ion-select-option v-for="category in ['kart','formula','gt','touring','rally','prototype','hypercar','road_car','motorcycle','other']" :key="category" :value="category">{{ category }}</ion-select-option></ion-select>
            <div class="two-col"><ion-input v-model="vehicleManufacturer" label="Costruttore" label-placement="stacked" /><ion-input v-model="vehicleModel" label="Modello" label-placement="stacked" /></div>
            <ion-input v-model="vehicleIdentifier" label="Telaio / identificativo" label-placement="stacked" />
            <ion-button expand="block" @click="saveVehicle">Salva offline</ion-button>
          </ion-card-content></ion-card>
        </main>

        <main v-else-if="active === 'weekend'" class="screen">
          <p class="eyebrow">RACE WEEKEND</p><h1>Weekend</h1>
          <div v-if="snapshot.events.length" class="stack-list"><ion-card v-for="event in snapshot.events" :key="event.id"><ion-card-header><div class="card-title-row"><ion-card-title>{{ event.name }}</ion-card-title><ion-badge>{{ event.status }}</ion-badge></div></ion-card-header><ion-card-content><p class="muted">{{ event.championship }} {{ event.round_label ? `· ${event.round_label}` : '' }}</p><div class="schedule-list" v-if="event.schedule.length"><div v-for="item in event.schedule" :key="item.id" class="schedule-row"><div><strong>{{ item.label }}</strong><span>{{ formatDate(item.starts_at) }}</span></div><ion-badge :color="item.status === 'completed' ? 'success' : 'medium'">{{ item.status }}</ion-badge></div></div><div class="entry-chips" v-if="event.entries.length"><ion-chip v-for="entry in event.entries" :key="entry.id">#{{ entry.entry_number || '—' }} {{ entry.driver || entry.vehicle }}</ion-chip></div></ion-card-content></ion-card></div>
          <div v-else class="empty">Nessun weekend: non è un problema. Cronometro, sessioni libere e note funzionano comunque.</div>
        </main>

        <main v-else-if="active === 'maintenance'" class="screen">
          <div class="section-head"><div><p class="eyebrow">MAINTENANCE</p><h1>Manutenzione</h1></div><ion-button fill="clear" @click="active = 'more'">Indietro</ion-button></div>
          <div class="compact-list"><article v-for="order in snapshot.work_orders" :key="order.id" class="compact-row"><ion-icon :icon="buildOutline" /><div><strong>{{ order.title }}</strong><span>{{ order.schedule || 'Intervento libero' }} · {{ order.due_at ? formatDate(order.due_at) : 'senza scadenza' }}</span></div><ion-badge :color="order.priority === 'critical' ? 'danger' : order.priority === 'high' ? 'warning' : 'medium'">{{ order.priority }}</ion-badge></article></div>
          <ion-card class="composer"><ion-card-content><ion-input v-model="workOrderTitle" label="Intervento" label-placement="stacked" /><ion-select v-model="workOrderScheduleId" label="Piano (opzionale)" label-placement="stacked"><ion-select-option :value="null">Nessuno</ion-select-option><ion-select-option v-for="item in snapshot.maintenance_schedules" :key="item.id" :value="item.id">{{ item.name }}</ion-select-option></ion-select><ion-select v-model="workOrderPriority" label="Priorità" label-placement="stacked"><ion-select-option v-for="priority in ['low','normal','high','critical']" :key="priority" :value="priority">{{ priority }}</ion-select-option></ion-select><ion-textarea v-model="workOrderNotes" label="Note" label-placement="stacked" :auto-grow="true" /><ion-button expand="block" @click="saveWorkOrder">Salva offline</ion-button></ion-card-content></ion-card>
        </main>

        <main v-else-if="active === 'setup'" class="screen">
          <div class="section-head"><div><p class="eyebrow">SETUP</p><h1>Setup</h1></div><ion-button fill="clear" @click="active = 'more'">Indietro</ion-button></div>
          <div class="compact-list"><article v-for="item in quick.filter(record => record.kind === 'setup').slice(0, 6)" :key="item.id" class="compact-row"><ion-icon :icon="settingsOutline" /><div><strong>{{ item.title }}</strong><span>Setup libero · {{ formatDate(item.occurred_at) }}</span></div><ion-badge color="medium">locale</ion-badge></article><article v-for="item in snapshot.setups" :key="`cloud-${item.id}`" class="compact-row"><ion-icon :icon="settingsOutline" /><div><strong>{{ item.name }}</strong><span>{{ item.vehicle }} · {{ item.description || 'Nessuna descrizione' }}</span></div><ion-badge>{{ item.status }}</ion-badge></article></div>
          <ion-card class="composer"><ion-card-content>
            <ion-select v-model="setupVehicleId" label="Mezzo (opzionale)" label-placement="stacked"><ion-select-option :value="null">Nessuno — setup libero</ion-select-option><ion-select-option v-for="vehicle in snapshot.vehicles" :key="vehicle.id" :value="vehicle.id">{{ vehicle.name }}</ion-select-option></ion-select>
            <ion-input v-model="setupName" label="Nome setup" label-placement="stacked" />
            <ion-textarea v-model="setupDescription" label="Descrizione" label-placement="stacked" :auto-grow="true" />
            <p class="form-label">Pressioni gomme (bar)</p><div class="four-col"><ion-input v-model.number="setupTyreFl" type="number" label="FL" label-placement="stacked" /><ion-input v-model.number="setupTyreFr" type="number" label="FR" label-placement="stacked" /><ion-input v-model.number="setupTyreRl" type="number" label="RL" label-placement="stacked" /><ion-input v-model.number="setupTyreRr" type="number" label="RR" label-placement="stacked" /></div>
            <div class="two-col"><ion-input v-model.number="setupRideFront" type="number" label="Altezza ant. mm" label-placement="stacked" /><ion-input v-model.number="setupRideRear" type="number" label="Altezza post. mm" label-placement="stacked" /></div>
            <ion-input v-model.number="setupBrakeBias" type="number" label="Brake bias %" label-placement="stacked" />
            <ion-button expand="block" @click="saveSetup">Salva setup</ion-button>
          </ion-card-content></ion-card>
        </main>

        <main v-else-if="active === 'gallery'" class="screen">
          <div class="section-head"><div><p class="eyebrow">MEDIA</p><h1>Galleria</h1></div><ion-button fill="clear" @click="active = 'more'">Indietro</ion-button></div>
          <ion-card class="composer"><ion-card-content><ion-input v-model="mediaTitle" label="Titolo (opzionale)" label-placement="stacked" placeholder="PitMetric ne userà uno generico" /><ion-textarea v-model="mediaDescription" label="Descrizione" label-placement="stacked" :auto-grow="true" /><div class="media-buttons"><ion-button expand="block" @click="addPhoto"><ion-icon slot="start" :icon="cameraOutline" />Fotocamera</ion-button><ion-button expand="block" fill="outline" @click="addMedia"><ion-icon slot="start" :icon="videocamOutline" />Foto / video</ion-button></div></ion-card-content></ion-card>
          <div v-if="gallery.length" class="gallery-grid"><article v-for="item in gallery" :key="item.local_id" class="photo-card"><video v-if="item.media_type === 'video'" :src="item.preview_uri || item.local_uri" controls playsinline preload="metadata" /><img v-else-if="item.preview_uri || item.local_uri.startsWith('http')" :src="item.preview_uri || item.local_uri" :alt="item.title" /><div v-else class="photo-placeholder"><ion-icon :icon="imagesOutline" /></div><div class="photo-body"><div class="photo-title"><strong>{{ item.title }}</strong><ion-badge :color="item.sync_state === 'synced' ? 'success' : item.sync_state === 'error' ? 'danger' : 'warning'">{{ item.sync_state }}</ion-badge></div><p>{{ item.description }}</p></div></article></div>
          <div v-else class="empty">Nessun media. Fotocamera e archivio funzionano anche senza account.</div>
        </main>

        <main v-else-if="active === 'account'" class="screen account-screen">
          <div class="section-head"><div><p class="eyebrow">ACCOUNT & CLOUD</p><h1>{{ session ? session.user?.name : 'Facoltativo' }}</h1></div><ion-button fill="clear" @click="active = 'more'">Indietro</ion-button></div>
          <template v-if="!session">
            <section class="local-first-note"><ion-icon :icon="personCircleOutline" /><div><strong>Non serve registrarsi.</strong><span>L’app resta utilizzabile in locale. Crea un account solo per collegare il dispositivo al cloud PitMetric.</span></div></section>
            <ion-card class="composer auth-card"><ion-card-content>
              <div class="auth-switch"><button :class="{ active: authMode === 'login' }" @click="authMode = 'login'">Accedi</button><button :class="{ active: authMode === 'register' }" @click="authMode = 'register'">Registrati</button></div>
              <ion-input v-if="authMode === 'register'" v-model="name" label="Nome" label-placement="stacked" autocomplete="name" />
              <ion-input v-model="email" label="Email" label-placement="stacked" type="email" autocomplete="email" />
              <ion-input v-model="password" label="Password" label-placement="stacked" type="password" :autocomplete="authMode === 'login' ? 'current-password' : 'new-password'" />
              <p v-if="authError" class="error">{{ authError }}</p>
              <ion-button expand="block" @click="submitAuth">{{ authMode === 'login' ? 'Collega account' : 'Crea e collega account' }}</ion-button>
              <ion-button v-if="biometric && authMode === 'login'" expand="block" fill="outline" @click="unlock"><ion-icon slot="start" :icon="fingerPrintOutline" />Sblocca sessione salvata</ion-button>
            </ion-card-content></ion-card>
          </template>
          <template v-else>
            <p class="muted">{{ session.user?.email }}</p>
            <ion-card><ion-card-content><strong>{{ cloudLabel }}</strong><p>{{ session.cloudEnabled ? 'Le operazioni compatibili vengono sincronizzate automaticamente. I Quick Log senza gerarchia restano sempre disponibili sul telefono.' : 'Puoi continuare a usare tutto in locale. Il cloud non è abilitato per questo account.' }}</p><p class="muted small">Ultimo snapshot: {{ snapshot.synced_at ? formatDate(snapshot.synced_at) : 'mai' }}</p></ion-card-content></ion-card>
            <ion-card v-if="pendingOps.length"><ion-card-header><ion-card-title>Coda offline</ion-card-title></ion-card-header><ion-card-content><div class="queue-row" v-for="item in pendingOps" :key="item.local_id"><span>{{ item.operation }}</span><ion-badge color="warning">{{ item.attempts }} retry</ion-badge></div></ion-card-content></ion-card>
            <ion-button v-if="session.cloudEnabled" expand="block" @click="forceSync"><ion-icon slot="start" :icon="syncOutline" />Sincronizza adesso</ion-button>
            <ion-button expand="block" fill="outline" color="danger" @click="signOut"><ion-icon slot="start" :icon="logOutOutline" />Scollega account</ion-button>
          </template>
        </main>

        <main v-else class="screen">
          <p class="eyebrow">TOOLS</p><h1>Altro</h1>
          <div class="tools-grid">
            <button type="button" @click="active = 'pit'"><ion-icon :icon="speedometerOutline" /><strong>Nota rapida</strong><span>Senza weekend</span></button>
            <button type="button" @click="active = 'weekend'"><ion-icon :icon="calendarOutline" /><strong>Weekend</strong><span>Quando ti serve struttura</span></button>
            <button type="button" @click="active = 'maintenance'"><ion-icon :icon="buildOutline" /><strong>Manutenzione</strong><span>Interventi e priorità</span></button>
            <button type="button" @click="active = 'setup'"><ion-icon :icon="settingsOutline" /><strong>Setup</strong><span>Anche senza mezzo</span></button>
            <button type="button" @click="active = 'gallery'"><ion-icon :icon="imagesOutline" /><strong>Galleria</strong><span>Foto + video</span></button>
            <button type="button" @click="active = 'account'"><ion-icon :icon="personCircleOutline" /><strong>{{ session ? 'Account' : 'Cloud opzionale' }}</strong><span>{{ session ? cloudLabel : 'Accedi quando vuoi' }}</span></button>
          </div>
        </main>
      </ion-content>

      <ion-tab-bar slot="bottom" class="bottom-nav">
        <ion-tab-button :selected="active === 'home'" @click="active = 'home'"><ion-icon :icon="homeOutline" /><ion-label>Home</ion-label></ion-tab-button>
        <ion-tab-button :selected="active === 'timer'" @click="active = 'timer'"><ion-icon :icon="stopwatchOutline" /><ion-label>Timer</ion-label></ion-tab-button>
        <ion-tab-button :selected="active === 'sessions'" @click="active = 'sessions'"><ion-icon :icon="flagOutline" /><ion-label>Sessioni</ion-label></ion-tab-button>
        <ion-tab-button :selected="active === 'garage'" @click="active = 'garage'"><ion-icon :icon="carSportOutline" /><ion-label>Garage</ion-label></ion-tab-button>
        <ion-tab-button :selected="moreSelected" @click="active = 'more'"><ion-icon :icon="ellipsisHorizontalOutline" /><ion-label>Altro</ion-label></ion-tab-button>
      </ion-tab-bar>
    </ion-page>
  </ion-app>
</template>