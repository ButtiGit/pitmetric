import { appState } from '../state';
import type { BootstrapPayload, RemoteGalleryPhoto, Session } from '../types';

export const API_BASE = (import.meta.env.VITE_API_URL || 'https://pitmetric.it').replace(/\/$/, '');

export class ApiError extends Error {
  constructor(public status: number, public code: string | null, message: string) {
    super(message);
  }
}

async function request<T>(path: string, options: RequestInit = {}, authenticated = true): Promise<T> {
  const headers = new Headers(options.headers || {});
  headers.set('Accept', 'application/json');
  if (!(options.body instanceof FormData)) headers.set('Content-Type', 'application/json');
  if (authenticated && appState.session?.token) headers.set('Authorization', `Bearer ${appState.session.token}`);

  const response = await fetch(`${API_BASE}${path}`, { ...options, headers });
  const contentType = response.headers.get('content-type') || '';
  const payload = contentType.includes('application/json') ? await response.json() : null;
  if (!response.ok) throw new ApiError(response.status, payload?.code ?? null, payload?.message ?? `HTTP ${response.status}`);
  return payload as T;
}

export async function login(email: string, password: string): Promise<Session> {
  const payload = await request<{ token: string; user: Session['user'] }>('/api/mobile/v1/login', {
    method: 'POST',
    body: JSON.stringify({ email, password, device_name: navigator.userAgent.slice(0, 120) }),
  }, false);
  return { token: payload.token, user: payload.user };
}

export async function register(name: string, email: string, password: string): Promise<Session> {
  const payload = await request<{ token: string; user: Session['user'] }>('/api/mobile/v1/register', {
    method: 'POST',
    body: JSON.stringify({ name, email, password, password_confirmation: password, device_name: navigator.userAgent.slice(0, 120) }),
  }, false);
  return { token: payload.token, user: payload.user };
}

export const bootstrap = () => request<BootstrapPayload>('/api/mobile/v1/bootstrap');

export const syncCaptures = (operations: Array<{ id: string; type: 'capture'; payload: Record<string, unknown> }>) =>
  request<{ synced: Array<{ id: string; duplicate: boolean; resource_id: number }>; server_time: string }>('/api/mobile/v1/sync', {
    method: 'POST',
    body: JSON.stringify({ operations }),
  });

export const galleryIndex = () => request<{ photos: RemoteGalleryPhoto[]; cloud_enabled: boolean }>('/api/mobile/v1/gallery');

export async function uploadGalleryPhoto(data: FormData): Promise<{ photo: RemoteGalleryPhoto; duplicate: boolean }> {
  return request('/api/mobile/v1/gallery', { method: 'POST', body: data });
}

export async function remoteImageBlob(url: string): Promise<Blob> {
  const response = await fetch(url, { headers: { Authorization: `Bearer ${appState.session?.token ?? ''}` } });
  if (!response.ok) throw new Error('Impossibile scaricare la foto.');
  return response.blob();
}

export async function logoutRemote(): Promise<void> {
  await request('/api/mobile/v1/token', { method: 'DELETE' }).catch(() => undefined);
}
