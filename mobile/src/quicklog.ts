import { Capacitor } from '@capacitor/core';
import { Directory, Filesystem } from '@capacitor/filesystem';
import { addWebQuickRecord, deleteWebQuickRecord, readWebQuickRecords } from './web-quicklog';
import { deleteWebRecord, readWebRecords, saveWebRecord } from './web-domain-store';
import { readWebSession, webScope } from './web-session';

export type QuickRecordKind = 'lap_time' | 'session' | 'note' | 'setup' | 'maintenance' | 'tyre_reading';

export type QuickRecord = {
  id: string;
  kind: QuickRecordKind;
  title: string;
  occurred_at: string;
  value_ms?: number | null;
  payload: Record<string, unknown>;
  sync_state?: 'local' | 'pending' | 'synced';
  remote_id?: number;
};

const FILE_PATH = 'pitmetric/quick-records.json';

function sortRecords(records: QuickRecord[]): QuickRecord[] {
  return [...records].sort((a, b) => b.occurred_at.localeCompare(a.occurred_at));
}

async function readNative(): Promise<QuickRecord[]> {
  try {
    const result = await Filesystem.readFile({ path: FILE_PATH, directory: Directory.Data });
    const raw = typeof result.data === 'string' ? result.data : await result.data.text();
    return sortRecords(JSON.parse(raw) as QuickRecord[]);
  } catch {
    return [];
  }
}

async function writeNative(records: QuickRecord[]): Promise<void> {
  await Filesystem.writeFile({
    path: FILE_PATH,
    data: JSON.stringify(sortRecords(records)),
    directory: Directory.Data,
    recursive: true,
  });
}

export async function quickRecords(): Promise<QuickRecord[]> {
  if (Capacitor.isNativePlatform()) return readNative();
  const scope = webScope();
  if (scope) return readWebRecords(scope);
  return readWebQuickRecords();
}

export async function addQuickRecord(
  kind: QuickRecordKind,
  title: string,
  payload: Record<string, unknown> = {},
  valueMs: number | null = null,
  occurredAt = new Date().toISOString(),
): Promise<QuickRecord> {
  const record: QuickRecord = {
    id: crypto.randomUUID(),
    kind,
    title: title.trim() || defaultTitle(kind),
    occurred_at: occurredAt,
    value_ms: valueMs,
    payload,
  };
  if (Capacitor.isNativePlatform()) {
    const records = await readNative();
    records.unshift(record);
    await writeNative(records.slice(0, 1000));
  } else {
    const scope = webScope();
    if (scope) {
      let operation: string | undefined;
      if (readWebSession()?.session.cloudEnabled) {
        if (kind === 'session' && payload.configuration_version_id) operation = 'session.create';
        if (kind === 'lap_time' && (payload.session_id || payload.session_client_id)) operation = 'timing.lap.create';
        if (kind === 'note' && payload.event_id) operation = 'event.note.create';
        if (kind === 'setup' && payload.vehicle_id) operation = 'setup.create';
        if (kind === 'tyre_reading' && (payload.session_id || payload.session_client_id)) operation = 'session.tyres.create';
      }
      record.payload = { ...payload, ...(kind === 'setup' ? { name: record.title } : {}), occurred_at: occurredAt, ...(kind === 'lap_time' ? { lap_time_ms: valueMs } : {}) };
      await saveWebRecord(scope, record, operation);
    } else await addWebQuickRecord(record);
  }
  return record;
}

export async function deleteQuickRecord(id: string): Promise<void> {
  if (Capacitor.isNativePlatform()) {
    await writeNative((await readNative()).filter(record => record.id !== id));
  } else {
    const scope = webScope();
    if (scope) await deleteWebRecord(scope, id);
    else await deleteWebQuickRecord(id);
  }
}

export function parseLapTime(input: string): number | null {
  const value = input.trim().replace(',', '.');
  if (!value) return null;
  if (/^\d+(?:\.\d+)?$/.test(value)) {
    const seconds = Number(value);
    return Number.isFinite(seconds) && seconds >= 0 ? Math.round(seconds * 1000) : null;
  }
  const parts = value.split(':');
  if (parts.length !== 2 || !/^\d+$/.test(parts[0]) || !/^\d+(?:\.\d+)?$/.test(parts[1])) return null;
  const minutes = Number(parts[0]);
  const seconds = Number(parts[1]);
  if (!Number.isFinite(minutes) || !Number.isFinite(seconds) || seconds >= 60) return null;
  return Math.round((minutes * 60 + seconds) * 1000);
}

export function formatLapTime(milliseconds?: number | null): string {
  if (milliseconds === null || milliseconds === undefined) return '—';
  const total = Math.max(0, Math.round(milliseconds));
  const minutes = Math.floor(total / 60000);
  const seconds = Math.floor((total % 60000) / 1000);
  const millis = total % 1000;
  return `${minutes}:${String(seconds).padStart(2, '0')}.${String(millis).padStart(3, '0')}`;
}

function defaultTitle(kind: QuickRecordKind): string {
  return {
    lap_time: 'Tempo sul giro',
    session: 'Sessione libera',
    note: 'Nota rapida',
    setup: 'Setup libero',
    maintenance: 'Promemoria manutenzione',
    tyre_reading: 'Pressioni kart',
  }[kind];
}
