import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';

const platform = process.argv[2];
if (!['android', 'ios'].includes(platform)) {
  throw new Error('Use: node scripts/native-bootstrap.mjs android|ios');
}

const run = (command, args) => execFileSync(command, args, { stdio: 'inherit', shell: process.platform === 'win32' });

run('npm', ['run', 'build']);
if (!existsSync(platform)) {
  run('npx', ['cap', 'add', platform]);
}

if (platform === 'android') {
  const manifest = 'android/app/src/main/AndroidManifest.xml';
  let xml = readFileSync(manifest, 'utf8');
  if (!xml.includes('android.permission.USE_BIOMETRIC')) {
    xml = xml.replace('<manifest', '<manifest').replace(/(<manifest[^>]*>)/, '$1\n    <uses-permission android:name="android.permission.USE_BIOMETRIC" />');
    writeFileSync(manifest, xml);
  }
} else {
  const plist = 'ios/App/App/Info.plist';
  let xml = readFileSync(plist, 'utf8');
  const permissions = [
    ['NSFaceIDUsageDescription', 'Usa Face ID per sbloccare in modo sicuro PitMetric.'],
    ['NSCameraUsageDescription', 'PitMetric usa la fotocamera per aggiungere foto tecniche alla galleria.'],
    ['NSPhotoLibraryUsageDescription', 'PitMetric accede alle foto che scegli di aggiungere alla galleria.'],
  ];
  for (const [key, value] of permissions) {
    if (!xml.includes(`<key>${key}</key>`)) {
      xml = xml.replace('</dict>\n</plist>', `\t<key>${key}</key>\n\t<string>${value}</string>\n</dict>\n</plist>`);
    }
  }
  writeFileSync(plist, xml);
}

run('npx', ['cap', 'sync', platform]);
run('npx', ['@capacitor/assets', 'generate', `--${platform}`, '--iconBackgroundColor', '#0B0D10', '--iconBackgroundColorDark', '#0B0D10', '--splashBackgroundColor', '#0B0D10', '--splashBackgroundColorDark', '#0B0D10']);
console.log(`PitMetric ${platform} project ready.`);
