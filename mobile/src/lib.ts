import { Capacitor } from '@capacitor/core';
import { Network } from '@capacitor/network';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { CapacitorSQLite, SQLiteConnection, SQLiteDBConnection } from '@capacitor-community/sqlite';
import { FilePicker } from '@capawesome/capacitor-file-picker';
import { BiometricAuth, AndroidBiometryStrength } from '@aparajita/capacitor-biometric-auth';
import { SecureStorage } from '@aparajita/capacitor-secure-storage';

export type SessionState = {
  authenticated: boolean;
  cloudEnabled: boolean;
  canWrite?: boolean;
  user?: { id: number; name: string; email: string };
  workspace?: { id: number; name: string } | null;
};

export type GalleryItem = {
  local_id: string;
  remote_id?: number | null;
  title: string;
  description: string;
  media_type: 'image' | 'video';
  mime_type?: string | null;
  original_name?: string | null;
  size_bytes?: number | null;
  local_uri: string;
  preview_uri?: string;
  created_at: string;
  sync_state: 'local' | 'pending' | 'synced' | 'error';
};

export type EventEntryItem = {
  id: number;
  entry_number?: string | null;
  vehicle_id: number;
  vehicle?: string | null;
  driver?: string | null;
  configuration_version_id?: number | null;
};

export type ScheduleItem = {
  id: number;
  event_entry_id?: number | null;
  session_id?: number | null;
  label: string;
  session_type?: string | null;
  starts_at?: string | null;
  duration_minutes?: number | null;
  status: string;
  notes?: string | null;
};

export type EventItem = {
  id: number;
  name: string;
  championship?: string | null;
  round_label?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  status: string;
  notes?: string | null;
  entries: EventEntryItem[];
  schedule: ScheduleItem[];
};

export type VehicleItem = {
  id: number;
  name: string;
  category: string;
  manufacturer?: string | null;
  model?: string | null;
  year?: number | null;
  identifier?: string | null;
  status: string;
  notes?: string | null;
};

export type SessionItem = {
  id: number;
  event_id?: number | null;
  event_entry_id?: number | null;
  vehicle_id?: number | null;
  vehicle?: string | null;
  configuration_version_id?: number | null;
  session_type: string;
  started_at?: string | null;
  completed_laps?: number | null;
  duration_seconds?: number | null;
  status: string;
  notes?: string | null;
};

export type ConfigurationItem = {
  id: number;
  version_number: number;
  configuration_id: number;
  configuration: string;
  vehicle_id: number;
  vehicle?: string | null;
  notes?: string | null;
};

export type MaintenanceScheduleItem = {
  id: number;
  name: string;
  interval_value: number;
  warning_value?: number | null;
  is_active: boolean;
  notes?: string | null;
};

export type WorkOrderItem = {
  id: number;
  maintenance_schedule_id?: number | null;
  schedule?: string | null;
  title: string;
  priority: string;
  status: string;
  due_at?: string | null;
  notes?: string | null;
};

export type SetupItem = {
  id: number;
  vehicle_id: number;
  vehicle?: string | null;
  name: string;
  description?: string | null;
  values?: Record<string, string | number | null> | null;
  status: string;
};

export type SetupField = { label: string; unit: string; group: string };

export type MobileSnapshot = {
  workspace?: { id: number; name: string } | null;
  events: EventItem[];
  vehicles: VehicleItem[];
  sessions: SessionItem[];
  configurations: ConfigurationItem[];
  maintenance_schedules: MaintenanceScheduleItem[];
  work_orders: WorkOrderItem[];
  setups: SetupItem[];
  setup_fields: Record<string, SetupField>;
  synced_at?: string | null;
};

export type PendingOperation = {
  local_id: string;
  operation: string;
  payload: Record<string, unknown>;
  created_at: string;
  attempts: number;
};

