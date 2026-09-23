import { Capacitor } from '@capacitor/core';
import { Network } from '@capacitor/network';
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera';
import { Filesystem, Directory } from '@capacitor/filesystem';
import { CapacitorSQLite, SQLiteConnection, SQLiteDBConnection } from '@capacitor-community/sqlite';
import { BiometricAuth, AndroidBiometryStrength } from '@aparajita/capacitor-biometric-auth';
import { SecureStorage } from '@aparajita/capacitor-secure-storage';

export type SessionState = {
  authenticated: boolean;
  cloudEnabled: boolean;
  user?: { id: number; name: string; email: string };
  workspace?: { id: number; name: string } | null;
};

export type GalleryItem = {
  local_id: string;
  remote_id?: number | null;
  title: string;
  description: string;
  local_uri: string;
  preview_uri?: string;
  created_at: string;
  sync_state: 'local' | 'pending' | 'synced' | 'error';
};

const API_BASE = (import.meta.env.VITE_PITMETRIC_API_URL || 'https://pitmetric.it/api/mobile').replace(/\/$/, '');
const sqlite = new SQLiteConnection(CapacitorSQLite);
let db: SQLiteDBConnection | null = null;

async function database(): Promise<SQLiteDBConnection> {
  if (db) return db;
  db = await sqlite.createConnection('pitmetric_mobile', false, 'no-encryption', 1, false);
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
  `);
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
  return data.session;
}

export async function register(name: string, email: string, password: string): Promise<SessionState> {
  const response = await api('/register', { method: 'POST', body: JSON.stringify({ name, email, password, password_confirmation: password, device_name: Capacitor.getPlatform() }) });
  if (!response.ok) throw new Error('Registrazione non riuscita. Controlla i dati inseriti.');
  const data = await response.json();
  await SecureStorage.set('pitmetric.mobile.token', data.token);
  await SecureStorage.set('pitmetric.mobile.session', data.session);
  await SecureStorage.set('pitmetric.mobile.biometric-ready', true);
  return data.session;
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

export async function addGalleryPhoto(title: string, description: string): Promise<GalleryItem> {
  const photo = await Camera.getPhoto({ source: CameraSource.Prompt, resultType: CameraResultType.Uri, quality: 88, correctOrientation: true });
  if (!photo.webPath) throw new Error('Foto non disponibile.');
  const localId = crypto.randomUUID();
  let localUri = photo.path || photo.webPath;

  if (Capacitor.isNativePlatform()) {
    const blob = await fetch(photo.webPath).then(r => r.blob());
    const base64 = await new Promise<string>((resolve, reject) => {
      const reader = new FileReader();
      reader.onloadend = () => resolve(String(reader.result).split(',')[1]);
      reader.onerror = reject;
      reader.readAsDataURL(blob);
    });
    const saved = await Filesystem.writeFile({ path: `gallery/${localId}.jpg`, data: base64, directory: Directory.Data, recursive: true });
    localUri = saved.uri;
  }

  const item: GalleryItem = { local_id: localId, title, description, local_uri: localUri, preview_uri: photo.webPath, created_at: new Date().toISOString(), sync_state: 'pending' };
  const conn = await database();
  await conn.run('INSERT INTO gallery_items (local_id,title,description,local_uri,created_at,sync_state) VALUES (?,?,?,?,?,?)', [item.local_id, item.title, item.description, item.local_uri, item.created_at, item.sync_state]);
  await enqueue('create', 'gallery', item.local_id, item);
  void syncNow();
  return item;
}

export async function galleryItems(): Promise<GalleryItem[]> {
  const conn = await database();
  const result = await conn.query('SELECT * FROM gallery_items ORDER BY created_at DESC');
  return ((result.values || []) as GalleryItem[]).map(item => ({
    ...item,
    preview_uri: Capacitor.isNativePlatform() ? Capacitor.convertFileSrc(item.local_uri) : item.local_uri
  }));
}

async function enqueue(operation: string, entity: string, localId: string, payload: unknown): Promise<void> {
  const conn = await database();
  await conn.run('INSERT INTO outbox (operation,entity,local_id,payload,created_at) VALUES (?,?,?,?,?)', [operation, entity, localId, JSON.stringify(payload), new Date().toISOString()]);
}

export async function pendingCount(): Promise<number> {
  const conn = await database();
  const result = await conn.query('SELECT COUNT(*) AS total FROM outbox');
  return Number(result.values?.[0]?.total || 0);
}

async function galleryFormData(item: GalleryItem): Promise<FormData> {
  const form = new FormData();
  form.set('client_id', item.local_id);
  form.set('title', item.title);
  form.set('description', item.description);
  form.set('captured_at', item.created_at);

  const source = Capacitor.isNativePlatform() ? Capacitor.convertFileSrc(item.local_uri) : item.local_uri;
  const response = await fetch(source);
  if (!response.ok) throw new Error('Impossibile leggere la foto locale.');
  const blob = await response.blob();
  form.set('photo', blob, `${item.local_id}.jpg`);

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
  for (const row of rows) {
    try {
      if (row.entity === 'gallery' && row.operation === 'create') {
        const item = JSON.parse(row.payload) as GalleryItem;
        const response = await api('/gallery', { method: 'POST', body: await galleryFormData(item) });
        if (!response.ok) throw new Error(`sync ${response.status}`);
        const remote = await response.json();
        await conn.run('UPDATE gallery_items SET remote_id=?, sync_state=? WHERE local_id=?', [remote.id, 'synced', item.local_id]);
      }
      await conn.run('DELETE FROM outbox WHERE id=?', [row.id]);
      synced++;
    } catch {
      await conn.run('UPDATE outbox SET attempts=attempts+1 WHERE id=?', [row.id]);
      await conn.run('UPDATE gallery_items SET sync_state=? WHERE local_id=?', ['error', row.local_id]);
    }
  }
  return { synced, pending: await pendingCount() };
}

export async function startAutoSync(onChange?: () => void): Promise<() => Promise<void>> {
  const listener = await Network.addListener('networkStatusChange', async status => {
    if (status.connected) {
      await syncNow();
      onChange?.();
    }
  });
  return async () => listener.remove();
}
