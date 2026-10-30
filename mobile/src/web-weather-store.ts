import { isWeatherSnapshot, type MobileWeatherSnapshot } from './mobile-weather.ts';

// Public, on-demand weather is kept apart from authenticated workspace data.
const DATABASE_NAME = 'pitmetric-pwa-weather';
const STORE_NAME = 'latest_forecast';
const LATEST_KEY = 'latest';

function openDatabase(): Promise<IDBDatabase> {
  if (typeof indexedDB === 'undefined') {
    return Promise.reject(new Error('Archivio meteo offline non disponibile su questo browser.'));
  }

  return new Promise((resolve, reject) => {
    const request = indexedDB.open(DATABASE_NAME, 1);
    request.onupgradeneeded = () => {
      if (!request.result.objectStoreNames.contains(STORE_NAME)) {
        request.result.createObjectStore(STORE_NAME, { keyPath: 'id' });
      }
    };
    request.onsuccess = () => {
      request.result.onversionchange = () => request.result.close();
      resolve(request.result);
    };
    request.onerror = () => reject(request.error || new Error('Impossibile aprire l’archivio meteo offline.'));
    request.onblocked = () => reject(new Error('Chiudi le altre finestre di PitMetric e riprova.'));
  });
}

async function transaction<T>(
  mode: IDBTransactionMode,
  operation: (store: IDBObjectStore, done: (value: T) => void) => void,
): Promise<T> {
  const db = await openDatabase();
  try {
    return await new Promise<T>((resolve, reject) => {
      const tx = db.transaction(STORE_NAME, mode);
      let result: T;
      tx.oncomplete = () => resolve(result);
      tx.onerror = () => reject(tx.error || new Error('Archivio meteo non accessibile.'));
      tx.onabort = () => reject(tx.error || new Error('Salvataggio meteo annullato.'));
      try {
        operation(tx.objectStore(STORE_NAME), value => { result = value; });
      } catch (error) {
        tx.abort();
        reject(error);
      }
    });
  } finally {
    db.close();
  }
}

export async function readWebWeather(): Promise<MobileWeatherSnapshot | null> {
  return transaction('readonly', (store, done) => {
    const request = store.get(LATEST_KEY);
    request.onsuccess = () => {
      const value: unknown = request.result?.snapshot;
      done(isWeatherSnapshot(value) ? value : null);
    };
  });
}

export async function saveWebWeather(snapshot: MobileWeatherSnapshot): Promise<void> {
  if (!isWeatherSnapshot(snapshot)) throw new Error('Previsione meteo non valida.');
  await transaction<void>('readwrite', store => {
    store.put({ id: LATEST_KEY, snapshot });
  });
}
