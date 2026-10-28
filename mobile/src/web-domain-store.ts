import type { MobileSnapshot, PendingOperation } from './lib';

export type WebStorageScope = Readonly<{ userId: number; workspaceId: number }>;

const DATABASE_PREFIX = 'pitmetric-pwa-domain';
const SNAPSHOTS_STORE = 'snapshots';
const OUTBOX_STORE = 'outbox';
const CURRENT_SNAPSHOT = 'current';

type SavedSnapshot = { id: string; payload: MobileSnapshot; synced_at: string };

function databaseName(scope: WebStorageScope): string {
  if (!Number.isSafeInteger(scope.userId) || scope.userId <= 0 ||
      !Number.isSafeInteger(scope.workspaceId) || scope.workspaceId <= 0) {
    throw new Error('Seleziona un account e uno spazio di lavoro validi prima di salvare dati del team.');
  }

  return `${DATABASE_PREFIX}-u${scope.userId}-w${scope.workspaceId}`;
}

function openDatabase(scope: WebStorageScope): Promise<IDBDatabase> {
  const name = databaseName(scope);
  if (typeof indexedDB === 'undefined') {
    throw new Error('IndexedDB non è disponibile: i dati del team non possono essere salvati in sicurezza.');
  }

  return new Promise((resolve, reject) => {
    const request = indexedDB.open(name, 1);
    request.onupgradeneeded = () => {
      const database = request.result;
      if (!database.objectStoreNames.contains(SNAPSHOTS_STORE)) {
        database.createObjectStore(SNAPSHOTS_STORE, { keyPath: 'id' });
      }
      if (!database.objectStoreNames.contains(OUTBOX_STORE)) {
        database.createObjectStore(OUTBOX_STORE, { keyPath: 'local_id' });
      }
    };
    request.onsuccess = () => {
      request.result.onversionchange = () => request.result.close();
      resolve(request.result);
    };
    request.onerror = () => reject(request.error || new Error('Impossibile aprire l’archivio del team.'));
    request.onblocked = () => reject(new Error('Chiudi le altre finestre di PitMetric e riprova.'));
  });
}

async function withStore<T>(
  scope: WebStorageScope,
  storeName: string,
  mode: IDBTransactionMode,
  action: (store: IDBObjectStore, done: (value: T) => void) => void,
): Promise<T> {
  const database = await openDatabase(scope);

  try {
    return await new Promise<T>((resolve, reject) => {
      const transaction = database.transaction(storeName, mode);
      let result: T;

      transaction.oncomplete = () => resolve(result);
      transaction.onerror = () => reject(transaction.error || new Error('Salvataggio IndexedDB non riuscito.'));
      transaction.onabort = () => reject(transaction.error || new Error('Operazione IndexedDB annullata.'));

      try {
        action(transaction.objectStore(storeName), value => { result = value; });
      } catch (error) {
        transaction.abort();
        reject(error);
      }
    });
  } finally {
    database.close();
  }
}

export async function saveWebSnapshot(scope: WebStorageScope, snapshot: MobileSnapshot): Promise<void> {
  if (snapshot.workspace?.id !== scope.workspaceId) {
    throw new Error('Lo snapshot non appartiene allo spazio di lavoro selezionato.');
  }

  await withStore<void>(scope, SNAPSHOTS_STORE, 'readwrite', store => {
    store.put({ id: CURRENT_SNAPSHOT, payload: snapshot, synced_at: snapshot.synced_at || new Date().toISOString() });
  });
}

export async function readWebSnapshot(scope: WebStorageScope): Promise<MobileSnapshot | null> {
  return withStore(scope, SNAPSHOTS_STORE, 'readonly', (store, done) => {
    const request = store.get(CURRENT_SNAPSHOT);
    request.onsuccess = () => {
      const entry = request.result as SavedSnapshot | undefined;
      done(entry ? { ...entry.payload, synced_at: entry.synced_at } : null);
    };
  });
}

export async function queueWebOperation(
  scope: WebStorageScope,
  operation: string,
  payload: Record<string, unknown>,
  clientId = crypto.randomUUID(),
): Promise<string> {
  if (!/^[a-f\d]{8}-[a-f\d]{4}-[1-8][a-f\d]{3}-[89ab][a-f\d]{3}-[a-f\d]{12}$/i.test(clientId)) {
    throw new Error('Identificativo operazione non valido.');
  }

  await withStore<void>(scope, OUTBOX_STORE, 'readwrite', store => {
    store.add({ local_id: clientId, operation, payload, created_at: new Date().toISOString(), attempts: 0 });
  });

  return clientId;
}

export async function pendingWebOperations(scope: WebStorageScope): Promise<PendingOperation[]> {
  return withStore(scope, OUTBOX_STORE, 'readonly', (store, done) => {
    const request = store.getAll();
    request.onsuccess = () => {
      const entries = request.result as PendingOperation[];
      done(entries.sort((a, b) => a.created_at.localeCompare(b.created_at)));
    };
  });
}

export async function pendingWebCount(scope: WebStorageScope): Promise<number> {
  return withStore(scope, OUTBOX_STORE, 'readonly', (store, done) => {
    const request = store.count();
    request.onsuccess = () => done(request.result);
  });
}

export async function confirmWebOperation(scope: WebStorageScope, clientId: string): Promise<void> {
  await withStore<void>(scope, OUTBOX_STORE, 'readwrite', store => { store.delete(clientId); });
}

export async function markWebOperationFailed(scope: WebStorageScope, clientId: string): Promise<void> {
  await withStore<void>(scope, OUTBOX_STORE, 'readwrite', store => {
    const request = store.get(clientId);
    request.onsuccess = () => {
      const operation = request.result as PendingOperation | undefined;
      if (operation) store.put({ ...operation, attempts: operation.attempts + 1 });
    };
  });
}