const API_BASE = (import.meta.env.VITE_PITMETRIC_API_URL || 'https://pitmetric.it/api/mobile').replace(/\/$/, '');
const sqlite = new SQLiteConnection(CapacitorSQLite);
let db: SQLiteDBConnection | null = null;

const emptySnapshot = (): MobileSnapshot => ({
  workspace: null,
  events: [],
  vehicles: [],
  sessions: [],
  configurations: [],
  maintenance_schedules: [],
  work_orders: [],
  setups: [],
  setup_fields: {},
  synced_at: null
});

async function database(): Promise<SQLiteDBConnection> {
  if (db) return db;
  db = await sqlite.createConnection('pitmetric_mobile', false, 'no-encryption', 2, false);
  await db.open();
  await db.execute(`
    CREATE TABLE IF NOT EXISTS gallery_items (
      local_id TEXT PRIMARY KEY,
      remote_id INTEGER NULL,
      title TEXT NOT NULL,
      description TEXT NOT NULL DEFAULT '',
      local_uri TEXT NOT NULL,
      created_at TEXT NOT NULL,
      sync_state TEXT NOT NULL DEFAULT 'pending'
    );
    CREATE TABLE IF NOT EXISTS outbox (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      operation TEXT NOT NULL,
      entity TEXT NOT NULL,
      local_id TEXT NOT NULL,
      payload TEXT NOT NULL,
      created_at TEXT NOT NULL,
      attempts INTEGER NOT NULL DEFAULT 0
    );
    CREATE TABLE IF NOT EXISTS mobile_snapshot (
      id INTEGER PRIMARY KEY,
      payload TEXT NOT NULL,
      updated_at TEXT NOT NULL
    );
  `);

  await db.execute("ALTER TABLE gallery_items ADD COLUMN media_type TEXT NOT NULL DEFAULT 'image'").catch(() => undefined);
  await db.execute('ALTER TABLE gallery_items ADD COLUMN mime_type TEXT NULL').catch(() => undefined);
  await db.execute('ALTER TABLE gallery_items ADD COLUMN original_name TEXT NULL').catch(() => undefined);
  await db.execute('ALTER TABLE gallery_items ADD COLUMN size_bytes INTEGER NULL').catch(() => undefined);

  return db;
}

export async function bootLocalStore(): Promise<void> {
  await database();
}

async function token(): Promise<string | null> {
  try { return (await SecureStorage.get('pitmetric.mobile.token')) as string | null; } catch { return null; }
}

async function api(path: string, init: RequestInit = {}): Promise<Response> {
  const bearer = await token();
  const headers = new Headers(init.headers || {});
  headers.set('Accept', 'application/json');
  if (!(init.body instanceof FormData)) headers.set('Content-Type', 'application/json');
  if (bearer) headers.set('Authorization', `Bearer ${bearer}`);
  return fetch(`${API_BASE}${path}`, { ...init, headers });
}

export async function login(email: string, password: string): Promise<SessionState> {
  const response = await api('/login', { method: 'POST', body: JSON.stringify({ email, password, device_name: Capacitor.getPlatform() }) });
  if (!response.ok) throw new Error(response.status === 422 ? 'Credenziali non valide.' : 'Accesso non riuscito.');
  const data = await response.json();
  await SecureStorage.set('pitmetric.mobile.token', data.token);
  await SecureStorage.set('pitmetric.mobile.session', data.session);
  await SecureStorage.set('pitmetric.mobile.biometric-ready', true);
  return data.session as SessionState;
}

export async function register(name: string, email: string, password: string): Promise<SessionState> {
  const response = await api('/register', { method: 'POST', body: JSON.stringify({ name, email, password, password_confirmation: password, device_name: Capacitor.getPlatform() }) });
  if (!response.ok) throw new Error('Registrazione non riuscita. Controlla i dati inseriti.');
  const data = await response.json();
  await SecureStorage.set('pitmetric.mobile.token', data.token);
  await SecureStorage.set('pitmetric.mobile.session', data.session);
  await SecureStorage.set('pitmetric.mobile.biometric-ready', true);
  return data.session as SessionState;
}

