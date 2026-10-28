import { Capacitor } from '@capacitor/core';
import { Geolocation } from '@capacitor/geolocation';

export type MobileWeatherSnapshot = {
  provider: string;
  latitude: number;
  longitude: number;
  captured_at: string;
  forecast_at: string;
  air_temperature_c: number;
  humidity_pct: number | null;
  wind_speed_kmh: number | null;
  wind_direction_deg: number | null;
  pressure_hpa: number | null;
  cloud_cover_pct: number | null;
  precipitation_next_hour_mm: number | null;
  symbol_code: string;
  hourly: Array<{ at: string; temperature_c: number; symbol_code: string }>;
};

export type WeatherSky = 'clear' | 'clouds' | 'rain' | 'storm' | 'snow';

const API_BASE = (import.meta.env?.VITE_PITMETRIC_API_URL || 'https://pitmetric.it/api/mobile').replace(/\/$/, '');

export function weatherSky(symbolCode: string): WeatherSky {
  if (symbolCode.includes('thunder')) return 'storm';
  if (symbolCode.includes('rain') || symbolCode.includes('sleet')) return 'rain';
  if (symbolCode.includes('snow')) return 'snow';
  if (symbolCode.includes('clearsky') || symbolCode.includes('fair')) return 'clear';
  return 'clouds';
}

export function weatherDescription(symbolCode: string): string {
  switch (weatherSky(symbolCode)) {
    case 'storm': return 'Temporale';
    case 'rain': return 'Pioggia';
    case 'snow': return 'Neve';
    case 'clear': return 'Cielo sereno';
    default: return 'Nuvoloso / variabile';
  }
}

export function windDirection(degrees: number | null): string {
  if (degrees === null || !Number.isFinite(degrees)) return '—';
  const compass = ['N', 'NE', 'E', 'SE', 'S', 'SO', 'O', 'NO'];
  return compass[Math.round(((degrees % 360) + 360) % 360 / 45) % 8];
}

export function weatherHour(value: string): string {
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('it-IT', { hour: '2-digit', minute: '2-digit' }).format(date);
}

export async function readDeviceCoordinates(): Promise<{ latitude: number; longitude: number }> {
  if (Capacitor.isNativePlatform()) {
    try {
      const existing = await Geolocation.checkPermissions();
      if (existing.location !== 'granted' && existing.coarseLocation !== 'granted') {
        const permission = await Geolocation.requestPermissions();
        if (permission.location !== 'granted' && permission.coarseLocation !== 'granted') {
          throw new Error('Permesso posizione negato. Abilita la posizione per PitMetric nelle impostazioni del dispositivo.');
        }
      }
      const position = await Geolocation.getCurrentPosition({ enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
      return { latitude: position.coords.latitude, longitude: position.coords.longitude };
    } catch (error) {
      if (error instanceof Error && error.message.startsWith('Permesso posizione negato')) throw error;
      throw new Error('Impossibile ottenere la posizione. Controlla GPS e autorizzazioni, poi riprova.');
    }
  }

  if (typeof navigator === 'undefined' || !navigator.geolocation || !globalThis.isSecureContext) {
    throw new Error('GPS non disponibile. Apri PitMetric tramite HTTPS e autorizza l’accesso alla posizione.');
  }

  return new Promise((resolve, reject) => {
    navigator.geolocation.getCurrentPosition(
      position => resolve({ latitude: position.coords.latitude, longitude: position.coords.longitude }),
      error => reject(new Error(error.code === 1
        ? 'Permesso GPS negato. Abilita la posizione nelle impostazioni di Safari o Chrome.'
        : error.code === 3
          ? 'Ricerca GPS scaduta. Riprova all’aperto.'
          : 'Posizione non disponibile. Controlla GPS e autorizzazioni.')),
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 },
    );
  });
}

export async function loadDeviceWeather(latitude: number, longitude: number): Promise<MobileWeatherSnapshot> {
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || Math.abs(latitude) > 90 || Math.abs(longitude) > 180) {
    throw new Error('La posizione GPS restituita dal dispositivo non è valida.');
  }

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 12000);

  try {
    const response = await fetch(`${API_BASE}/weather`, {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ latitude: Number(latitude.toFixed(4)), longitude: Number(longitude.toFixed(4)) }),
      signal: controller.signal,
    });

    if (!response.ok) {
      throw new Error(response.status === 429
        ? 'Troppi aggiornamenti meteo. Attendi un minuto e riprova.'
        : 'Servizio meteo temporaneamente non disponibile. Riprova tra poco.');
    }

    const responseBody: unknown = await response.json();
    const forecast = typeof responseBody === 'object' && responseBody !== null && 'data' in responseBody
      ? responseBody.data as MobileWeatherSnapshot
      : null;

    if (!forecast || forecast.provider !== 'met_norway' || !Number.isFinite(forecast.air_temperature_c) ||
        !Array.isArray(forecast.hourly) || typeof forecast.captured_at !== 'string') {
      throw new Error('Il servizio meteo ha restituito dati incompleti.');
    }

    return forecast;
  } catch (error) {
    if (error instanceof Error && error.name === 'AbortError') throw new Error('Il meteo non risponde. Verifica la connessione e riprova.');
    if (error instanceof TypeError) throw new Error('Connessione assente. Per aggiornare il meteo devi essere online.');
    throw error;
  } finally {
    clearTimeout(timer);
  }
}
