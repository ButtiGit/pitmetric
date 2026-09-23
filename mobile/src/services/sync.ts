import { App } from '@capacitor/app';
import { Directory, Filesystem } from '@capacitor/filesystem';
import { Network } from '@capacitor/network';
import { appState } from '../state';
import type { CapturePayload, LocalCapture, LocalGalleryPhoto } from '../types';
import { ApiError, bootstrap, syncCaptures, uploadGalleryPhoto } from './api';
import { getCaptures, getGalleryPhotos, pendingCount, saveCapture, saveGalleryPhoto, setMeta } from './local-db';

let listenersStarted = false;

export async function queueCapture(payload: CapturePayload): Promise<LocalCapture> {
  const capture: LocalCapture = {
    id: crypto.randomUUID(),
    payload,
    syncState: 'pending',
    createdAt: new Date().toISOString(),
  };
  await saveCapture(capture);
  await refreshPendingCount();
  void syncNow();
  return capture;
}

export async function refreshPendingCount(): Promise<void> {
  appState.pendingCount = await pendingCount();
}

export async function refreshBootstrap(): Promise<void> {
  if (!appState.session || !appState.online) return;
  try {
    const payload = await bootstrap();
    appState.bootstrap = payload;
    appState.session.user = payload.user;
    await setMeta('bootstrap', payload);
  } catch {
    // Cached local data remains authoritative while offline/server unavailable.
  }
}

export async function syncNow(): Promise<void> {
  if (appState.syncing || !appState.online || !appState.session?.user.cloud_enabled) {
    await refreshPendingCount();
    return;
  }

  appState.syncing = true;
  try {
    const captures = (await getCaptures()).filter((item) => item.syncState !== 'synced').slice(0, 50);
    if (captures.length) {
      const response = await syncCaptures(captures.map((capture) => ({
        id: capture.id,
        type: 'capture' as const,
        payload: capture.payload as unknown as Record<string, unknown>,
      })));
      for (const result of response.synced) {
        const capture = captures.find((item) => item.id === result.id);
        if (!capture) continue;
        await saveCapture({ ...capture, syncState: 'synced', remoteId: result.resource_id, error: null });
      }
    }

    const photos = (await getGalleryPhotos()).filter((item) => item.syncState !== 'synced');
    for (const photo of photos) await syncPhoto(photo);
    await refreshBootstrap();
  } catch (error) {
    if (error instanceof ApiError && ['database_access_required', 'email_verification_required'].includes(error.code ?? '')) {
      appState.session.user.cloud_enabled = false;
      appState.session.user.cloud_reason = error.code;
    }
  } finally {
    appState.syncing = false;
    await refreshPendingCount();
  }
}

async function syncPhoto(photo: LocalGalleryPhoto): Promise<void> {
  try {
    const file = await Filesystem.readFile({ path: photo.filePath, directory: Directory.Data });
    if (typeof file.data !== 'string') throw new Error('Formato foto non supportato.');
    const binary = atob(file.data);
    const bytes = new Uint8Array(binary.length);
    for (let index = 0; index < binary.length; index += 1) bytes[index] = binary.charCodeAt(index);
    const blob = new Blob([bytes], { type: photo.mimeType });
    const body = new FormData();
    body.append('client_uuid', photo.id);
    body.append('title', photo.title);
    body.append('description', photo.description);
    body.append('taken_at', photo.takenAt);
    body.append('photo', blob, `${photo.id}.jpg`);
    const uploaded = await uploadGalleryPhoto(body);
    await saveGalleryPhoto({ ...photo, syncState: 'synced', remoteId: uploaded.photo.id, error: null });
  } catch (error) {
    await saveGalleryPhoto({ ...photo, syncState: 'failed', error: error instanceof Error ? error.message : 'Sync failed' });
    throw error;
  }
}

export async function startConnectivity(): Promise<void> {
  const status = await Network.getStatus();
  appState.online = status.connected;
  await refreshPendingCount();
  if (listenersStarted) return;
  listenersStarted = true;

  await Network.addListener('networkStatusChange', (network) => {
    appState.online = network.connected;
    if (network.connected) void syncNow();
  });
  await App.addListener('appStateChange', ({ isActive }) => {
    if (isActive) void syncNow();
  });
}
