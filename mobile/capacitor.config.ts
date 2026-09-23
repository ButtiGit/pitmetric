import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'it.pitmetric.app',
  appName: 'PitMetric',
  webDir: 'dist',
  server: { androidScheme: 'https' },
  plugins: {
    Camera: { presentationStyle: 'popover' },
    CapacitorSQLite: { iosDatabaseLocation: 'Library/CapacitorDatabase' }
  }
};

export default config;
