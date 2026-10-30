<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Capacitor } from '@capacitor/core';
import { Network } from '@capacitor/network';
import {
  IonApp, IonPage, IonContent, IonButton, IonInput, IonTextarea, IonIcon, IonBadge,
  IonTabBar, IonTabButton, IonSelect, IonSelectOption, IonLabel, IonSpinner
} from '@ionic/vue';
import {
  addOutline, analyticsOutline, buildOutline, cameraOutline, carSportOutline,
  checkmarkCircleOutline, chevronForwardOutline, cloudDoneOutline, cloudOfflineOutline,
  cloudOutline, cloudyOutline, ellipsisHorizontalOutline, flagOutline, homeOutline, imagesOutline, locationOutline, logOutOutline,
  pauseOutline, personCircleOutline, playOutline, settingsOutline, speedometerOutline,
  partlySunnyOutline, rainyOutline, snowOutline, stopwatchOutline, sunnyOutline,
  syncOutline, thunderstormOutline, timeOutline, trashOutline, videocamOutline
} from 'ionicons/icons';
import {
  addGalleryMedia, addGalleryPhoto, biometricAvailable, biometricUnlock, bootLocalStore,
  cachedSnapshot, galleryItems, login, logout, pendingCount, pendingOperations, queueOperation,
  readNativeWeather, refreshSnapshotFromCloud, register, restoreSession, saveNativeWeather, startAutoSync, syncNow,
  type EventItem, type GalleryItem, type MobileSnapshot, type PendingOperation,
  type SessionState
} from './lib';
import {
  addQuickRecord, deleteQuickRecord, formatLapTime, parseLapTime, quickRecords,
  type QuickRecord
} from './quicklog';
import {
  loadDeviceWeather, readDeviceCoordinates, weatherDescription, weatherHour, weatherSky,
  windDirection, type MobileWeatherSnapshot
} from './mobile-weather';
import { readWebWeather, saveWebWeather } from './web-weather-store';

type Screen = 'home' | 'timer' | 'sessions' | 'garage' | 'more' | 'pit' | 'weekend' | 'maintenance' | 'setup' | 'gallery' | 'account' | 'weather';

const webPreview = !Capacitor.isNativePlatform();
const ready = ref(false);
const offlineShellReady = ref(!webPreview || Boolean(navigator.serviceWorker?.controller));
const session = ref<SessionState | null>(null);
const active = ref<Screen>('home');
const actionMessage = ref('');
const weather = ref<MobileWeatherSnapshot | null>(null);
const weatherLoading = ref(false);
const weatherStage = ref('');
const weatherError = ref('');
const weatherStorageError = ref('');
const weatherOnline = ref(typeof navigator === 'undefined' ? true : navigator.onLine);
const weatherCacheState = ref<'none' | 'cached' | 'saved' | 'volatile'>('none');
const weatherStored = computed(() => weatherCacheState.value === 'cached' || weatherCacheState.value === 'saved');
const weatherShowingSaved = computed(() => weatherStored.value && (!weatherOnline.value || weatherCacheState.value === 'cached'));
const syncing = ref(false);
const biometric = ref(false);
let stopAutoSync: (() => Promise<void>) | null = null;
let stopWeatherNetwork: (() => Promise<void>) | null = null;

const authMode = ref<'login' | 'register'>('login');
const name = ref('');
const email = ref('');
const password = ref('');
const authError = ref('');
const authBusy = ref(false);

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
const timingSession = ref('');
const tyrePhase = ref('cold');
const tyrePressures = ref<Record<string, number | string | null>>({});
const timingNotes = ref('');
const manualLap = ref('');

const sessionConfigurationId = ref<number | null>(null);
const sessionEventId = ref<number | null>(null);
const sessionEntryId = ref<number | null>(null);
const sessionType = ref('practice');
const sessionStartedAt = ref(new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 16));
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
const setupKartValues = ref<Record<string, number | string | null>>({});
const setupKartFields = [
  { key: 'camber_front_deg', label: 'Camber anteriore (°)' },
  { key: 'caster_front_deg', label: 'Caster anteriore (°)' },
  { key: 'track_width_front_mm', label: 'Carreggiata anteriore (mm)' },
  { key: 'track_width_rear_mm', label: 'Carreggiata posteriore (mm)' },
  { key: 'rear_axle_length_mm', label: 'Lunghezza assale (mm)' },
  { key: 'pinion_teeth', label: 'Denti pignone' },
  { key: 'crown_teeth', label: 'Denti corona' },
];
const setupReduction = computed(() => {
  const pinion = Number(setupKartValues.value.pinion_teeth), crown = Number(setupKartValues.value.crown_teeth);
  return pinion > 0 && crown > 0 ? `${(crown / pinion).toFixed(3)}:1` : '—';
});

const mediaTitle = ref('');
const mediaDescription = ref('');

const lapRecords = computed(() => quick.value.filter(item => item.kind === 'lap_time' && item.value_ms != null));
const sessionRecords = computed(() => quick.value.filter(item => item.kind === 'session'));
const recentQuick = computed(() => quick.value.slice(0, 8));
const firstName = computed(() => session.value?.user?.name?.split(' ')[0] || 'pilota');
const cloudLabel = computed(() => syncing.value ? 'Invio in corso…' : pending.value ? `${pending.value} da inviare` : session.value?.cloudEnabled ? 'Cloud attivo' : 'Locale');
const timingOptions = computed(() => [
  ...sessionRecords.value.filter(record => record.sync_state === 'pending').map(record => ({
    value: `local:${record.id}`, label: `Da inviare · ${formatDate(record.occurred_at)}`,
  })),
  ...snapshot.value.sessions.map(item => ({ value: `remote:${item.id}`, label: `${item.vehicle || 'Sessione'} · ${formatDate(item.started_at)}` })),
]);

function recordStatus(record: QuickRecord): string {
  return record.sync_state === 'synced' ? 'Sincronizzato' : record.sync_state === 'pending' ? 'Da inviare' : 'Salvato sul dispositivo';
}