export async function restoreSession(): Promise<SessionState | null> {
  try { return (await SecureStorage.get('pitmetric.mobile.session')) as SessionState | null; } catch { return null; }
}

export async function biometricAvailable(): Promise<boolean> {
  if (!Capacitor.isNativePlatform()) return false;
  const result = await BiometricAuth.checkBiometry();
  return result.isAvailable || result.deviceIsSecure;
}

export async function biometricUnlock(): Promise<SessionState | null> {
  const ready = await SecureStorage.get('pitmetric.mobile.biometric-ready').catch(() => false);
  if (!ready) return null;
  await BiometricAuth.authenticate({
    reason: 'Sblocca PitMetric',
    cancelTitle: 'Annulla',
    allowDeviceCredential: true,
    iosFallbackTitle: 'Usa codice',
    androidTitle: 'Sblocca PitMetric',
    androidSubtitle: 'Usa viso, impronta o blocco dispositivo',
    androidConfirmationRequired: false,
    androidBiometryStrength: AndroidBiometryStrength.weak
  });
  return restoreSession();
}

export async function logout(): Promise<void> {
  const bearer = await token();
  if (bearer) await api('/logout', { method: 'POST' }).catch(() => undefined);
  await SecureStorage.remove('pitmetric.mobile.token').catch(() => undefined);
  await SecureStorage.remove('pitmetric.mobile.session').catch(() => undefined);
  await SecureStorage.remove('pitmetric.mobile.biometric-ready').catch(() => undefined);
}

async function persistPhoto(webPath: string, localId: string): Promise<{ localUri: string; size: number; mime: string }> {
  const blob = await fetch(webPath).then(response => response.blob());
  const base64 = await new Promise<string>((resolve, reject) => {
    const reader = new FileReader();
    reader.onloadend = () => resolve(String(reader.result).split(',')[1]);
    reader.onerror = reject;
    reader.readAsDataURL(blob);
  });
  const saved = await Filesystem.writeFile({ path: `gallery/${localId}.jpg`, data: base64, directory: Directory.Data, recursive: true });
  return { localUri: saved.uri, size: blob.size, mime: blob.type || 'image/jpeg' };
}

export async function addGalleryPhoto(title: string, description: string): Promise<GalleryItem> {
  const photo = await Camera.getPhoto({ source: CameraSource.Prompt, resultType: CameraResultType.Uri, quality: 88, correctOrientation: true });
  if (!photo.webPath) throw new Error('Foto non disponibile.');
  const localId = crypto.randomUUID();
  let localUri = photo.path || photo.webPath;
  let size = 0;
  let mime = 'image/jpeg';

  if (Capacitor.isNativePlatform()) {
    const persisted = await persistPhoto(photo.webPath, localId);
    localUri = persisted.localUri;
    size = persisted.size;
    mime = persisted.mime;
  }

  const item: GalleryItem = {
    local_id: localId,
    title,
    description,
    media_type: 'image',
    mime_type: mime,
    original_name: `${localId}.jpg`,
    size_bytes: size,
    local_uri: localUri,
    preview_uri: photo.webPath,
    created_at: new Date().toISOString(),
    sync_state: 'pending'
  };
  await storeGalleryItem(item);
  return item;
}

function extensionFor(name: string, mimeType: string): string {
  const fromName = name.split('.').pop()?.toLowerCase();
  if (fromName && /^[a-z0-9]{2,5}$/.test(fromName)) return fromName;
  if (mimeType === 'video/quicktime') return 'mov';
  if (mimeType.startsWith('video/')) return 'mp4';
  if (mimeType === 'image/png') return 'png';
  if (mimeType === 'image/webp') return 'webp';
  return 'jpg';
}

