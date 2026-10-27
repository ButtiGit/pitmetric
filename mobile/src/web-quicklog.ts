import type { QuickRecord } from './quicklog';

const DATABASE_NAME = 'pitmetric-pwa-quick-records';
const RECORDS_STORE = 'records';
const METADATA_STORE = 'metadata';
const MIGRATION_KEY = 'local-storage-imported';
const LEGACY_STORAGE_KEY = 'pitmetric.quick-records';

function ordered(records: QuickRecord[]): QuickRecord[] {
  return records.sort((first, second) => second.occurred_at.localeCompare(first.occurred_at));
}

function legacyRecords(): QuickRecord[] {
  const raw = localStorage.getItem(LEGACY_STORAGE_KEY);
  if (!raw) return [];
  const records: unknown = JSON.parse(raw);
  if (!Array.isArray(records)) throw new Error('Archivio locale precedente non valido.');
  if (!records.every((record): record is QuickRecord =>
    typeof record === 'object' && record !== null &&
    typeof record.id === 'string' && typeof record.occurred_at === 'string' &&
    typeof record.kind === 'string' && typeof record.title === 'string'
  )) throw new Error('Alcune registrazioni locali precedenti non sono valide.');
  return ordered(records);
}

function openDatabase(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DATABASE_NAME, 1);
    request.onupgradeneeded = () => {
      const database = request.result;
      database.createObjectStore(RECORDS_STORE, { keyPath: 'id' });
      database.createObjectStore(METADATA_STORE, { keyPath: 'key' });
    };
    request.onsuccess = () => {
      request.result.onversionchange = () => request.result.close();
      resolve(request.result);
    };
    request.onerror = () => reject(request.error || new Error('Impossibile aprire l’archivio offline.'));
    request.onblocked = () => reject(new Error('Archivio offline occupato da un’altra versione della PWA.'));
  });
}

function migrateLegacyRecords(database: IDBDatabase): Promise<void> {
  return new Promise((resolve, reject) => {
    const transaction = database.transaction([RECORDS_STORE, METADATA_STORE], 'readwrite');
    const marker = transaction.objectStore(METADATA_STORE).get(MIGRATION_KEY);
    let failure: Error | null = null;

    marker.onsuccess = () => {
      if (marker.result) return;
      try {
        const records = legacyRecords();
        const store = transaction.objectStore(RECORDS_STORE);
        for (const record of records) store.put(record);
        transaction.objectStore(METADATA_STORE).put({ key: MIGRATION_KEY });
      } catch (error) {
        failure = error instanceof Error ? error : new Error('Importazione dei dati precedenti non riuscita.');
        transaction.abort();
      }
    };

    transaction.oncomplete = () => resolve();
    transaction.onerror = () => reject(transaction.error || new Error('Importazione dei dati precedenti non riuscita.'));
    transaction.onabort = () => reject(failure || transaction.error || new Error('Importazione dei dati precedenti annullata.'));
  });
}

async function useStore<T>(mode: IDBTransactionMode, action: (store: IDBObjectStore, done: (result: T) => void) => void): Promise<T> {
  const database = await openDatabase();
  try {
    await migrateLegacyRecords(database);
    return await new Promise<T>((resolve, reject) => {
      const transaction = database.transaction(RECORDS_STORE, mode);
      let result: T | undefined;
      try {
        action(transaction.objectStore(RECORDS_STORE), value => { result = value; });
      } catch (error) {
        transaction.abort();
        reject(error);
        return;
      }
      transaction.oncomplete = () => resolve(result as T);
      transaction.onerror = () => reject(transaction.error || new Error('Operazione offline non riuscita.'));
      transaction.onabort = () => reject(transaction.error || new Error('Operazione offline annullata.'));
    });
  } finally {
    database.close();
  }
}

export async function readWebQuickRecords(): Promise<QuickRecord[]> {
  if (typeof indexedDB === 'undefined') return legacyRecords();
  return useStore('readonly', (store, done) => {
    const request = store.getAll();
    request.onsuccess = () => done(ordered(request.result as QuickRecord[]));
  });
}

export async function addWebQuickRecord(record: QuickRecord): Promise<void> {
  if (typeof indexedDB === 'undefined') {
    const records = legacyRecords();
    localStorage.setItem(LEGACY_STORAGE_KEY, JSON.stringify(ordered([record, ...records]).slice(0, 1000)));
    return;
  }
  await useStore<void>('readwrite', store => { store.put(record); });
}

export async function deleteWebQuickRecord(id: string): Promise<void> {
  if (typeof indexedDB === 'undefined') {
    localStorage.setItem(LEGACY_STORAGE_KEY, JSON.stringify(legacyRecords().filter(record => record.id !== id)));
    return;
  }
  await useStore<void>('readwrite', store => { store.delete(id); });
}
