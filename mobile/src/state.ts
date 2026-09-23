import { reactive } from 'vue';
import type { BootstrapPayload, Session } from './types';

export const appState = reactive<{
  ready: boolean;
  session: Session | null;
  bootstrap: BootstrapPayload | null;
  online: boolean;
  syncing: boolean;
  pendingCount: number;
  biometricEnabled: boolean;
}>({
  ready: false,
  session: null,
  bootstrap: null,
  online: true,
  syncing: false,
  pendingCount: 0,
  biometricEnabled: false,
});