function resetTeamSelection(): void {
  timingSession.value = ''; sessionConfigurationId.value = null; sessionEventId.value = null;
  sessionEntryId.value = null; selectedEventId.value = null; setupVehicleId.value = null;
  workOrderScheduleId.value = null; setupKartValues.value = {}; tyrePressures.value = {};
  noteBody.value = ''; sessionNotes.value = ''; timingNotes.value = ''; timingVehicle.value = '';
}
const currentEvent = computed<EventItem | null>(() => snapshot.value.events.find(event => event.id === selectedEventId.value) || null);
const selectedSessionEvent = computed<EventItem | null>(() => snapshot.value.events.find(event => event.id === sessionEventId.value) || null);
const moreSelected = computed(() => !['home', 'timer', 'sessions', 'garage'].includes(active.value));
const sky = computed(() => weatherSky(weather.value?.symbol_code ?? 'cloudy'));

function weatherIcon(symbolCode: string): string {
  const condition = weatherSky(symbolCode);
  if (condition === 'storm') return thunderstormOutline;
  if (condition === 'rain') return rainyOutline;
  if (condition === 'snow') return snowOutline;
  if (condition === 'clear') return sunnyOutline;
  return symbolCode.includes('partly') ? partlySunnyOutline : cloudyOutline;
}

async function updateWeatherFromGps(): Promise<void> {
  if (weatherLoading.value) return;
  if (!weatherOnline.value) {
    weatherError.value = 'Sei offline. L’ultima previsione salvata rimane consultabile, ma non può essere aggiornata.';
    return;
  }
  weatherLoading.value = true;
  weatherError.value = '';
  try {
    weatherStage.value = 'Ricerca posizione GPS…';
    const coordinates = await readDeviceCoordinates();
    weatherStage.value = 'Aggiornamento previsioni…';
    const latest = await loadDeviceWeather(coordinates.latitude, coordinates.longitude);
    weather.value = latest;
    try {
      if (webPreview) await saveWebWeather(latest);
      else await saveNativeWeather(latest);
      weatherCacheState.value = 'saved';
      weatherStorageError.value = '';
    } catch {
      weatherCacheState.value = 'volatile';
      weatherStorageError.value = 'Meteo aggiornato, ma non salvato sul dispositivo: potrebbe sparire alla chiusura dell’app.';
    }
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Non è stato possibile aggiornare il meteo.';
    weatherError.value = `${message}${weather.value ? ' I dati precedentemente visualizzati rimangono disponibili.' : ''}`;
  } finally {
    weatherStage.value = '';
    weatherLoading.value = false;
  }
}

async function restoreSavedWeather(): Promise<void> {
  try {
    const saved = webPreview ? await readWebWeather() : await readNativeWeather();
    if (saved) {
      weather.value = saved;
      weatherCacheState.value = 'cached';
    }
  } catch {
    weatherStorageError.value = 'Archivio meteo offline non accessibile. I dati potrebbero non essere disponibili alla riapertura.';
  }
}

async function onSessionStorageChange(event: StorageEvent): Promise<void> {
  if (event.key !== 'pitmetric.pwa.session.v1') return;
  pauseTimer();
  resetTeamSelection();
  session.value = await restoreSession();
  await refreshLocal(false).catch(() => { actionMessage.value = 'Archivio offline non accessibile.'; });
}

function onBrowserConnectivityChange(event: Event): void {
  weatherOnline.value = event.type === 'online';
}

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
  return `${formatDate(item.occurred_at)} · ${recordStatus(item)}`;
}

async function saveLocalRecord(action: () => Promise<unknown>): Promise<boolean> {
  try {
    await action();
    return true;
  } catch {
    actionMessage.value = 'Salvataggio offline non riuscito: verifica lo spazio disponibile e le impostazioni del browser.';
    return false;
  }
}

async function refreshLocal(preferCloud = false) {
  [gallery.value, quick.value, pending.value, pendingOps.value, snapshot.value] = await Promise.all([
    galleryItems(), quickRecords(), pendingCount(), pendingOperations(), cachedSnapshot()
  ]);
  if (preferCloud && session.value?.cloudEnabled) snapshot.value = await refreshSnapshotFromCloud();
}

async function submitAuth() {
  if (authBusy.value || syncing.value) return;
  authBusy.value = true;
  authError.value = '';
  try {
    session.value = authMode.value === 'login'
      ? await login(email.value, password.value)
      : await register(name.value, email.value, password.value);
    password.value = '';
    resetTeamSelection();
    await refreshLocal(false);
    if (session.value?.cloudEnabled) await forceSync();
    else actionMessage.value = 'Account collegato. Il cloud non è abilitato: i dati restano sul dispositivo.';
  } catch (error) {
    authError.value = error instanceof Error ? error.message : 'Accesso non riuscito.';
  } finally { authBusy.value = false; }
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
  const [targetKind, targetId] = timingSession.value.split(':');
  if (!(await saveLocalRecord(() => addQuickRecord('lap_time', timingLabel.value || 'Tempo sul giro', {
    vehicle: timingVehicle.value || null,
    notes: timingNotes.value || null,
    source,
    ...(targetKind === 'remote' ? { session_id: Number(targetId) } : targetKind === 'local' ? { session_client_id: targetId } : {}),
  }, value)))) return;
  quick.value = await quickRecords();
  pending.value = await pendingCount();
  pendingOps.value = await pendingOperations();
  actionMessage.value = `Tempo salvato: ${formatLapTime(value)}. ${timingSession.value && webPreview ? 'Da inviare al prossimo login o con Sincronizza ora.' : 'Salvato sul dispositivo.'}`;
}

