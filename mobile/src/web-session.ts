import type { SessionState } from './lib';
import type { WebStorageScope } from './web-domain-store';

type WebSession = { token: string; session: SessionState };
const SESSION_KEY = 'pitmetric.pwa.session.v1';

export function readWebSession(): WebSession | null {
  const raw = localStorage.getItem(SESSION_KEY);
  if (!raw) return null;
  try {
    const value = JSON.parse(raw) as WebSession;
    return typeof value.token === 'string' && value.token.length > 0 &&
      value.session?.authenticated && Number.isSafeInteger(value.session.user?.id) ? value : null;
  } catch {
    return null;
  }
}

export function writeWebSession(value: WebSession): void {
  localStorage.setItem(SESSION_KEY, JSON.stringify(value));
}

export function clearWebSession(): void {
  localStorage.removeItem(SESSION_KEY);
}

export function webScope(): WebStorageScope | null {
  const session = readWebSession()?.session;
  if (!session?.user || !session.workspace) return null;
  return { userId: session.user.id, workspaceId: session.workspace.id };
}