export async function addGalleryMedia(title: string, description: string): Promise<GalleryItem> {
  if (!Capacitor.isNativePlatform()) throw new Error('La selezione video è disponibile nell’app installata.');

  const result = await FilePicker.pickMedia({ limit: 1, readData: false });
  const picked = result.files[0];
  if (!picked || !picked.webPath) throw new Error('Nessun file selezionato.');
  if (picked.size > 100 * 1024 * 1024) throw new Error('Il file supera il limite di 100 MB.');

  const localId = crypto.randomUUID();
  const mediaType: 'image' | 'video' = picked.mimeType.startsWith('video/') ? 'video' : 'image';
  const extension = extensionFor(picked.name, picked.mimeType);
  let localUri = picked.path || picked.webPath;

  if (picked.path) {
    try {
      const copied = await Filesystem.copy({
        from: picked.path,
        to: `gallery/${localId}.${extension}`,
        toDirectory: Directory.Data
      });
      localUri = copied.uri;
    } catch {
      localUri = picked.path;
    }
  }

  const item: GalleryItem = {
    local_id: localId,
    title,
    description,
    media_type: mediaType,
    mime_type: picked.mimeType,
    original_name: picked.name,
    size_bytes: picked.size,
    local_uri: localUri,
    preview_uri: picked.webPath,
    created_at: new Date().toISOString(),
    sync_state: 'pending'
  };
  await storeGalleryItem(item);
  return item;
}

async function storeGalleryItem(item: GalleryItem): Promise<void> {
  const conn = await database();
  await conn.run(
    'INSERT INTO gallery_items (local_id,title,description,media_type,mime_type,original_name,size_bytes,local_uri,created_at,sync_state) VALUES (?,?,?,?,?,?,?,?,?,?)',
    [item.local_id, item.title, item.description, item.media_type, item.mime_type || null, item.original_name || null, item.size_bytes || null, item.local_uri, item.created_at, item.sync_state]
  );
  await enqueue('create', 'gallery', item.local_id, item);
  void syncNow();
}

export async function galleryItems(): Promise<GalleryItem[]> {
  const conn = await database();
  const result = await conn.query('SELECT * FROM gallery_items ORDER BY created_at DESC');
  return ((result.values || []) as GalleryItem[]).map(item => ({
    ...item,
    media_type: item.media_type || 'image',
    preview_uri: Capacitor.isNativePlatform() ? Capacitor.convertFileSrc(item.local_uri) : item.local_uri
  }));
}

async function enqueue(operation: string, entity: string, localId: string, payload: unknown): Promise<void> {
  const conn = await database();
  await conn.run('INSERT INTO outbox (operation,entity,local_id,payload,created_at) VALUES (?,?,?,?,?)', [operation, entity, localId, JSON.stringify(payload), new Date().toISOString()]);
}

export async function queueOperation(operation: string, payload: Record<string, unknown>): Promise<string> {
  const localId = crypto.randomUUID();
  await enqueue(operation, 'domain', localId, payload);
  void syncNow();
  return localId;
}

export async function pendingCount(): Promise<number> {
  const conn = await database();
  const result = await conn.query('SELECT COUNT(*) AS total FROM outbox');
  return Number(result.values?.[0]?.total || 0);
}

export async function pendingOperations(): Promise<PendingOperation[]> {
  const conn = await database();
  const result = await conn.query("SELECT local_id, operation, payload, created_at, attempts FROM outbox WHERE entity='domain' ORDER BY id DESC");
  return (result.values || []).map(row => ({
    local_id: String(row.local_id),
    operation: String(row.operation),
    payload: JSON.parse(String(row.payload)) as Record<string, unknown>,
    created_at: String(row.created_at),
    attempts: Number(row.attempts || 0)
  }));
}

export async function cachedSnapshot(): Promise<MobileSnapshot> {
  const conn = await database();
  const result = await conn.query('SELECT payload, updated_at FROM mobile_snapshot WHERE id=1');
  const row = result.values?.[0];
  if (!row) return emptySnapshot();
  try {
    const snapshot = JSON.parse(String(row.payload)) as MobileSnapshot;
    return { ...emptySnapshot(), ...snapshot, synced_at: String(row.updated_at) };
  } catch {
    return emptySnapshot();
  }
}

