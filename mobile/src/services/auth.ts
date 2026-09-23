import { Capacitor } from '@capacitor/core';
import { Preferences } from '@capacitor/preferences';
import { AccessControl, NativeBiometric } from '@capgo/capacitor-native-biometric';
import { appState } from '../state';
import type { Session } from '../types';

const SESSION_KEY = 'pitmetric.session';
const BIOMETRIC_KEY = 'pitmetric.biometric-session';
const BIOMETRIC_FLAG = 'pitmetric.biometric-enabled';

async function writePlain(session: Session): Promise<void> {
  const value = JSON.stringify(session);
  if (Capacitor.isNativePlatform()) {
    await NativeBiometric.setData({ key: SESSION_KEY, value, accessControl: AccessControl.NONE });
  } else {
    await Preferences.set({ key: SESSION_KEY, value });
  }
}

async function readPlain(): Promise<Session | null> {
  try {
    if (Capacitor.isNativePlatform()) {
      const saved = await NativeBiometric.isDataSaved({ key: SESSION_KEY });
      if (!saved.isSaved) return null;
      return JSON.parse((await NativeBiometric.getData({ key: SESSION_KEY })).value) as Session;
    }
    const { value } = await Preferences.get({ key: SESSION_KEY });
    return value ? JSON.parse(value) as Session : null;
  } catch {
    return null;
  }
}

export async function restoreSession(): Promise<Session | null> {
  const { value: biometricFlag } = await Preferences.get({ key: BIOMETRIC_FLAG });
  appState.biometricEnabled = biometricFlag === '1';

  if (appState.biometricEnabled && Capacitor.isNativePlatform()) {
    try {
      const saved = await NativeBiometric.isDataSaved({ key: BIOMETRIC_KEY });
      if (saved.isSaved) {
        const protectedData = await NativeBiometric.getSecureData({
          key: BIOMETRIC_KEY,
          reason: 'Sblocca PitMetric',
          title: 'PitMetric',
          subtitle: 'Usa viso o impronta',
          negativeButtonText: 'Annulla',
        });
        appState.session = JSON.parse(protectedData.value) as Session;
        return appState.session;
      }
    } catch {
      return null;
    }
  }

  appState.session = await readPlain();
  return appState.session;
}

export async function saveSession(session: Session): Promise<void> {
  appState.session = session;
  if (appState.biometricEnabled && Capacitor.isNativePlatform()) {
    await NativeBiometric.setData({
      key: BIOMETRIC_KEY,
      value: JSON.stringify(session),
      accessControl: AccessControl.BIOMETRY_ANY,
      title: 'Proteggi PitMetric',
      negativeButtonText: 'Annulla',
    });
    await NativeBiometric.deleteData({ key: SESSION_KEY }).catch(() => undefined);
    return;
  }
  await writePlain(session);
}

export async function enableBiometric(): Promise<void> {
  if (!appState.session || !Capacitor.isNativePlatform()) return;
  const available = await NativeBiometric.isAvailable();
  if (!available.isAvailable) throw new Error('Biometria non disponibile o non configurata sul dispositivo.');

  await NativeBiometric.setData({
    key: BIOMETRIC_KEY,
    value: JSON.stringify(appState.session),
    accessControl: AccessControl.BIOMETRY_ANY,
    title: 'Proteggi PitMetric',
    negativeButtonText: 'Annulla',
  });
  await NativeBiometric.deleteData({ key: SESSION_KEY }).catch(() => undefined);
  await Preferences.set({ key: BIOMETRIC_FLAG, value: '1' });
  appState.biometricEnabled = true;
}

export async function disableBiometric(): Promise<void> {
  if (!appState.session) return;
  await writePlain(appState.session);
  await NativeBiometric.deleteData({ key: BIOMETRIC_KEY }).catch(() => undefined);
  await Preferences.set({ key: BIOMETRIC_FLAG, value: '0' });
  appState.biometricEnabled = false;
}

export async function clearSession(): Promise<void> {
  appState.session = null;
  appState.bootstrap = null;
  await Preferences.remove({ key: SESSION_KEY });
  await Preferences.set({ key: BIOMETRIC_FLAG, value: '0' });
  if (Capacitor.isNativePlatform()) {
    await NativeBiometric.deleteData({ key: SESSION_KEY }).catch(() => undefined);
    await NativeBiometric.deleteData({ key: BIOMETRIC_KEY }).catch(() => undefined);
  }
  appState.biometricEnabled = false;
}
