import assert from 'node:assert/strict';
import { beforeEach, test } from 'node:test';
import {
  confirmWebOperation,
  markWebOperationFailed,
  pendingWebCount,
  pendingWebOperations,
  queueWebOperation,
  readWebSnapshot,
  saveWebSnapshot,
} from '../src/web-domain-store.ts';

function memoryIndexedDB() {
  const databases = new Map();

  return {
    open(name) {
      const request = {};
      queueMicrotask(() => {
        const upgrading = !databases.has(name);
        if (upgrading) {
          const stores = new Map();
          const database = {
            objectStoreNames: { contains: key => stores.has(key) },
            createObjectStore(key, options) { stores.set(key, { keyPath: options.keyPath, data: new Map() }); },
            transaction(key, mode) {
              const storeInfo = stores.get(key);
              assert.ok(storeInfo);
              const transaction = { error: null, oncomplete: null, onerror: null, onabort: null, completed: false, aborted: false, pending: 0 };
              const enqueue = operation => {
                const item = { onsuccess: null, onerror: null, result: undefined };
                transaction.pending++;
                queueMicrotask(() => {
                  if (transaction.aborted) return;
                  try {
                    item.result = operation();
                    item.onsuccess?.();
                  } catch (error) {
                    transaction.error = error;
                    transaction.aborted = true;
                    item.onerror?.();
                    transaction.onerror?.();
                    transaction.onabort?.();
                  }
                  transaction.pending--;
                  if (!transaction.pending && !transaction.aborted) {
                    setTimeout(() => {
                      if (!transaction.pending && !transaction.completed && !transaction.aborted) {
                        transaction.completed = true;
                        transaction.oncomplete?.();
                      }
                    }, 0);
                  }
                });
                return item;
              };
              transaction.objectStore = () => ({
                get: id => enqueue(() => storeInfo.data.get(id)),
                getAll: () => enqueue(() => [...storeInfo.data.values()]),
                count: () => enqueue(() => storeInfo.data.size),
                put: entry => enqueue(() => {
                  if (mode !== 'readwrite') throw new Error('Read only');
                  storeInfo.data.set(entry[storeInfo.keyPath], structuredClone(entry));
                }),
                add: entry => enqueue(() => {
                  const id = entry[storeInfo.keyPath];
                  if (storeInfo.data.has(id)) throw new Error('ConstraintError');
                  storeInfo.data.set(id, structuredClone(entry));
                }),
                delete: id => enqueue(() => { storeInfo.data.delete(id); }),
              });
              transaction.abort = () => {
                transaction.aborted = true;
                queueMicrotask(() => transaction.onabort?.());
              };
              return transaction;
            },
            close() {},
          };
          databases.set(name, database);
        }
        request.result = databases.get(name);
        if (upgrading) request.onupgradeneeded?.();
        request.onsuccess?.();
      });
      return request;
    },
  };
}

const teamA = { userId: 12, workspaceId: 50 };
const teamB = { userId: 12, workspaceId: 51 };
const anotherUser = { userId: 13, workspaceId: 50 };
const snapshot = scope => ({
  workspace: { id: scope.workspaceId, name: 'PN Rent' },
  vehicles: [{ id: 19, name: 'Kart 19', category: 'kart', status: 'active' }],
  events: [], sessions: [], configurations: [], maintenance_schedules: [], work_orders: [], setups: [], setup_fields: {},
  synced_at: '2026-10-28T12:00:00Z',
});

beforeEach(() => { globalThis.indexedDB = memoryIndexedDB(); });

test('offline team snapshots survive reopening and cannot be read from another account or workspace', async () => {
  assert.equal(await readWebSnapshot(teamA), null);
  await saveWebSnapshot(teamA, snapshot(teamA));

  assert.deepEqual(await readWebSnapshot(teamA), snapshot(teamA));
  assert.equal(await readWebSnapshot(teamB), null);
  assert.equal(await readWebSnapshot(anotherUser), null);

  await saveWebSnapshot(teamB, snapshot(teamB));
  assert.equal((await readWebSnapshot(teamA)).workspace.id, teamA.workspaceId);
  assert.equal((await readWebSnapshot(teamB)).workspace.id, teamB.workspaceId);
});

test('rejects writing a snapshot for a different workspace without changing stored data', async () => {
  await saveWebSnapshot(teamA, snapshot(teamA));
  await assert.rejects(saveWebSnapshot(teamA, snapshot(teamB)), /non appartiene/);
  assert.deepEqual(await readWebSnapshot(teamA), snapshot(teamA));
});

test('pending operations keep stable client UUIDs until explicitly confirmed', async () => {
  const clientId = '12345678-1234-4234-9234-123456789abc';
  const payload = { event_id: 8, notes: 'Cold tyres' };
  assert.equal(await queueWebOperation(teamA, 'session.create', payload, clientId), clientId);
  assert.equal(await pendingWebCount(teamA), 1);
  assert.equal(await pendingWebCount(teamB), 0);

  let [record] = await pendingWebOperations(teamA);
  assert.equal(record.local_id, clientId);
  assert.deepEqual(record.payload, payload);
  assert.equal(record.attempts, 0);

  await markWebOperationFailed(teamA, clientId);
  [record] = await pendingWebOperations(teamA);
  assert.equal(record.local_id, clientId);
  assert.equal(record.attempts, 1);
  assert.equal(await pendingWebCount(teamA), 1);

  await confirmWebOperation(teamB, clientId);
  assert.equal(await pendingWebCount(teamA), 1);
  await confirmWebOperation(teamA, clientId);
  assert.equal(await pendingWebCount(teamA), 0);
});

test('reusing a client UUID never overwrites an existing operation', async () => {
  const clientId = '12345678-1234-4234-9234-123456789abc';
  await queueWebOperation(teamA, 'session.create', { lap_count: 1 }, clientId);
  await assert.rejects(queueWebOperation(teamA, 'session.create', { lap_count: 99 }, clientId));
  assert.deepEqual((await pendingWebOperations(teamA))[0].payload, { lap_count: 1 });
});

test('does not fall back to unscoped storage when IndexedDB is unavailable', async () => {
  delete globalThis.indexedDB;
  await assert.rejects(readWebSnapshot(teamA), /IndexedDB non è disponibile/);
  await assert.rejects(queueWebOperation(teamA, 'session.create', {}), /IndexedDB non è disponibile/);
});

test('invalid account and workspace IDs cannot create local team data', async () => {
  await assert.rejects(readWebSnapshot({ userId: 0, workspaceId: 10 }), /account/);
  await assert.rejects(queueWebOperation({ userId: 10, workspaceId: -1 }, 'session.create', {}), /account/);
});
