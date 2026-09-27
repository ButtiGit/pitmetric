import { Capacitor } from '@capacitor/core';
import { Directory, Filesystem } from '@capacitor/filesystem';

export type QuickRecordKind = 'lap_time' | 'session' | 'note' | 'setup' | 'maintenance';

export type QuickRecord = {
  id: string;
  kind: QuickRecordKind;
  title: string;
  occurred_at: string;
  value_ms?: number | null;
  payload: Record<string, unknown>;
};

const FILE_PATH = 'pitmetric/quick-records.json';
const WEB_KEY = 'pitmetric.quick-records';

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
  try {
    return sortRecords(JSON.parse(localStorage.getItem(WEB_KEY) || '[]') as QuickRecord[]);
  } catch {
    return [];
  }
}

async function persist(records: QuickRecord[]): Promise<void> {
  if (Capacitor.isNativePlatform()) {
    await writeNative(records);
    return;
  }
  localStorage.setItem(WEB_KEY, JSON.stringify(sortRecords(records)));
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
  const records = await quickRecords();
  records.unshift(record);
  await persist(records.slice(0, 1000));
  return record;
}

export async function deleteQuickRecord(id: string): Promise<void> {
  await persist((await quickRecords()).filter(record => record.id !== id));
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
  }[kind];
}