async function saveTyres(): Promise<void> {
  if (!timingSession.value) {
    actionMessage.value = 'Seleziona la sessione a cui appartengono le pressioni.';
    return;
  }
  const pressures = Object.entries(tyrePressures.value).filter(([, value]) => value !== null && value !== '');
  if (!pressures.length || pressures.some(([, value]) => !Number.isFinite(Number(value)) || Number(value) < 0.1 || Number(value) > 10)) {
    actionMessage.value = 'Inserisci almeno una pressione tra 0,1 e 10 bar.';
    return;
  }
  const [kind, id] = timingSession.value.split(':');
  if (!(await saveLocalRecord(() => addQuickRecord('tyre_reading', tyrePhase.value === 'cold' ? 'Pressioni a freddo' : 'Pressioni a caldo', {
    ...(kind === 'remote' ? { session_id: Number(id) } : { session_client_id: id }),
    phase: tyrePhase.value, measured_at: new Date().toISOString(),
    ...Object.fromEntries(pressures.map(([corner, value]) => [`pressure_${corner}`, Number(value)])),
  })))) return;
  await refreshLocal(false);
  tyrePressures.value = {};
  actionMessage.value = 'Pressioni salvate sul dispositivo e da inviare al team.';
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
  if (value === null || value < 1 || value > 3600000) {
    actionMessage.value = 'Inserisci un tempo come 1:23.456 oppure 83.456.';
    return;
  }
  await storeLap(value, 'manual');
  manualLap.value = '';
}

