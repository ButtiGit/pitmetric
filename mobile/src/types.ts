export type CloudUser = {
  id: number;
  name: string;
  email: string;
  email_verified: boolean;
  cloud_enabled: boolean;
  cloud_reason: string | null;
};

export type Session = {
  token: string;
  user: CloudUser;
};

export type BootstrapPayload = {
  user: CloudUser;
  workspace: null | { id: number; name: string; role: string | null; can_write: boolean };
  captures: RemoteCapture[];
  gallery_count: number;
  server_time: string;
};

export type CapturePayload = {
  kind: 'lap' | 'note' | 'issue' | 'tyre_pressure' | 'component_change';
  circuit_name?: string | null;
  lap_time_ms?: number | null;
  notes?: string | null;
  occurred_at: string;
  payload?: Record<string, unknown> | null;
  context?: Record<string, string | null>;
};

export type LocalCapture = {
  id: string;
  ownerUserId: number;
  payload: CapturePayload;
  syncState: 'pending' | 'synced' | 'failed';
  remoteId?: number | null;
  createdAt: string;
  error?: string | null;
};

export type RemoteCapture = {
  id: number;
  kind: CapturePayload['kind'];
  circuit_name: string | null;
  lap_time_ms: number | null;
  payload: Record<string, unknown> | null;
  occurred_at: string;
  status: string;
  notes: string | null;
};

export type LocalGalleryPhoto = {
  id: string;
  ownerUserId: number;
  title: string;
  description: string;
  filePath: string;
  mimeType: string;
  takenAt: string;
  syncState: 'pending' | 'synced' | 'failed';
  remoteId?: number | null;
  error?: string | null;
};

export type RemoteGalleryPhoto = {
  id: number;
  client_uuid: string;
  title: string;
  description: string | null;
  mime_type: string;
  size_bytes: number;
  taken_at: string | null;
  created_at: string | null;
  image_url: string;
};