async function saveSnapshot(snapshot: MobileSnapshot, syncedAt: string): Promise<void> {
  const conn = await database();
  await conn.run(
    'INSERT OR REPLACE INTO mobile_snapshot (id,payload,updated_at) VALUES (1,?,?)',
    [JSON.stringify(snapshot), syncedAt]
  );
}

export async function refreshSnapshotFromCloud(): Promise<MobileSnapshot> {
  const session = await restoreSession();
  const status = await Network.getStatus();
  if (!session?.cloudEnabled || !status.connected || !(await token())) return cachedSnapshot();

  const response = await api('/bootstrap');
  if (!response.ok) return cachedSnapshot();
  const body = await response.json();
  const snapshot = { ...emptySnapshot(), ...(body.data as MobileSnapshot), synced_at: String(body.synced_at || new Date().toISOString()) };
  await saveSnapshot(snapshot, snapshot.synced_at || new Date().toISOString());
  return snapshot;
}

async function galleryFormData(item: GalleryItem): Promise<FormData> {
  const form = new FormData();
  form.set('client_id', item.local_id);
  form.set('title', item.title);
  form.set('description', item.description);
  form.set('captured_at', item.created_at);
  form.set('media_type', item.media_type);

  const source = Capacitor.isNativePlatform() ? Capacitor.convertFileSrc(item.local_uri) : item.local_uri;
  const response = await fetch(source);
  if (!response.ok) throw new Error('Impossibile leggere il file locale.');
  const blob = await response.blob();
  form.set('media', blob, item.original_name || `${item.local_id}.${extensionFor('', item.mime_type || blob.type)}`);

  return form;
}

export async function syncNow(): Promise<{ synced: number; pending: number }> {
  const status = await Network.getStatus();
  const bearer = await token();
  if (!status.connected || !bearer) return { synced: 0, pending: await pendingCount() };

  const session = await restoreSession();
  if (!session?.cloudEnabled) return { synced: 0, pending: await pendingCount() };

  const conn = await database();
  const rows = (await conn.query('SELECT * FROM outbox ORDER BY id ASC LIMIT 50')).values || [];
  let synced = 0;
  let domainChanged = false;

  for (const row of rows) {
    try {
      if (row.entity === 'gallery' && row.operation === 'create') {
        const item = JSON.parse(String(row.payload)) as GalleryItem;
        const response = await api('/gallery', { method: 'POST', body: await galleryFormData(item) });
        if (!response.ok) throw new Error(`sync ${response.status}`);
        const remote = await response.json();
        await conn.run('UPDATE gallery_items SET remote_id=?, sync_state=? WHERE local_id=?', [remote.id, 'synced', item.local_id]);
      } else if (row.entity === 'domain') {
        const response = await api('/sync', {
          method: 'POST',
          body: JSON.stringify({
            client_id: row.local_id,
            operation: row.operation,
            payload: JSON.parse(String(row.payload))
          })
        });
        if (!response.ok) throw new Error(`sync ${response.status}`);
        domainChanged = true;
      }

      await conn.run('DELETE FROM outbox WHERE id=?', [row.id]);
      synced++;
    } catch {
      await conn.run('UPDATE outbox SET attempts=attempts+1 WHERE id=?', [row.id]);
      if (row.entity === 'gallery') {
        await conn.run('UPDATE gallery_items SET sync_state=? WHERE local_id=?', ['error', row.local_id]);
      }
    }
  }

  if (domainChanged) await refreshSnapshotFromCloud();
  return { synced, pending: await pendingCount() };
}

export async function startAutoSync(onChange?: () => void): Promise<() => Promise<void>> {
  const listener = await Network.addListener('networkStatusChange', async status => {
    if (status.connected) {
      await syncNow();
      await refreshSnapshotFromCloud();
      onChange?.();
    }
  });
  return async () => listener.remove();
}
