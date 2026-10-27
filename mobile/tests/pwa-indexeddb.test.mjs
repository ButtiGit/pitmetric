import assert from 'node:assert/strict';
import { beforeEach, test } from 'node:test';
import {
  addWebQuickRecord,
  deleteWebQuickRecord,
  readWebQuickRecords,
} from '../src/web-quicklog.ts';

function browserStorage() {
  const entries = new Map();
  return {
    getItem: key => entries.get(key) ?? null,
    setItem: (key, value) => entries.set(key, String(value)),
    removeItem: key => entries.delete(key),
  };
}

function memoryIndexedDB() {
  const databases = new Map();

  return {
    open(name) {
      const request = {};
      queueMicrotask(() => {
        let database = databases.get(name);
        const upgrade = !database;
        if (!database) {
          const stores = new Map();
          database = {
            onversionchange: null,
            createObjectStore(storeName, { keyPath }) {
              stores.set(storeName, { keyPath, entries: new Map() });
            },
            transaction(storeNames) {
              const names = Array.isArray(storeNames) ? storeNames : [storeNames];
              const transaction = {
                error: null,
                pending: 0,
                aborted: false,
                completed: false,
                objectStore(storeName) {
                  assert.ok(names.includes(storeName));
                  const { keyPath, entries } = stores.get(storeName);

                  const enqueue = operation => {
                    const item = {};
                    transaction.pending++;
                    queueMicrotask(() => {
                      if (transaction.aborted) return;
                      try {
                        item.result = operation();
                        item.onsuccess?.();
                      } catch (error) {
                        transaction.error = error;
                        transaction.abort();
                      }
                      transaction.pending--;
                      if (transaction.pending === 0) setTimeout(() => {
                        if (!transaction.pending && !transaction.aborted && !transaction.completed) {
                          transaction.completed = true;
                          transaction.oncomplete?.();
                        }
                      }, 0);
                    });
                    return item;
                  };

                  return {
                    get: key => enqueue(() => entries.get(key)),
                    getAll: () => enqueue(() => [...entries.values()]),
                    put: value => enqueue(() => { entries.set(value[keyPath], value); return value[keyPath]; }),
                    delete: key => enqueue(() => { entries.delete(key); }),
                  };
                },
                abort() {
                  this.aborted = true;
                  queueMicrotask(() => this.onabort?.());
                },
              };
              return transaction;
            },
            close() {},
          };
          databases.set(name, database);
        }
        request.result = database;
        if (upgrade) request.onupgradeneeded?.();
        request.onsuccess?.();
      });
      return request;
    },
  };
}

const record = (id, occurredAt) => ({
  id,
  kind: 'lap_time',
  title: `Giro ${id}`,
  occurred_at: occurredAt,
  value_ms: 83123,
  payload: {},
});

beforeEach(() => {
  globalThis.localStorage = browserStorage();
  globalThis.indexedDB = memoryIndexedDB();
});

test('IndexedDB stores offline records and retrieves them newest first', async () => {
  assert.deepEqual(await readWebQuickRecords(), []);
  await addWebQuickRecord(record('old', '2026-10-26T12:00:00Z'));
  await addWebQuickRecord(record('new', '2026-10-27T12:00:00Z'));
  assert.deepEqual((await readWebQuickRecords()).map(item => item.id), ['new', 'old']);
});

test('old localStorage records migrate once and deleted records do not reappear', async () => {
  const legacy = record('legacy', '2026-10-27T12:00:00Z');
  localStorage.setItem('pitmetric.quick-records', JSON.stringify([legacy]));
  assert.deepEqual((await readWebQuickRecords()).map(item => item.id), ['legacy']);
  await deleteWebQuickRecord('legacy');
  assert.deepEqual(await readWebQuickRecords(), []);
  assert.ok(localStorage.getItem('pitmetric.quick-records'), 'the original data is retained as a migration backup');
});

test('malformed legacy records do not get silently discarded', async () => {
  localStorage.setItem('pitmetric.quick-records', '{bad json');
  await assert.rejects(readWebQuickRecords());
  assert.equal(localStorage.getItem('pitmetric.quick-records'), '{bad json');
});

test('older browsers without IndexedDB keep the existing localStorage fallback', async () => {
  delete globalThis.indexedDB;
  await addWebQuickRecord(record('fallback', '2026-10-27T12:00:00Z'));
  assert.deepEqual((await readWebQuickRecords()).map(item => item.id), ['fallback']);
  await deleteWebQuickRecord('fallback');
  assert.deepEqual(await readWebQuickRecords(), []);
});
