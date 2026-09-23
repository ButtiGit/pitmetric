<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
  IonApp, IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButton,
  IonInput, IonTextarea, IonItem, IonLabel, IonList, IonIcon, IonBadge,
  IonTabBar, IonTabButton, IonCard, IonCardContent, IonCardHeader, IonCardTitle,
  IonSpinner, IonSelect, IonSelectOption, IonChip
} from '@ionic/vue';
import {
  addOutline, buildOutline, calendarOutline, cameraOutline, carSportOutline,
  cloudDoneOutline, cloudOfflineOutline, ellipsisHorizontalOutline, fingerPrintOutline,
  flagOutline, homeOutline, imagesOutline, logOutOutline, settingsOutline,
  speedometerOutline, stopwatchOutline, syncOutline, videocamOutline
} from 'ionicons/icons';
import {
  addGalleryMedia, addGalleryPhoto, biometricAvailable, biometricUnlock, bootLocalStore,
  cachedSnapshot, galleryItems, login, logout, pendingCount, pendingOperations, queueOperation,
  refreshSnapshotFromCloud, register, restoreSession, startAutoSync, syncNow,
  type EventItem, type GalleryItem, type MobileSnapshot, type PendingOperation,
  type SessionState
} from './lib';

type Screen = 'home' | 'pit' | 'weekend' | 'garage' | 'more' | 'sessions' | 'maintenance' | 'setup' | 'gallery' | 'account';

const ready = ref(false);
const session = ref<SessionState | null>(null);
const authMode = ref<'login' | 'register'>('login');
const name = ref('');
const email = ref('');
const password = ref('');
const authError = ref('');
const biometric = ref(false);
const active = ref<Screen>('home');
const gallery = ref<GalleryItem[]>([]);
const snapshot = ref<MobileSnapshot>({ events: [], vehicles: [], sessions: [], configurations: [], maintenance_schedules: [], work_orders: [], setups: [], setup_fields: {} });
const pendingOps = ref<PendingOperation[]>([]);
const pending = ref(0);
const syncing = ref(false);
const actionMessage = ref('');
let stopAutoSync: (() => Promise<void>) | null = null;

const mediaTitle = ref('');
const mediaDescription = ref('');
const selectedEventId = ref<number | null>(null);
const noteBody = ref('');
const noteKind = ref('technical');

const sessionEventId = ref<number | null>(null);
const sessionEntryId = ref<number | null>(null);
const sessionConfigurationId = ref<number | null>(null);
const sessionType = ref('practice');
const sessionStartedAt = ref(new Date().toISOString().slice(0, 16));
const sessionLaps = ref<number | null>(null);
const sessionDuration = ref<number | null>(null);
const sessionNotes = ref('');

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

const cloudLabel = computed(() => session.value?.cloudEnabled ? 'Cloud attivo' : 'Solo dispositivo');
const currentEvent = computed<EventItem | null>(() => snapshot.value.events.find(event => event.id === selectedEventId.value) || snapshot.value.events[0] || null);
const selectedSessionEvent = computed<EventItem | null>(() => snapshot.value.events.find(event => event.id === sessionEventId.value) || null);
const moreSelected = computed(() => !['home', 'pit', 'weekend', 'garage'].includes(active.value));

function formatDate(value?: string | null): string {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('it-IT', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }).format(date);
}

