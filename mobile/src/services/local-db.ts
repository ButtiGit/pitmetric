import type { LocalCapture, LocalGalleryPhoto } from '../types';

const DB_NAME = 'pitmetric-mobile';
const DB_VERSION = 1;

type StoreName = 'meta' | 'captures' | 'gallery';

let databasePromise: Promise<IDBDatabase> | null = null;

function db(): Promise<IDBDatabase> {
  if (databasePromise) return databasePromise;
  databasePromise = new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);
    request.onupgradeneeded = () => {
      const database = request.result;
      if (!database.objectStoreNames.contains('meta')) database.createObjectStore('meta', { keyPath: 'key' });
      if (!database.objectStoreNames.contains('captures')) database.createObjectStore('captures', { keyPath: 'id' });
      if (!database.objectStoreNames.contains('gallery')) database.createObjectStore('gallery', { keyPath: 'id' });
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
  return databasePromise;
}

async function put<T>(store: StoreName, value: T): Promise<void> {
  const database = await db();
  await new Promise<void>((resolve, reject) => {
    const tx = database.transaction(store, 'readwrite');
    tx.objectStore(store).put(value);
    tx.oncomplete = () => resolve();
    tx.onerror = () => reject(tx.error);
  });
}

async function all<T>(store: StoreName): Promise<T[]> {
  const database = await db();
  return new Promise<T[]>((resolve, reject) => {
    const request = database.transaction(store, 'readonly').objectStore(store).getAll();
    request.onsuccess = () => resolve(request.result as T[]);
    request.onerror = () => reject(request.error);
  });
}

export const saveCapture = (capture: LocalCapture) => put('captures', capture);
export const saveGalleryPhoto = (photo: LocalGalleryPhoto) => put('gallery', photo);
export const getCaptures = () => all<LocalCapture>('captures');
export const getGalleryPhotos = () => all<LocalGalleryPhoto>('gallery');

export async function setMeta<T>(key: string, value: T): Promise<void> {
  await put('meta', { key, value });
}

export async function getMeta<T>(key: string): Promise<T | null> {
  const database = await db();
  return new Promise<T | null>((resolve, reject) => {
    const request = database.transaction('meta', 'readonly').objectStore('meta').get(key);
    request.onsuccess = () => resolve(request.result?.value ?? null);
    request.onerror = () => reject(request.error);
  });
}

export async function pendingCount(): Promise<number> {
  const [captures, photos] = await Promise.all([getCaptures(), getGalleryPhotos()]);
  return captures.filter((item) => item.syncState !== 'synced').length + photos.filter((item) => item.syncState !== 'synced').length;
}
