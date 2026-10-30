import type { MobileSnapshot, SessionState } from './lib';
import {
  acknowledgeWebRecord, markWebOperationFailed, pendingWebCount, pendingWebOperations,
  readWebSnapshot, saveWebSnapshot, type WebStorageScope,
} from './web-domain-store.ts';
import { clearWebSession, readWebSession, webScope, writeWebSession } from './web-session.ts';

const API_BASE = (import.meta.env?.VITE_PITMETRIC_API_URL || 'https://pitmetric.it/api/mobile').replace(/\/$/, '');
let syncing: Promise<{ synced: number; pending: number }> | null = null;

function sameScope(scope: WebStorageScope): boolean {
  const current = webScope();
  return current?.userId === scope.userId && current.workspaceId === scope.workspaceId;
}

async function request(path: string, init: RequestInit = {}, scope?: WebStorageScope): Promise<Response> {
  const saved = readWebSession();
  const headers = new Headers({ Accept: 'application/json', 'Content-Type': 'application/json' });
  if (saved && path !== '/login' && path !== '/register') headers.set('Authorization', `Bearer ${saved.token}`);
  if (scope) {
    if (!sameScope(scope)) throw new Error('Account cambiato. Riprova dal nuovo account.');
    headers.set('X-Pitmetric-Workspace', String(scope.workspaceId));
  }
  const response = await fetch(`${API_BASE}${path}`, { ...init, headers, credentials: 'omit', cache: 'no-store', signal: AbortSignal.timeout(20000) });
  if (!response.ok) {
    const message = response.status === 401 ? 'Accesso scaduto: accedi di nuovo. I dati da inviare sono conservati.'
      : response.status === 403 ? 'Accesso o permessi cloud non disponibili. I dati da inviare sono conservati.'
      : response.status === 422 ? (path === '/login' ? 'Credenziali non valide.' : 'Dati non accettati dal server. La registrazione resta sul dispositivo.')
      : 'Server non disponibile. I dati da inviare sono conservati: riprova più tardi.';
    throw new Error(message);
  }
  return response;
}

export async function loginWeb(email: string, password: string, name?: string): Promise<SessionState> {
  const response = await request(name === undefined ? '/login' : '/register', {
    method: 'POST', body: JSON.stringify({ email, password, ...(name === undefined ? {} : { name, password_confirmation: password }), device_name: 'PWA' }),
  });
  const body = await response.json();
  if (typeof body.token !== 'string' || !body.session?.authenticated || !Number.isSafeInteger(body.session.user?.id)) {
    throw new Error('Risposta di accesso non valida.');
  }
  writeWebSession({ token: body.token, session: body.session });
  return body.session;
}

export async function logoutWeb(): Promise<void> {
  const saved = readWebSession();
  clearWebSession();
  if (saved) void fetch(`${API_BASE}/logout`, {
    method: 'POST', credentials: 'omit', headers: { Authorization: `Bearer ${saved.token}`, Accept: 'application/json' },
    signal: AbortSignal.timeout(5000),
  }).catch(() => undefined);
}

export async function refreshWebSnapshot(): Promise<MobileSnapshot | null> {
  const scope = webScope();
  if (!scope) return null;
  const body = await (await request('/bootstrap', {}, scope)).json();
  if (body.data?.workspace?.id !== scope.workspaceId) throw new Error('Il team restituito dal server è diverso: nessun dato locale è stato sostituito.');
  const snapshot: MobileSnapshot = { ...body.data, synced_at: body.synced_at };
  await saveWebSnapshot(scope, snapshot);
  return snapshot;
}

export async function syncWebNow(): Promise<{ synced: number; pending: number }> {
  if (syncing) return syncing;
  const scope = webScope();
  if (!scope) throw new Error('Accedi al tuo account prima di sincronizzare.');
  syncing = syncScope(scope).finally(() => { syncing = null; });
  return syncing;
}

async function syncScope(scope: WebStorageScope): Promise<{ synced: number; pending: number }> {
  if (typeof navigator !== 'undefined' && navigator.onLine === false) throw new Error('Connessione assente. I dati restano salvati sul dispositivo.');
  // Refresh permissions before sending; the server checks them again on every operation.
  const body = await (await request('/me', {}, scope)).json();
  if (body.session?.user?.id !== scope.userId || body.session?.workspace?.id !== scope.workspaceId) {
    throw new Error('Account o team cambiato. Accedi nuovamente prima di inviare i dati.');
  }
  if (!sameScope(scope)) throw new Error('Account cambiato durante la sincronizzazione.');
  writeWebSession({ token: readWebSession()!.token, session: body.session });
  if (!body.session.cloudEnabled) throw new Error('Cloud non abilitato. I dati restano sul dispositivo.');
  const operations = await pendingWebOperations(scope);
  if (operations.length && !body.session.canWrite) throw new Error('Il tuo ruolo consente solo la consultazione. I dati da inviare sono conservati.');
  // Sessions must exist before the laps captured against their client UUIDs.
  operations.sort((a, b) => Number(b.operation === 'session.create') - Number(a.operation === 'session.create'));
  let synced = 0;
  let failure: unknown;
  for (const operation of operations) {
    try {
      const response = await request('/sync', {
        method: 'POST', body: JSON.stringify({ client_id: operation.local_id, operation: operation.operation, payload: operation.payload }),
      }, scope);
      const confirmation = await response.json();
      if (!Number.isSafeInteger(confirmation.result?.id) || confirmation.result.id <= 0 || typeof confirmation.duplicate !== 'boolean') {
        throw new Error('Conferma del server incompleta. La registrazione resta da inviare.');
      }
      await acknowledgeWebRecord(scope, operation.local_id, confirmation.result.id);
      synced++;
    } catch (error) {
      await markWebOperationFailed(scope, operation.local_id);
      failure = error;
      break;
    }
  }
  if (sameScope(scope)) await refreshWebSnapshot();
  if (failure) throw failure;
  return { synced, pending: await pendingWebCount(scope) };
}

export async function cachedWebSnapshot(): Promise<MobileSnapshot | null> {
  const scope = webScope();
  return scope ? readWebSnapshot(scope) : null;
}