async function refreshLocal(preferCloud = false) {
  gallery.value = await galleryItems();
  pending.value = await pendingCount();
  pendingOps.value = await pendingOperations();
  snapshot.value = await cachedSnapshot();

  if (preferCloud && session.value?.cloudEnabled) {
    snapshot.value = await refreshSnapshotFromCloud();
  }

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
    await refreshLocal(true);
  } catch (error) {
    authError.value = error instanceof Error ? error.message : 'Errore di accesso.';
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

async function savePitNote() {
  const event = currentEvent.value;
  if (!event || !noteBody.value.trim()) return;
  await queue('event.note.create', {
    event_id: event.id,
    kind: noteKind.value,
    body: noteBody.value.trim(),
    occurred_at: new Date().toISOString()
  }, 'Nota salvata sul dispositivo.');
  noteBody.value = '';
}

async function saveSession() {
  if (!sessionConfigurationId.value) return;
  if (sessionEventId.value && !sessionEntryId.value) {
    actionMessage.value = 'Se selezioni un weekend devi scegliere anche la vettura/iscrizione.';
    return;
  }

  const payload: Record<string, unknown> = {
    configuration_version_id: sessionConfigurationId.value,
    session_type: sessionType.value,
    started_at: new Date(sessionStartedAt.value).toISOString(),
    completed_laps: sessionLaps.value,
    duration_minutes: sessionDuration.value,
    notes: sessionNotes.value || null
  };
  if (sessionEventId.value) payload.event_id = sessionEventId.value;
  if (sessionEntryId.value) payload.event_entry_id = sessionEntryId.value;

  await queue('session.create', payload, 'Sessione salvata offline e messa in coda.');
  sessionLaps.value = null;
  sessionDuration.value = null;
  sessionNotes.value = '';
}

async function saveVehicle() {
  if (!vehicleName.value.trim()) return;
  await queue('vehicle.create', {
    name: vehicleName.value.trim(),
    category: vehicleCategory.value,
    manufacturer: vehicleManufacturer.value || null,
    model: vehicleModel.value || null,
    identifier: vehicleIdentifier.value || null
  }, 'Veicolo salvato offline.');
  vehicleName.value = '';
  vehicleManufacturer.value = '';
  vehicleModel.value = '';
  vehicleIdentifier.value = '';
}

async function saveWorkOrder() {
  if (!workOrderTitle.value.trim()) return;
  await queue('maintenance.work_order.create', {
    maintenance_schedule_id: workOrderScheduleId.value,
    title: workOrderTitle.value.trim(),
    priority: workOrderPriority.value,
    notes: workOrderNotes.value || null
  }, 'Intervento salvato offline.');
  workOrderTitle.value = '';
  workOrderNotes.value = '';
}

async function saveSetup() {
  if (!setupVehicleId.value || !setupName.value.trim()) return;
  const values: Record<string, number> = {};
  const map: Array<[string, number | null]> = [
    ['tyre_pressure_fl', setupTyreFl.value], ['tyre_pressure_fr', setupTyreFr.value],
    ['tyre_pressure_rl', setupTyreRl.value], ['tyre_pressure_rr', setupTyreRr.value],
    ['ride_height_front_mm', setupRideFront.value], ['ride_height_rear_mm', setupRideRear.value],
    ['brake_bias_pct', setupBrakeBias.value]
  ];
  map.forEach(([key, value]) => { if (value !== null && value !== undefined) values[key] = Number(value); });

  await queue('setup.create', {
    vehicle_id: setupVehicleId.value,
    name: setupName.value.trim(),
    description: setupDescription.value || null,
    values
  }, 'Setup salvato offline.');
  setupName.value = '';
  setupDescription.value = '';
}

async function addPhoto() {
  if (!mediaTitle.value.trim()) return;
  await addGalleryPhoto(mediaTitle.value.trim(), mediaDescription.value.trim());
  mediaTitle.value = '';
  mediaDescription.value = '';
  await refreshLocal();
}

async function addMedia() {
  if (!mediaTitle.value.trim()) return;
  try {
    await addGalleryMedia(mediaTitle.value.trim(), mediaDescription.value.trim());
    mediaTitle.value = '';
    mediaDescription.value = '';
    await refreshLocal();
  } catch (error) {
    actionMessage.value = error instanceof Error ? error.message : 'File non disponibile.';
  }
}

async function forceSync() {
  syncing.value = true;
  actionMessage.value = '';
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
}

function openMore(screen: Screen) {
  active.value = screen;
  actionMessage.value = '';
}

onMounted(async () => {
  await bootLocalStore();
  session.value = await restoreSession();
  biometric.value = await biometricAvailable().catch(() => false);
  await refreshLocal(Boolean(session.value?.cloudEnabled));
  stopAutoSync = await startAutoSync(() => refreshLocal(false));
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
          <p class="muted">Accedi una volta online. Poi puoi riaprire l’app con viso, impronta o codice dispositivo e lavorare senza rete.</p>
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
        <p v-if="actionMessage" class="action-message">{{ actionMessage }}</p>

        <main v-if="active === 'home'" class="screen">
          <section class="hero">
            <p class="eyebrow">TRACKSIDE</p>
            <h1>Ciao, {{ session.user?.name?.split(' ')[0] }}</h1>
            <p v-if="session.cloudEnabled">Tutto viene scritto prima sul telefono. Se perdi il segnale in pista continui a lavorare e PitMetric sincronizza dopo.</p>
            <p v-else>Il database cloud non è attivo. L’app continua a salvare sul dispositivo; la coda resta pronta finché il cloud non viene abilitato.</p>
          </section>

          <div class="status-grid">
            <ion-card><ion-card-content><ion-icon :icon="session.cloudEnabled ? cloudDoneOutline : cloudOfflineOutline" /><strong>{{ cloudLabel }}</strong><span>{{ pending }} in coda</span></ion-card-content></ion-card>
            <ion-card><ion-card-content><ion-icon :icon="flagOutline" /><strong>{{ snapshot.events.length }} weekend</strong><span>{{ snapshot.workspace?.name || 'Locale' }}</span></ion-card-content></ion-card>
            <ion-card><ion-card-content><ion-icon :icon="carSportOutline" /><strong>{{ snapshot.vehicles.length }} mezzi</strong><span>Garage</span></ion-card-content></ion-card>
            <ion-card><ion-card-content><ion-icon :icon="imagesOutline" /><strong>{{ gallery.length }} media</strong><span>Foto + video</span></ion-card-content></ion-card>
          </div>

          <ion-card v-if="currentEvent" class="action-card" button @click="active = 'weekend'">
            <ion-card-header><ion-card-title>{{ currentEvent.name }}</ion-card-title></ion-card-header>
            <ion-card-content>{{ currentEvent.round_label || currentEvent.championship || 'Race weekend' }} · {{ currentEvent.status }}</ion-card-content>
          </ion-card>
          <ion-card class="action-card" button @click="active = 'pit'"><ion-card-header><ion-card-title>Pit Mode</ion-card-title></ion-card-header><ion-card-content>Nota tecnica immediata e registrazione sessione con pochi tocchi.</ion-card-content></ion-card>
        </main>

        <main v-else-if="active === 'pit'" class="screen">
          <div class="section-head"><div><p class="eyebrow">LIVE OPERATIONS</p><h1>Pit Mode</h1></div><ion-badge color="warning">{{ pending }} offline</ion-badge></div>
          <ion-card v-if="snapshot.events.length" class="composer"><ion-card-content>
            <ion-select v-model="selectedEventId" label="Weekend attivo" label-placement="stacked" interface="action-sheet">
              <ion-select-option v-for="event in snapshot.events" :key="event.id" :value="event.id">{{ event.name }}</ion-select-option>
            </ion-select>
            <ion-select v-model="noteKind" label="Tipo nota" label-placement="stacked" interface="action-sheet">
              <ion-select-option value="technical">Tecnica</ion-select-option><ion-select-option value="driver">Pilota</ion-select-option><ion-select-option value="strategy">Strategia</ion-select-option><ion-select-option value="weather">Meteo</ion-select-option><ion-select-option value="incident">Incidente</ion-select-option>
            </ion-select>
            <ion-textarea v-model="noteBody" label="Nota rapida" label-placement="stacked" :auto-grow="true" placeholder="Pressioni, comportamento vettura, pista…" />
            <ion-button expand="block" @click="savePitNote"><ion-icon slot="start" :icon="addOutline" />Salva nota</ion-button>
          </ion-card-content></ion-card>
          <div v-else class="empty">Sincronizza almeno un weekend una volta online. Dopo sarà disponibile anche senza rete.</div>

          <ion-card class="action-card" button @click="active = 'sessions'"><ion-card-header><ion-card-title>Registra sessione</ion-card-title></ion-card-header><ion-card-content>Giri, durata, configurazione e note vengono messi in coda anche offline.</ion-card-content></ion-card>
        </main>

        <main v-else-if="active === 'weekend'" class="screen">
          <p class="eyebrow">RACE WEEKEND</p><h1>Weekend</h1>
          <div v-if="snapshot.events.length" class="stack-list">
            <ion-card v-for="event in snapshot.events" :key="event.id">
              <ion-card-header><div class="card-title-row"><ion-card-title>{{ event.name }}</ion-card-title><ion-badge>{{ event.status }}</ion-badge></div></ion-card-header>
              <ion-card-content>
                <p class="muted">{{ event.championship }} {{ event.round_label ? `· ${event.round_label}` : '' }}</p>
                <div class="schedule-list" v-if="event.schedule.length">
                  <div v-for="item in event.schedule" :key="item.id" class="schedule-row"><div><strong>{{ item.label }}</strong><span>{{ formatDate(item.starts_at) }}</span></div><ion-badge :color="item.status === 'completed' ? 'success' : 'medium'">{{ item.status }}</ion-badge></div>
                </div>
                <div class="entry-chips" v-if="event.entries.length"><ion-chip v-for="entry in event.entries" :key="entry.id">#{{ entry.entry_number || '—' }} {{ entry.driver || entry.vehicle }}</ion-chip></div>
              </ion-card-content>
            </ion-card>
          </div>
          <div v-else class="empty">Nessun weekend ancora disponibile sul dispositivo.</div>
        </main>

        <main v-else-if="active === 'garage'" class="screen">
          <div class="section-head"><div><p class="eyebrow">GARAGE</p><h1>Mezzi</h1></div><ion-badge>{{ snapshot.vehicles.length }}</ion-badge></div>
          <div class="compact-list"><article v-for="vehicle in snapshot.vehicles" :key="vehicle.id" class="compact-row"><ion-icon :icon="carSportOutline" /><div><strong>{{ vehicle.name }}</strong><span>{{ vehicle.manufacturer }} {{ vehicle.model }} · {{ vehicle.category }}</span></div><ion-badge :color="vehicle.status === 'active' ? 'success' : 'medium'">{{ vehicle.status }}</ion-badge></article></div>
          <ion-card class="composer"><ion-card-header><ion-card-title>Nuovo mezzo offline</ion-card-title></ion-card-header><ion-card-content>
            <ion-input v-model="vehicleName" label="Nome" label-placement="stacked" />
            <ion-select v-model="vehicleCategory" label="Categoria" label-placement="stacked"><ion-select-option v-for="category in ['kart','formula','gt','touring','rally','prototype','hypercar','road_car','motorcycle','other']" :key="category" :value="category">{{ category }}</ion-select-option></ion-select>
            <div class="two-col"><ion-input v-model="vehicleManufacturer" label="Costruttore" label-placement="stacked" /><ion-input v-model="vehicleModel" label="Modello" label-placement="stacked" /></div>
            <ion-input v-model="vehicleIdentifier" label="Telaio / identificativo" label-placement="stacked" />
            <ion-button expand="block" @click="saveVehicle">Salva sul dispositivo</ion-button>
          </ion-card-content></ion-card>
        </main>

        <main v-else-if="active === 'sessions'" class="screen">
          <div class="section-head"><div><p class="eyebrow">SESSIONS</p><h1>Sessioni</h1></div><ion-button fill="clear" @click="active = 'more'">Indietro</ion-button></div>
          <ion-card class="composer"><ion-card-content>
            <ion-select v-model="sessionEventId" label="Weekend (opzionale)" label-placement="stacked" @ion-change="sessionEntryId = null"><ion-select-option :value="null">Sessione libera</ion-select-option><ion-select-option v-for="event in snapshot.events" :key="event.id" :value="event.id">{{ event.name }}</ion-select-option></ion-select>
            <ion-select v-if="selectedSessionEvent" v-model="sessionEntryId" label="Iscrizione" label-placement="stacked"><ion-select-option v-for="entry in selectedSessionEvent.entries" :key="entry.id" :value="entry.id">#{{ entry.entry_number || '—' }} · {{ entry.driver || entry.vehicle }}</ion-select-option></ion-select>
            <ion-select v-model="sessionConfigurationId" label="Configurazione" label-placement="stacked"><ion-select-option v-for="config in snapshot.configurations" :key="config.id" :value="config.id">{{ config.vehicle }} · {{ config.configuration }} v{{ config.version_number }}</ion-select-option></ion-select>
            <div class="two-col"><ion-select v-model="sessionType" label="Tipo" label-placement="stacked"><ion-select-option v-for="type in ['practice','qualifying','heat','prefinal','final','race','test']" :key="type" :value="type">{{ type }}</ion-select-option></ion-select><ion-input v-model="sessionStartedAt" type="datetime-local" label="Inizio" label-placement="stacked" /></div>
            <div class="two-col"><ion-input v-model.number="sessionLaps" type="number" label="Giri" label-placement="stacked" /><ion-input v-model.number="sessionDuration" type="number" label="Minuti" label-placement="stacked" /></div>
            <ion-textarea v-model="sessionNotes" label="Note" label-placement="stacked" :auto-grow="true" />
            <ion-button expand="block" :disabled="!sessionConfigurationId" @click="saveSession"><ion-icon slot="start" :icon="stopwatchOutline" />Registra offline</ion-button>
            <p v-if="!snapshot.configurations.length" class="muted small">Serve almeno una configurazione già sincronizzata dal database PitMetric.</p>
          </ion-card-content></ion-card>
          <div class="compact-list"><article v-for="item in snapshot.sessions" :key="item.id" class="compact-row"><ion-icon :icon="stopwatchOutline" /><div><strong>{{ item.vehicle || item.session_type }}</strong><span>{{ item.session_type }} · {{ formatDate(item.started_at) }} · {{ item.completed_laps ?? '—' }} giri</span></div><ion-badge>{{ item.status }}</ion-badge></article></div>
        </main>

        <main v-else-if="active === 'maintenance'" class="screen">
          <div class="section-head"><div><p class="eyebrow">MAINTENANCE</p><h1>Manutenzione</h1></div><ion-button fill="clear" @click="active = 'more'">Indietro</ion-button></div>
          <div class="compact-list"><article v-for="order in snapshot.work_orders" :key="order.id" class="compact-row"><ion-icon :icon="buildOutline" /><div><strong>{{ order.title }}</strong><span>{{ order.schedule || 'Intervento libero' }} · {{ order.due_at ? formatDate(order.due_at) : 'senza scadenza' }}</span></div><ion-badge :color="order.priority === 'critical' ? 'danger' : order.priority === 'high' ? 'warning' : 'medium'">{{ order.priority }}</ion-badge></article></div>
          <ion-card class="composer"><ion-card-header><ion-card-title>Nuovo intervento</ion-card-title></ion-card-header><ion-card-content>
            <ion-input v-model="workOrderTitle" label="Titolo" label-placement="stacked" />
            <ion-select v-model="workOrderScheduleId" label="Piano manutenzione" label-placement="stacked"><ion-select-option :value="null">Nessuno</ion-select-option><ion-select-option v-for="item in snapshot.maintenance_schedules" :key="item.id" :value="item.id">{{ item.name }}</ion-select-option></ion-select>
            <ion-select v-model="workOrderPriority" label="Priorità" label-placement="stacked"><ion-select-option v-for="priority in ['low','normal','high','critical']" :key="priority" :value="priority">{{ priority }}</ion-select-option></ion-select>
            <ion-textarea v-model="workOrderNotes" label="Note" label-placement="stacked" :auto-grow="true" />
            <ion-button expand="block" @click="saveWorkOrder">Salva offline</ion-button>
          </ion-card-content></ion-card>
        </main>

        <main v-else-if="active === 'setup'" class="screen">
          <div class="section-head"><div><p class="eyebrow">SETUP</p><h1>Setup</h1></div><ion-button fill="clear" @click="active = 'more'">Indietro</ion-button></div>
          <div class="compact-list"><article v-for="item in snapshot.setups" :key="item.id" class="compact-row"><ion-icon :icon="settingsOutline" /><div><strong>{{ item.name }}</strong><span>{{ item.vehicle }} · {{ item.description || 'Nessuna descrizione' }}</span></div><ion-badge>{{ item.status }}</ion-badge></article></div>
          <ion-card class="composer"><ion-card-header><ion-card-title>Nuovo setup offline</ion-card-title></ion-card-header><ion-card-content>
            <ion-select v-model="setupVehicleId" label="Mezzo" label-placement="stacked"><ion-select-option v-for="vehicle in snapshot.vehicles" :key="vehicle.id" :value="vehicle.id">{{ vehicle.name }}</ion-select-option></ion-select>
            <ion-input v-model="setupName" label="Nome setup" label-placement="stacked" />
            <ion-textarea v-model="setupDescription" label="Descrizione" label-placement="stacked" :auto-grow="true" />
            <p class="form-label">Pressioni gomme (bar)</p><div class="four-col"><ion-input v-model.number="setupTyreFl" type="number" label="FL" label-placement="stacked" /><ion-input v-model.number="setupTyreFr" type="number" label="FR" label-placement="stacked" /><ion-input v-model.number="setupTyreRl" type="number" label="RL" label-placement="stacked" /><ion-input v-model.number="setupTyreRr" type="number" label="RR" label-placement="stacked" /></div>
            <div class="two-col"><ion-input v-model.number="setupRideFront" type="number" label="Altezza ant. mm" label-placement="stacked" /><ion-input v-model.number="setupRideRear" type="number" label="Altezza post. mm" label-placement="stacked" /></div>
            <ion-input v-model.number="setupBrakeBias" type="number" label="Brake bias %" label-placement="stacked" />
            <ion-button expand="block" :disabled="!setupVehicleId" @click="saveSetup">Salva offline</ion-button>
          </ion-card-content></ion-card>
        </main>

        <main v-else-if="active === 'gallery'" class="screen">
          <div class="section-head"><div><p class="eyebrow">MEDIA</p><h1>Galleria</h1></div><ion-button fill="clear" @click="active = 'more'">Indietro</ion-button></div>
          <ion-card class="composer"><ion-card-content>
            <ion-input v-model="mediaTitle" label="Titolo" label-placement="stacked" placeholder="Es. Setup pre-qualifica" />
            <ion-textarea v-model="mediaDescription" label="Descrizione" label-placement="stacked" :auto-grow="true" placeholder="Note sul file…" />
            <div class="media-buttons"><ion-button expand="block" @click="addPhoto"><ion-icon slot="start" :icon="cameraOutline" />Fotocamera</ion-button><ion-button expand="block" fill="outline" @click="addMedia"><ion-icon slot="start" :icon="videocamOutline" />Foto / video</ion-button></div>
            <p class="muted small">Video fino a 100 MB. Foto e video restano sul telefono e vengono caricati appena torna la connessione.</p>
          </ion-card-content></ion-card>

          <div v-if="gallery.length" class="gallery-grid">
            <article v-for="item in gallery" :key="item.local_id" class="photo-card">
              <video v-if="item.media_type === 'video'" :src="item.preview_uri || item.local_uri" controls playsinline preload="metadata" />
              <img v-else-if="item.preview_uri || item.local_uri.startsWith('http')" :src="item.preview_uri || item.local_uri" :alt="item.title" />
              <div v-else class="photo-placeholder"><ion-icon :icon="imagesOutline" /></div>
              <div class="photo-body"><div class="photo-title"><strong>{{ item.title }}</strong><ion-badge :color="item.sync_state === 'synced' ? 'success' : item.sync_state === 'error' ? 'danger' : 'warning'">{{ item.sync_state }}</ion-badge></div><p>{{ item.description }}</p><span class="media-kind">{{ item.media_type }} · {{ item.size_bytes ? `${Math.round(item.size_bytes / 1024 / 1024)} MB` : 'locale' }}</span></div>
            </article>
          </div>
          <div v-else class="empty">Nessun media ancora. Il primo può essere aggiunto anche completamente offline.</div>
        </main>

        <main v-else-if="active === 'account'" class="screen">
          <div class="section-head"><div><p class="eyebrow">ACCOUNT</p><h1>{{ session.user?.name }}</h1></div><ion-button fill="clear" @click="active = 'more'">Indietro</ion-button></div>
          <p class="muted">{{ session.user?.email }}</p>
          <ion-card><ion-card-content><strong>Sincronizzazione</strong><p>{{ session.cloudEnabled ? 'Database cloud abilitato. Tutte le operazioni offline vengono accodate e sincronizzate automaticamente.' : 'Database cloud non abilitato. I dati restano sul dispositivo finché l’accesso cloud non viene attivato.' }}</p><p class="muted small">Ultimo snapshot: {{ snapshot.synced_at ? formatDate(snapshot.synced_at) : 'mai' }}</p></ion-card-content></ion-card>
          <ion-card v-if="pendingOps.length"><ion-card-header><ion-card-title>Coda offline</ion-card-title></ion-card-header><ion-card-content><div class="queue-row" v-for="item in pendingOps" :key="item.local_id"><span>{{ item.operation }}</span><ion-badge color="warning">{{ item.attempts }} retry</ion-badge></div></ion-card-content></ion-card>
          <ion-button expand="block" fill="outline" color="danger" @click="signOut"><ion-icon slot="start" :icon="logOutOutline" />Esci</ion-button>
        </main>

        <main v-else class="screen">
          <p class="eyebrow">TOOLS</p><h1>Altro</h1>
          <div class="tools-grid">
            <button type="button" @click="openMore('sessions')"><ion-icon :icon="stopwatchOutline" /><strong>Sessioni</strong><span>Registra e consulta</span></button>
            <button type="button" @click="openMore('maintenance')"><ion-icon :icon="buildOutline" /><strong>Manutenzione</strong><span>Work order</span></button>
            <button type="button" @click="openMore('setup')"><ion-icon :icon="settingsOutline" /><strong>Setup</strong><span>Assetto vettura</span></button>
            <button type="button" @click="openMore('gallery')"><ion-icon :icon="imagesOutline" /><strong>Galleria</strong><span>Foto + video</span></button>
            <button type="button" @click="openMore('account')"><ion-icon :icon="fingerPrintOutline" /><strong>Account</strong><span>Biometria e sync</span></button>
          </div>
        </main>
      </ion-content>

      <ion-tab-bar slot="bottom" class="bottom-nav">
        <ion-tab-button :selected="active === 'home'" @click="active = 'home'"><ion-icon :icon="homeOutline" /><ion-label>Home</ion-label></ion-tab-button>
        <ion-tab-button :selected="active === 'pit'" @click="active = 'pit'"><ion-icon :icon="speedometerOutline" /><ion-label>Pit</ion-label></ion-tab-button>
        <ion-tab-button :selected="active === 'weekend'" @click="active = 'weekend'"><ion-icon :icon="calendarOutline" /><ion-label>Weekend</ion-label></ion-tab-button>
        <ion-tab-button :selected="active === 'garage'" @click="active = 'garage'"><ion-icon :icon="carSportOutline" /><ion-label>Garage</ion-label></ion-tab-button>
        <ion-tab-button :selected="moreSelected" @click="active = 'more'"><ion-icon :icon="ellipsisHorizontalOutline" /><ion-label>Altro</ion-label></ion-tab-button>
      </ion-tab-bar>
    </ion-page>
  </ion-app>
</template>
