export type MobilePlatform = 'pwa' | 'android' | 'ios';

const RELEASE_KEY = '2026-11-09-first-open-v1';
const INSTALLATION_KEY = 'pitmetric.mobile.installation.v1';
const CONFIRMED_KEY = `pitmetric.mobile.activation.${RELEASE_KEY}`;
const API_BASE = (import.meta.env?.VITE_PITMETRIC_API_URL || 'https://pitmetric.it/api/mobile').replace(/\/$/, '');

let inFlight: Promise<boolean> | null = null;

async function sendActivation(platform: MobilePlatform): Promise<boolean> {
  let installationId: string;

  try {
    if (localStorage.getItem(CONFIRMED_KEY) === '1') return true;

    installationId = localStorage.getItem(INSTALLATION_KEY) || crypto.randomUUID();
    localStorage.setItem(INSTALLATION_KEY, installationId);
  } catch {
    return false;
  }

  if (typeof navigator !== 'undefined' && navigator.onLine === false) return false;

  try {
    const response = await fetch(`${API_BASE}/activation`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ installation_id: installationId, platform }),
      credentials: 'omit',
      cache: 'no-store',
      signal: AbortSignal.timeout(10000),
    });

    if (!response.ok) return false;
    const result = await response.json();
    if (result.registered !== true || result.release_key !== RELEASE_KEY) return false;

    localStorage.setItem(CONFIRMED_KEY, '1');
    return true;
  } catch {
    return false;
  }
}

/** A failed or offline first opening is retried on the next launch or reconnection. */
export function trackFirstOpen(platform: MobilePlatform): Promise<boolean> {
  if (inFlight) return inFlight;

  inFlight = sendActivation(platform).finally(() => { inFlight = null; });
  return inFlight;
}
