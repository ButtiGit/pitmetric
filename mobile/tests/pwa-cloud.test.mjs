import assert from 'node:assert/strict';
import { after, beforeEach, test } from 'node:test';
import { cachedWebSnapshot, loginWeb, logoutWeb, syncWebNow } from '../src/web-cloud.ts';
import { clearWebSession, readWebSession, webScope, writeWebSession } from '../src/web-session.ts';
import { pendingWebOperations, queueWebOperation, readWebRecords, saveWebRecord, saveWebSnapshot } from '../src/web-domain-store.ts';
import { memoryIndexedDB, memoryStorage } from './helpers/web-storage.mjs';

const originalFetch = globalThis.fetch;
const scope = { userId: 12, workspaceId: 50 };
const session = { authenticated: true, cloudEnabled: true, canWrite: true, user: { id: 12, name: 'PN Rent', email: 'pn@example.test' }, workspace: { id: 50, name: 'PN Rent' } };
const snapshot = { workspace: session.workspace, events: [], vehicles: [], configurations: [], sessions: [], setups: [], maintenance_schedules: [], work_orders: [], setup_fields: {} };
const json = (body, status = 200) => new Response(JSON.stringify(body), { status });
const uuid = () => crypto.randomUUID();

beforeEach(() => {
  globalThis.localStorage = memoryStorage();
  globalThis.indexedDB = memoryIndexedDB();
  writeWebSession({ token: 'pwa-token', session });
});
after(() => { globalThis.fetch = originalFetch; });

test('PWA login stores no password, does not send guest records and supports invalid credentials', async () => {
  let requests = 0;
  globalThis.fetch = async (url, init) => {
    requests++;
    assert.ok(url.endsWith('/login'));
    assert.equal(init.credentials, 'omit');
    assert.equal(init.headers.get('Authorization'), null);
    assert.equal(JSON.parse(init.body).password, 'secret');
    return json({ token: 'new-token', session });
  };
  await loginWeb('pn@example.test', 'secret');
  assert.equal(requests, 1);
  assert.equal(readWebSession().token, 'new-token');
  assert.ok(!JSON.stringify(readWebSession()).includes('secret'));
  globalThis.fetch = async () => json({}, 422);
  await assert.rejects(loginWeb('pn@example.test', 'wrong'), /Credenziali/);
  assert.equal(readWebSession().token, 'new-token');
});

test('record and UUID outbox are saved atomically and acknowledged only after a valid server confirmation', async () => {
  const record = { id: uuid(), kind: 'note', title: 'Pressioni', payload: { event_id: 9, body: 'Pressioni' }, occurred_at: new Date().toISOString() };
  await saveWebRecord(scope, record, 'event.note.create');
  assert.equal((await readWebRecords(scope))[0].sync_state, 'pending');
  const requests = [];
  globalThis.fetch = async (url, init) => {
    requests.push(url);
    assert.equal(init.headers.get('X-Pitmetric-Workspace'), '50');
    if (url.endsWith('/me')) return json({ session });
    if (url.endsWith('/bootstrap')) return json({ data: snapshot, synced_at: '2026-10-30T12:00:00Z' });
    assert.equal(JSON.parse(init.body).client_id, record.id);
    return json({ duplicate: false, result: { id: 71, entity: 'event_note' } }, 201);
  };
  const result = await syncWebNow();
  assert.deepEqual(result, { synced: 1, pending: 0 });
  assert.equal((await readWebRecords(scope))[0].sync_state, 'synced');
  assert.equal((await readWebRecords(scope))[0].remote_id, 71);
  assert.equal((await cachedWebSnapshot()).workspace.id, 50);
  assert.equal(requests.length, 3);
});

test('a failed atomic record save never leaves a partial outbox or overwrites the original', async () => {
  const record = { id: uuid(), kind: 'note', title: 'Originale', payload: {}, occurred_at: new Date().toISOString() };
  await saveWebRecord(scope, record);
  await assert.rejects(saveWebRecord(scope, { ...record, title: 'Sovrascritto' }, 'event.note.create'), /ConstraintError/);
  assert.equal((await readWebRecords(scope))[0].title, 'Originale');
  assert.equal((await pendingWebOperations(scope)).length, 0);
});

