import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { resolve } from 'node:path';

const manifestPath = resolve(fileURLToPath(new URL('.', import.meta.url)), 'android/app/src/main/AndroidManifest.xml');

export function withLocationPermissions(xml) {
  const permissions = ['android.permission.ACCESS_COARSE_LOCATION', 'android.permission.ACCESS_FINE_LOCATION'];
  if (!/<manifest\b[^>]*>/.test(xml)) throw new Error('AndroidManifest.xml is not a valid Android manifest.');

  const missing = permissions.filter(permission => !new RegExp(`<uses-permission\\b[^>]*android:name=["']${permission}["']`).test(xml));
  if (!missing.length) return xml;

  const additions = missing.map(permission => `\n    <uses-permission android:name="${permission}" />`).join('');
  return xml.replace(/<manifest\b[^>]*>/, openingTag => `${openingTag}${additions}`);
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  if (!existsSync(manifestPath)) {
    console.log('Android project not generated; skipping location permission configuration.');
  } else {
    const previous = readFileSync(manifestPath, 'utf8');
    const updated = withLocationPermissions(previous);
    if (previous !== updated) writeFileSync(manifestPath, updated);
    console.log('Android geolocation permissions verified.');
  }
}