async function saveSession() {
  const bestLapMs = parseLapTime(sessionBestLap.value);
  if (!Number.isFinite(Date.parse(sessionStartedAt.value)) || (sessionBestLap.value.trim() && (bestLapMs === null || bestLapMs < 1))) {
    actionMessage.value = 'Controlla data e miglior giro della sessione.';
    return;
  }
  if (sessionEventId.value && !sessionEntryId.value) {
    actionMessage.value = 'Seleziona anche il pilota / iscrizione del weekend.';
    return;
  }
  const common = {
    session_type: sessionType.value,
    started_at: new Date(sessionStartedAt.value).toISOString(),
    completed_laps: sessionLaps.value,
    duration_minutes: sessionDuration.value,
    best_lap_ms: bestLapMs,
    notes: sessionNotes.value || null,
    event_id: sessionEventId.value,
    event_entry_id: sessionEntryId.value,
    configuration_version_id: sessionConfigurationId.value,
  };

  if (!(await saveLocalRecord(() => addQuickRecord('session', 'Sessione', common, bestLapMs, common.started_at)))) return;
  if (bestLapMs !== null && !webPreview) {
    if (!(await saveLocalRecord(() => addQuickRecord('lap_time', 'Best lap sessione', { source: 'session', notes: sessionNotes.value || null }, bestLapMs, common.started_at)))) {
      actionMessage.value = 'Sessione salvata, ma non è stato possibile registrare il miglior giro.';
      return;
    }
  }

  if (!webPreview && sessionConfigurationId.value) {
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
  pendingOps.value = await pendingOperations();
  const savedSession = quick.value.find(item => item.kind === 'session');
  if (savedSession?.sync_state === 'pending') timingSession.value = `local:${savedSession.id}`;
  sessionLaps.value = null;
  sessionDuration.value = null;
  sessionBestLap.value = '';
  sessionNotes.value = '';
  actionMessage.value = savedSession?.sync_state === 'pending' ? 'Sessione salvata sul dispositivo e da inviare al prossimo login. Puoi già collegarle i tempi.' : 'Sessione salvata sul dispositivo. Per inviarla al team, accedi e scegli una configurazione prima di registrarla.';
}

async function savePitNote() {
  if (!noteBody.value.trim()) return;
  if (!(await saveLocalRecord(() => addQuickRecord('note', noteBody.value.trim().slice(0, 52), {
    kind: noteKind.value,
    body: noteBody.value.trim(),
    event_id: selectedEventId.value,
  })))) return;
  quick.value = await quickRecords();
  pending.value = await pendingCount();
  pendingOps.value = await pendingOperations();
  noteBody.value = '';
  actionMessage.value = selectedEventId.value && session.value?.cloudEnabled ? 'Nota salvata e da inviare al team.' : 'Nota salvata sul dispositivo. Per inviarla al team seleziona un weekend.';
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
  if (!(await saveLocalRecord(() => addQuickRecord('maintenance', workOrderTitle.value.trim(), {
    maintenance_schedule_id: workOrderScheduleId.value,
    priority: workOrderPriority.value,
    notes: workOrderNotes.value || null,
  })))) return;
  quick.value = await quickRecords();
  workOrderTitle.value = ''; workOrderNotes.value = '';
  actionMessage.value = 'Promemoria manutenzione salvato.';
}

async function saveSetup() {
  if (!setupName.value.trim()) return;
  const values: Record<string, number | string> = {};
  const map: Array<[string, number | null]> = [
    ['tyre_pressure_fl', setupTyreFl.value], ['tyre_pressure_fr', setupTyreFr.value],
    ['tyre_pressure_rl', setupTyreRl.value], ['tyre_pressure_rr', setupTyreRr.value],
    ['ride_height_front_mm', setupRideFront.value], ['ride_height_rear_mm', setupRideRear.value],
    ['brake_bias_pct', setupBrakeBias.value]
  ];
  map.forEach(([key, value]) => { if (value !== null && value !== undefined) values[key] = Number(value); });
  for (const [key, value] of Object.entries(setupKartValues.value)) {
    if (value !== null && value !== '') values[key] = Number(value);
  }
  values.rear_width_mode = setupKartValues.value.rear_axle_length_mm ? 'both' : 'track';
  if (Boolean(values.pinion_teeth) !== Boolean(values.crown_teeth)) {
    actionMessage.value = 'Inserisci sia i denti del pignone sia quelli della corona.';
    return;
  }
  if (!(await saveLocalRecord(() => addQuickRecord('setup', setupName.value.trim(), {
    vehicle_id: setupVehicleId.value,
    description: setupDescription.value || null,
    values,
  })))) return;
  quick.value = await quickRecords();
  pending.value = await pendingCount();
  pendingOps.value = await pendingOperations();
  setupName.value = ''; setupDescription.value = '';
  actionMessage.value = setupVehicleId.value && session.value?.cloudEnabled ? 'Setup salvato e da inviare al team.' : 'Setup salvato sul dispositivo. Per inviarlo al team seleziona un kart.';
}

async function addPhoto() {
  await addGalleryPhoto(mediaTitle.value.trim() || 'Foto trackside', mediaDescription.value.trim());
  mediaTitle.value = ''; mediaDescription.value = '';
  await refreshLocal();
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
  if (!window.confirm('Eliminare questa registrazione locale?')) return;
  if (!(await saveLocalRecord(() => deleteQuickRecord(id)))) return;
  quick.value = await quickRecords();
}
async function forceSync() {
  if (syncing.value) return;
  if (!session.value?.cloudEnabled) {
    active.value = 'account';
    actionMessage.value = 'Il cloud è opzionale: collega un account solo se vuoi sincronizzare.';
    return;
  }
  syncing.value = true;
  try {
    const result = await syncNow();
    session.value = await restoreSession();
    await refreshLocal(false);
    if (timingSession.value.startsWith('local:')) {
      const saved = quick.value.find(item => item.id === timingSession.value.slice(6));
      if (saved?.remote_id) timingSession.value = `remote:${saved.remote_id}`;
    }
    actionMessage.value = result.pending ? `${result.pending} registrazioni ancora da inviare.` : result.synced > 0 ? `${result.synced} elementi sincronizzati.` : 'Dati già aggiornati.';
  } catch (error) {
    actionMessage.value = error instanceof Error ? error.message : 'Invio non riuscito. I dati restano sul dispositivo.';
    await refreshLocal(false).catch(() => undefined);
  } finally { syncing.value = false; }
}
async function signOut() {
  if (syncing.value || authBusy.value) return;
  await logout();
  session.value = null;
  resetTeamSelection();
  await refreshLocal(false);
  actionMessage.value = 'Account scollegato. I dati locali restano sul dispositivo.';
  active.value = 'home';
}

onMounted(async () => {
  if (webPreview) {
    window.addEventListener('online', onBrowserConnectivityChange);
    window.addEventListener('offline', onBrowserConnectivityChange);
    window.addEventListener('storage', onSessionStorageChange);
    if ('serviceWorker' in navigator) {
      void navigator.serviceWorker.ready.then(() => { offlineShellReady.value = true; });
    }
  } else {
    weatherOnline.value = (await Network.getStatus().catch(() => ({ connected: true }))).connected;
    const listener = await Network.addListener('networkStatusChange', status => {
      weatherOnline.value = status.connected;
    }).catch(() => null);
    if (listener) stopWeatherNetwork = () => listener.remove();
  }
  await restoreSavedWeather();
  if (webPreview) {
    try {
      session.value = await restoreSession();
      await refreshLocal(false);
    } catch {
      actionMessage.value = 'Archivio offline non disponibile. Controlla le impostazioni di Safari prima di registrare dati.';
    }
    ready.value = true;
    return;
  }
  await bootLocalStore();
  session.value = await restoreSession();
  biometric.value = await biometricAvailable().catch(() => false);
  await refreshLocal(Boolean(session.value?.cloudEnabled));
  stopAutoSync = await startAutoSync(() => refreshLocal(false));
  ready.value = true;
});
onUnmounted(async () => {
  pauseTimer();
  window.removeEventListener('online', onBrowserConnectivityChange);
  window.removeEventListener('offline', onBrowserConnectivityChange);
  window.removeEventListener('storage', onSessionStorageChange);
  await stopWeatherNetwork?.();
  await stopAutoSync?.();
});
</script>

<template>
  <ion-app>
    <div v-if="!ready" class="splash"><div class="mark" /><ion-spinner name="crescent" /></div>

    <ion-page v-else>
      <ion-content :fullscreen="true" class="app-content">
        <header class="app-topbar">
          <div class="brand-mini"><div class="mark" /><span class="wordmark">PitMetric</span></div>
          <button class="sync-pill" type="button" @click="forceSync">
            <ion-icon :icon="session?.cloudEnabled ? cloudDoneOutline : cloudOfflineOutline" />
            <span>{{ cloudLabel }}</span>
          </button>
        </header>

        <div v-if="webPreview" class="pwa-beta-banner" role="status">
          <strong>{{ session?.workspace?.name || 'PWA' }} · {{ offlineShellReady ? 'Offline pronta' : 'Preparazione offline… resta online' }}</strong>
          <span>{{ session?.cloudEnabled ? 'Salva offline. Invia al login o con Sincronizza ora. Nessun invio in background.' : 'Accedi da Altro → Account per scaricare i dati del team. Le registrazioni senza account restano separate sul dispositivo.' }}</span>
        </div>

        <button v-if="actionMessage" class="toast" type="button" @click="actionMessage = ''">{{ actionMessage }}</button>

        <main v-if="active === 'home'" class="screen dashboard-screen">
          <section class="dashboard-hero">
            <div>
              <p class="eyebrow">RACE DASHBOARD</p>
              <h1>{{ bestLap !== null ? formatLapTime(bestLap) : 'Pronto a girare' }}</h1>
              <p>{{ bestLap !== null ? 'Personal best locale' : `Ciao ${firstName}. Inizia dal primo giro, senza configurare nulla.` }}</p>
            </div>
            <button class="hero-start" type="button" @click="active = 'timer'"><ion-icon :icon="playOutline" />START</button>
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
            <button class="quick-card" type="button" @click="active = 'sessions'"><span class="quick-icon"><ion-icon :icon="flagOutline" /></span><strong>Sessione</strong><small>Senza gerarchie obbligatorie</small><ion-icon class="chev" :icon="chevronForwardOutline" /></button>
            <button class="quick-card" type="button" @click="active = 'pit'"><span class="quick-icon"><ion-icon :icon="speedometerOutline" /></span><strong>Nota</strong><small>Trackside in 2 tocchi</small><ion-icon class="chev" :icon="chevronForwardOutline" /></button>
            <button class="quick-card weather-quick-card" type="button" @click="active = 'weather'"><span class="quick-icon"><ion-icon :icon="partlySunnyOutline" /></span><strong>Meteo GPS</strong><small>Previsioni in pista</small><ion-icon class="chev" :icon="chevronForwardOutline" /></button>
          </section>

          <section class="panel activity-panel">
            <div class="panel-head"><div><p class="eyebrow">ACTIVITY</p><h2>Ultime registrazioni</h2></div><span>{{ quick.length }}</span></div>
            <div v-if="recentQuick.length" class="activity-list">
              <article v-for="item in recentQuick" :key="item.id" class="activity-row">
                <div class="activity-dot"><ion-icon :icon="item.kind === 'lap_time' ? stopwatchOutline : item.kind === 'session' ? flagOutline : item.kind === 'setup' ? settingsOutline : timeOutline" /></div>
                <div><strong>{{ item.kind === 'lap_time' ? formatLapTime(item.value_ms) : item.title }}</strong><span>{{ item.kind === 'lap_time' ? item.title : quickMeta(item) }}</span></div>
                <button v-if="!item.sync_state || item.sync_state === 'local'" type="button" class="icon-button" aria-label="Elimina registrazione locale" @click="removeQuick(item.id)"><ion-icon :icon="trashOutline" /></button>
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
            <ion-select v-if="webPreview && session?.cloudEnabled" v-model="timingSession" label="Sessione a cui inviare i tempi" label-placement="stacked"><ion-select-option value="">Solo sul dispositivo</ion-select-option><ion-select-option v-for="item in timingOptions" :key="item.value" :value="item.value">{{ item.label }}</ion-select-option></ion-select>
            <p v-if="webPreview && session?.cloudEnabled">Seleziona una sessione, anche appena salvata offline, per inviare i giri al team.</p>
            <div class="form-grid two"><ion-input v-model="timingLabel" label="Pista / etichetta" label-placement="stacked" placeholder="Es. Busca"/><ion-input v-model="timingVehicle" label="Mezzo" label-placement="stacked" placeholder="Opzionale"/></div>
            <ion-textarea v-model="timingNotes" label="Note" label-placement="stacked" :auto-grow="true" placeholder="Gomme, meteo, traffico…"/>
          </section>

          <section class="manual-card">
            <div><span>INSERIMENTO MANUALE</span><strong>Hai già il tempo?</strong></div>
            <div class="manual-row"><ion-input v-model="manualLap" inputmode="decimal" label="Tempo manuale" label-placement="stacked" placeholder="1:23.456"/><button type="button" @click="saveManualLap">Salva</button></div>
            <small>Nessuna configurazione, vettura o sessione necessaria.</small>
          </section>

          <section v-if="webPreview && session?.cloudEnabled" class="panel form-panel">
            <div class="panel-head"><div><p class="eyebrow">PRESSIONI KART</p><h2>Prima / dopo la sessione</h2></div></div>
            <p>Usa la sessione selezionata sopra. Le misure rimangono distinte dal setup iniziale.</p>
            <ion-select v-model="tyrePhase" label="Quando hai misurato?" label-placement="stacked"><ion-select-option value="cold">A freddo</ion-select-option><ion-select-option value="hot">A caldo</ion-select-option></ion-select>
            <div class="form-grid two"><ion-input v-for="corner in [{key:'fl',label:'Anteriore sinistra (bar)'},{key:'fr',label:'Anteriore destra (bar)'},{key:'rl',label:'Posteriore sinistra (bar)'},{key:'rr',label:'Posteriore destra (bar)'}]" :key="corner.key" v-model="tyrePressures[corner.key]" type="number" inputmode="decimal" :label="corner.label" label-placement="stacked"/></div>
            <button class="save-button" type="button" @click="saveTyres">Salva pressioni</button>
            <div class="activity-list"><article v-for="reading in quick.filter(item => item.kind === 'tyre_reading').slice(0,4)" :key="reading.id" class="activity-row"><div><strong>{{ reading.title }}</strong><span>{{ recordStatus(reading) }} · {{ formatDate(reading.occurred_at) }}</span></div></article></div>
          </section>
          <section class="panel"><div class="panel-head"><div><p class="eyebrow">LAP HISTORY</p><h2>Ultimi giri</h2></div><span>{{ lapRecords.length }}</span></div>
            <div class="lap-list"><article v-for="(item, index) in lapRecords.slice(0, 12)" :key="item.id" class="lap-list-row"><span class="lap-index">{{ String(lapRecords.length - index).padStart(2,'0') }}</span><strong>{{ formatLapTime(item.value_ms) }}</strong><span>{{ item.title }} · {{ recordStatus(item) }}</span><button v-if="!item.sync_state || item.sync_state === 'local'" type="button" class="icon-button" aria-label="Elimina registrazione locale" @click="removeQuick(item.id)"><ion-icon :icon="trashOutline"/></button></article></div>
          </section>
        </main>

        <main v-else-if="active === 'sessions'" class="screen">
          <section class="screen-title"><div><p class="eyebrow">SESSION LOGGER</p><h1>Nuova sessione</h1><p>Salva prima. Collega configurazione e cloud solo se ti servono.</p></div></section>
          <section class="panel form-panel">
            <div class="form-grid two"><ion-select v-model="sessionType" label="Tipo" label-placement="stacked"><ion-select-option v-for="type in ['practice','qualifying','heat','prefinal','final','race','test']" :key="type" :value="type">{{ type }}</ion-select-option></ion-select><ion-input v-model="sessionStartedAt" type="datetime-local" label="Inizio" label-placement="stacked"/></div>
            <div class="form-grid three"><ion-input v-model.number="sessionLaps" type="number" label="Giri" label-placement="stacked"/><ion-input v-model.number="sessionDuration" type="number" label="Minuti" label-placement="stacked"/><ion-input v-model="sessionBestLap" inputmode="decimal" label="Best lap" label-placement="stacked" placeholder="1:23.456"/></div>
            <ion-textarea v-model="sessionNotes" label="Note" label-placement="stacked" :auto-grow="true"/>
            <details class="advanced"><summary>{{ session?.cloudEnabled ? 'Collega al team per inviare la sessione' : 'Collegamenti opzionali' }}</summary><div class="advanced-body"><ion-select v-model="sessionConfigurationId" label="Configurazione" label-placement="stacked"><ion-select-option :value="null">Nessuna</ion-select-option><ion-select-option v-for="config in snapshot.configurations" :key="config.id" :value="config.id">{{ config.vehicle }} · {{ config.configuration }} v{{ config.version_number }}</ion-select-option></ion-select><ion-select v-model="sessionEventId" label="Weekend" label-placement="stacked" @ion-change="sessionEntryId = null"><ion-select-option :value="null">Nessuno</ion-select-option><ion-select-option v-for="event in snapshot.events" :key="event.id" :value="event.id">{{ event.name }}</ion-select-option></ion-select><ion-select v-if="selectedSessionEvent" v-model="sessionEntryId" label="Iscrizione" label-placement="stacked"><ion-select-option :value="null">Nessuna</ion-select-option><ion-select-option v-for="entry in selectedSessionEvent.entries" :key="entry.id" :value="entry.id">#{{ entry.entry_number || '—' }} · {{ entry.driver || entry.vehicle }}</ion-select-option></ion-select></div></details>
            <button class="save-button" type="button" @click="saveSession"><ion-icon :icon="addOutline"/>Salva sessione</button>
          </section>
          <section v-if="snapshot.sessions.length" class="panel"><div class="panel-head"><h2>Sessioni del team · disponibili offline</h2></div><div class="activity-list"><article v-for="item in snapshot.sessions" :key="item.id" class="activity-row"><div><strong>{{ item.vehicle || 'Sessione' }}</strong><span>{{ formatDate(item.started_at) }} · {{ item.session_type }}</span></div><button class="secondary-button" type="button" @click="timingSession = `remote:${item.id}`; active = 'timer'">Tempi / pressioni</button></article></div></section>
          <section class="panel"><div class="panel-head"><div><p class="eyebrow">LOCAL SESSIONS</p><h2>Storico</h2></div><span>{{ sessionRecords.length }}</span></div><div class="activity-list"><article v-for="item in sessionRecords.slice(0,12)" :key="item.id" class="activity-row"><div class="activity-dot"><ion-icon :icon="flagOutline"/></div><div><strong>{{ String(item.payload.session_type || 'Sessione') }}</strong><span>{{ quickMeta(item) }} · {{ recordStatus(item) }} · {{ item.payload.completed_laps || '—' }} giri</span></div><button v-if="!item.sync_state || item.sync_state === 'local'" type="button" class="icon-button" aria-label="Elimina registrazione locale" @click="removeQuick(item.id)"><ion-icon :icon="trashOutline"/></button></article></div></section>
        </main>

        <main v-else-if="active === 'weather'" class="screen pm-weather-mobile">
          <section class="pm-weather-title">
            <div><p class="eyebrow">TRACKSIDE WEATHER</p><h1>Meteo in pista</h1><p>Previsioni per la tua posizione, senza inserire il circuito.</p></div>
            <span class="pm-weather-gps"><ion-icon :icon="locationOutline" /> GPS</span>
          </section>

          <section v-if="!weatherOnline" class="pm-weather-offline-banner" role="status" aria-live="polite">
            <ion-icon :icon="cloudOfflineOutline" />
            <div><strong>Connessione assente</strong><span>{{ weatherStored ? 'Ultimo meteo salvato disponibile. Per aggiornarlo torna online.' : weather ? 'Il meteo visualizzato non è stato archiviato. Per aggiornarlo torna online.' : 'Nessun dato salvato. Il meteo sarà disponibile quando torni online.' }}</span></div>
          </section>

          <section class="pm-weather-sky" :data-sky="sky">
            <div class="pm-weather-sky-light" aria-hidden="true"></div>
            <div class="pm-weather-sky-top"><span><ion-icon :icon="locationOutline" /> {{ weatherShowingSaved ? 'Ultima posizione rilevata' : 'Posizione GPS' }}</span><span v-if="weather">{{ formatDate(weather.captured_at) }}</span></div>
            <template v-if="weather">
              <div class="pm-weather-main-reading"><ion-icon :icon="weatherIcon(weather.symbol_code)" class="pm-weather-hero-icon" /><div><strong>{{ Math.round(weather.air_temperature_c) }}<sup>°</sup></strong><span>{{ weatherDescription(weather.symbol_code) }}</span></div></div>
              <div class="pm-weather-forecast-label">Previsione MET Norway per {{ formatDate(weather.forecast_at) }}</div>
              <div class="pm-weather-cache-indicator" :data-cache="weatherShowingSaved ? 'cached' : weatherCacheState" role="status">
                <ion-icon :icon="weatherShowingSaved || !weatherStored ? cloudOfflineOutline : cloudDoneOutline" />
                {{ weatherShowingSaved ? 'Dati salvati sul dispositivo, non aggiornati' : weatherStored ? 'Aggiornati e salvati sul dispositivo' : 'Dati non salvati sul dispositivo' }}
              </div>
            </template>
            <template v-else>
              <div class="pm-weather-main-reading pm-weather-empty-reading"><ion-icon :icon="partlySunnyOutline" class="pm-weather-hero-icon" /><div><strong>—<sup>°</sup></strong><span>Meteo pronto quando vuoi</span></div></div>
              <p class="pm-weather-empty-hint">{{ weatherOnline ? 'Consenti la posizione per conoscere temperatura, vento e condizioni della pista.' : 'Quando torna la connessione potrai rilevare il meteo e salvarlo per l’uso offline.' }}</p>
            </template>
            <button class="pm-weather-update" type="button" :disabled="weatherLoading || !weatherOnline" @click="updateWeatherFromGps">
              <ion-spinner v-if="weatherLoading" name="crescent" /><ion-icon v-else :icon="weatherOnline ? locationOutline : cloudOfflineOutline" />
              {{ !weatherOnline ? 'Aggiornamento non disponibile offline' : weatherLoading ? weatherStage : weather ? 'Aggiorna con GPS' : 'Rileva posizione e meteo' }}
            </button>
            <p v-if="weatherError" class="pm-weather-error" role="alert">{{ weatherError }}</p>
          </section>

          <p v-if="weatherStorageError" class="pm-weather-storage-error" role="alert">{{ weatherStorageError }}</p>

          <template v-if="weather">
            <section class="pm-weather-metrics" aria-label="Dettagli meteo">
              <article><ion-icon :icon="cloudOutline" /><span>Umidità</span><strong>{{ weather.humidity_pct == null ? '—' : `${weather.humidity_pct}%` }}</strong></article>
              <article><ion-icon :icon="locationOutline" /><span>Vento</span><strong>{{ weather.wind_speed_kmh == null ? '—' : `${Math.round(weather.wind_speed_kmh)} km/h` }}</strong><small>{{ windDirection(weather.wind_direction_deg) }}</small></article>
              <article><ion-icon :icon="speedometerOutline" /><span>Pressione</span><strong>{{ weather.pressure_hpa == null ? '—' : `${Math.round(weather.pressure_hpa)} hPa` }}</strong></article>
              <article><ion-icon :icon="rainyOutline" /><span>Pioggia · 1h</span><strong>{{ weather.precipitation_next_hour_mm == null ? '—' : `${weather.precipitation_next_hour_mm} mm` }}</strong></article>
              <article><ion-icon :icon="cloudyOutline" /><span>Nuvolosità</span><strong>{{ weather.cloud_cover_pct == null ? '—' : `${Math.round(weather.cloud_cover_pct)}%` }}</strong></article>
              <article><ion-icon :icon="sunnyOutline" /><span>Temperatura</span><strong>{{ weather.air_temperature_c.toFixed(1) }} °C</strong></article>
            </section>
            <section v-if="weather.hourly.length" class="pm-weather-hourly">
              <div class="pm-weather-section-heading"><h2>{{ weatherShowingSaved ? 'Previsioni salvate' : 'Prossime ore' }}</h2><span>{{ weatherShowingSaved ? 'Non aggiornate' : 'Previsione oraria' }}</span></div>
              <div class="pm-weather-hours"><article v-for="hour in weather.hourly" :key="hour.at"><span>{{ weatherHour(hour.at) }}</span><ion-icon :icon="weatherIcon(hour.symbol_code)" /><strong>{{ Math.round(hour.temperature_c) }}°</strong></article></div>
            </section>
          </template>

          <p class="pm-weather-disclaimer">La posizione viene richiesta solo quando tocchi il pulsante. Le ultime previsioni vengono conservate sul dispositivo e restano consultabili offline, ma non rappresentano il meteo attuale né misure dei sensori del kart. Dati: <a href="https://www.met.no/en/free-meteorological-data" target="_blank" rel="noopener noreferrer">MET Norway (CC BY 4.0)</a>. Per aggiornarle serve internet.</p>
        </main>

        <main v-else-if="webPreview && active === 'gallery'" class="screen">
          <section class="screen-title"><div><p class="eyebrow">PWA BETA</p><h1>Funzione in preparazione</h1><p>La galleria offline sarà disponibile in un aggiornamento successivo.</p></div></section>
          <section class="panel form-panel">
            <p>Per le fotografie usa la galleria nel gestionale PitMetric. I dati del team, le sessioni, i tempi e le note sono disponibili qui dopo il login.</p>
            <button class="save-button" type="button" @click="active = 'home'">Torna alla dashboard</button>
          </section>
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

        <main v-else-if="active === 'setup'" class="screen"><section class="screen-title"><div><p class="eyebrow">SETUP</p><h1>Setup kart</h1><p>Seleziona un kart per inviarlo al team.</p></div></section><section class="panel form-panel"><ion-select v-model="setupVehicleId" label="Mezzo opzionale" label-placement="stacked"><ion-select-option :value="null">Nessuno</ion-select-option><ion-select-option v-for="vehicle in snapshot.vehicles" :key="vehicle.id" :value="vehicle.id">{{ vehicle.name }}</ion-select-option></ion-select><ion-input v-model="setupName" label="Nome setup" label-placement="stacked"/><ion-textarea v-model="setupDescription" label="Descrizione" label-placement="stacked" :auto-grow="true"/><div class="form-grid four"><ion-input v-model.number="setupTyreFl" type="number" label="FL bar" label-placement="stacked"/><ion-input v-model.number="setupTyreFr" type="number" label="FR bar" label-placement="stacked"/><ion-input v-model.number="setupTyreRl" type="number" label="RL bar" label-placement="stacked"/><ion-input v-model.number="setupTyreRr" type="number" label="RR bar" label-placement="stacked"/></div><div class="form-grid two"><ion-input v-model.number="setupRideFront" type="number" label="Altezza ant." label-placement="stacked"/><ion-input v-model.number="setupRideRear" type="number" label="Altezza post." label-placement="stacked"/></div><ion-input v-model.number="setupBrakeBias" type="number" label="Brake bias %" label-placement="stacked"/><div class="form-grid two"><ion-input v-for="field in setupKartFields" :key="field.key" v-model="setupKartValues[field.key]" type="number" :label="field.label" label-placement="stacked"/></div><p>Riduzione pignone / corona: {{ setupReduction }}</p><button class="save-button" type="button" @click="saveSetup">Salva setup</button></section></main>

        <main v-else-if="active === 'gallery'" class="screen"><section class="screen-title"><div><p class="eyebrow">MEDIA</p><h1>Galleria</h1></div></section><section class="panel form-panel"><ion-input v-model="mediaTitle" label="Titolo opzionale" label-placement="stacked"/><ion-textarea v-model="mediaDescription" label="Descrizione" label-placement="stacked" :auto-grow="true"/><div class="media-actions"><button type="button" @click="addPhoto"><ion-icon :icon="cameraOutline"/>Fotocamera</button><button type="button" @click="addMedia"><ion-icon :icon="videocamOutline"/>Foto / video</button></div></section><div class="gallery-grid"><article v-for="item in gallery" :key="item.local_id" class="media-card"><video v-if="item.media_type === 'video'" :src="item.preview_uri || item.local_uri" controls playsinline preload="metadata"/><img v-else-if="item.preview_uri || item.local_uri.startsWith('http')" :src="item.preview_uri || item.local_uri" :alt="item.title"/><div v-else class="media-placeholder"><ion-icon :icon="imagesOutline"/></div><div><strong>{{ item.title }}</strong><span>{{ item.sync_state }}</span></div></article></div></main>

        <main v-else-if="active === 'account'" class="screen"><section class="screen-title"><div><p class="eyebrow">ACCOUNT & CLOUD</p><h1>{{ session ? session.user?.name : 'Opzionale' }}</h1><p>{{ session ? session.user?.email : 'Usa PitMetric in locale quanto vuoi. Accedi solo per sincronizzare.' }}</p></div></section><section v-if="!session" class="panel auth-panel"><div class="segmented"><button :class="{active: authMode === 'login'}" type="button" @click="authMode='login'">Accedi</button><button :class="{active: authMode === 'register'}" type="button" @click="authMode='register'">Registrati</button></div><ion-input v-if="authMode === 'register'" v-model="name" label="Nome" label-placement="stacked"/><ion-input v-model="email" type="email" label="Email" label-placement="stacked"/><ion-input v-model="password" type="password" label="Password" label-placement="stacked"/><p v-if="authError" class="error">{{ authError }}</p><button class="save-button" type="button" :disabled="authBusy || syncing" @click="submitAuth">{{ authBusy ? 'Accesso in corso…' : authMode === 'login' ? 'Accedi' : 'Crea account' }}</button><button v-if="biometric && authMode === 'login'" class="secondary-button" type="button" @click="unlock"><ion-icon :icon="personCircleOutline"/>Sblocco biometrico</button></section><section v-else class="panel account-panel"><div class="account-status"><ion-icon :icon="session.cloudEnabled ? cloudDoneOutline : cloudOfflineOutline"/><div><strong>{{ session.cloudEnabled ? 'Cloud attivo' : 'Account collegato' }}</strong><span>{{ session.workspace?.name }} · {{ pending }} da inviare</span></div></div><button class="secondary-button" type="button" :disabled="syncing || authBusy" @click="forceSync"><ion-icon :icon="syncOutline"/>Sincronizza ora</button><button class="danger-button" type="button" :disabled="syncing || authBusy" @click="signOut"><ion-icon :icon="logOutOutline"/>Scollega account</button><p v-if="webPreview">Apri la PWA online prima di andare in pista. Salva offline; al ritorno della rete premi Sincronizza ora oppure accedi nuovamente. La sola riapertura non invia dati.</p><p v-if="snapshot.synced_at">Dati del team scaricati: {{ formatDate(snapshot.synced_at) }}</p><div v-if="pendingOps.length" class="queue"><span v-for="item in pendingOps.slice(0,8)" :key="item.local_id">{{ item.operation }} · Da inviare{{ item.attempts ? ' · Invio da riprovare' : '' }}</span></div></section></main>

        <main v-else class="screen">
          <section class="screen-title"><div><p class="eyebrow">TOOLS</p><h1>Altro</h1></div></section>
          <section class="tools-grid"><button type="button" @click="active='weather'"><ion-icon :icon="partlySunnyOutline"/><strong>Meteo GPS</strong><span>Previsioni in pista</span></button><button type="button" @click="active='pit'"><ion-icon :icon="speedometerOutline"/><strong>Nota rapida</strong><span>Trackside</span></button><button type="button" @click="active='setup'"><ion-icon :icon="settingsOutline"/><strong>Setup</strong><span>Assetto libero</span></button><button type="button" @click="active='maintenance'"><ion-icon :icon="buildOutline"/><strong>Manutenzione</strong><span>Promemoria</span></button><button type="button" @click="active='gallery'"><ion-icon :icon="imagesOutline"/><strong>Galleria</strong><span>Foto + video</span></button><button type="button" @click="active='weekend'"><ion-icon :icon="flagOutline"/><strong>Weekend</strong><span>Cloud opzionale</span></button><button type="button" @click="active='account'"><ion-icon :icon="personCircleOutline"/><strong>Account</strong><span>{{ session ? 'Collegato' : 'Opzionale' }}</span></button></section>
        </main>
      </ion-content>

      <ion-tab-bar slot="bottom" class="bottom-nav">
        <ion-tab-button :selected="active === 'home'" @click="active='home'"><ion-icon :icon="homeOutline"/><ion-label>Home</ion-label></ion-tab-button>
        <ion-tab-button :selected="active === 'timer'" @click="active='timer'"><ion-icon :icon="stopwatchOutline"/><ion-label>Timer</ion-label></ion-tab-button>
        <ion-tab-button :selected="active === 'sessions'" @click="active='sessions'"><ion-icon :icon="flagOutline"/><ion-label>Sessioni</ion-label></ion-tab-button>
        <ion-tab-button :selected="active === 'garage'" @click="active='garage'"><ion-icon :icon="carSportOutline"/><ion-label>Garage</ion-label></ion-tab-button>
        <ion-tab-button :selected="moreSelected" @click="active='more'"><ion-icon :icon="ellipsisHorizontalOutline"/><ion-label>Altro</ion-label></ion-tab-button>
      </ion-tab-bar>
    </ion-page>
  </ion-app>
</template>