test('lost responses and malformed confirmations retain the same operation UUID for idempotent retry', async () => {
  const id = await queueWebOperation(scope, 'vehicle.create', { name: 'Kart' });
  const attempts = [];
  let answer = 'lost';
  globalThis.fetch = async (url, init) => {
    if (url.endsWith('/me')) return json({ session });
    if (url.endsWith('/bootstrap')) return json({ data: snapshot });
    attempts.push(JSON.parse(init.body).client_id);
    if (answer === 'lost') throw new TypeError('Connection lost');
    if (answer === 'malformed') return json({});
    return json({ duplicate: true, result: { id: 19 } });
  };
  await assert.rejects(syncWebNow(), /Connection lost/);
  answer = 'malformed';
  await assert.rejects(syncWebNow(), /Conferma/);
  assert.equal((await pendingWebOperations(scope))[0].attempts, 2);
  answer = 'duplicate';
  await syncWebNow();
  assert.deepEqual(attempts, [id, id, id]);
  assert.equal((await pendingWebOperations(scope)).length, 0);
});

test('a synchronous outbox failure rolls back the record in the same transaction', async () => {
  globalThis.indexedDB = memoryIndexedDB({ throwOnAddStore: 'outbox' });
  const record = { id: uuid(), kind: 'note', title: 'Nota', payload: {}, occurred_at: new Date().toISOString() };
  await assert.rejects(saveWebRecord(scope, record, 'event.note.create'));
  assert.equal((await readWebRecords(scope)).length, 0);
  assert.equal((await pendingWebOperations(scope)).length, 0);
});

test('expired authentication, forbidden permissions and changed workspace never drain the queue', async () => {
  await queueWebOperation(scope, 'vehicle.create', { name: 'Kart' });
  for (const status of [401, 403]) {
    globalThis.fetch = async () => json({}, status);
    await assert.rejects(syncWebNow(), /conservati/);
    assert.equal((await pendingWebOperations(scope)).length, 1);
  }
  globalThis.fetch = async () => json({ session: { ...session, canWrite: false } });
  await assert.rejects(syncWebNow(), /ruolo/);
  globalThis.fetch = async () => json({ session: { ...session, workspace: { id: 51 } } });
  await assert.rejects(syncWebNow(), /team cambiato/);
  assert.equal(webScope().workspaceId, 50);
});

test('multiple sync triggers share one operation loop and dependencies send sessions before laps', async () => {
  await queueWebOperation(scope, 'timing.lap.create', { session_client_id: uuid(), lap_time_ms: 51345 });
  await queueWebOperation(scope, 'session.create', { configuration_version_id: 7 });
  const sent = [];
  globalThis.fetch = async (url, init) => {
    if (url.endsWith('/me')) return json({ session });
    if (url.endsWith('/bootstrap')) return json({ data: snapshot });
    sent.push(JSON.parse(init.body).operation);
    return json({ duplicate: false, result: { id: 1 } });
  };
  await Promise.all([syncWebNow(), syncWebNow()]);
  assert.deepEqual(sent, ['session.create', 'timing.lap.create']);
});

test('logout, reopening and a different account preserve isolated local data without automatic sending', async () => {
  const id = await queueWebOperation(scope, 'vehicle.create', { name: 'Kart' });
  await saveWebSnapshot(scope, snapshot);
  let requests = 0;
  globalThis.fetch = async () => { requests++; return json({}); };
  await logoutWeb();
  assert.equal(webScope(), null);
  assert.equal(await cachedWebSnapshot(), null);
  assert.equal(requests, 1); // Explicit logout revocation only.
  writeWebSession({ token: 'other-token', session: { ...session, user: { ...session.user, id: 13 } } });
  assert.equal((await pendingWebOperations(webScope())).length, 0);
  assert.equal(await cachedWebSnapshot(), null);
  clearWebSession();
  writeWebSession({ token: 'renewed-token', session });
  assert.equal((await pendingWebOperations(webScope()))[0].local_id, id);
  assert.equal((await cachedWebSnapshot()).workspace.id, 50);
  assert.equal(requests, 1);
});
