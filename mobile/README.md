# PitMetric Mobile

Native mobile client for PitMetric built with Ionic Vue + Capacitor 8.

## What is implemented

- native Android/iOS shell (`it.pitmetric.app`)
- PitMetric dark mobile UI and generated icon/splash sources
- login and registration against `/api/mobile`
- secure token storage
- Face ID / Touch ID / Android biometrics as a local unlock after a valid server login
- SQLite-first local persistence
- offline outbox with automatic retry when connectivity returns
- clear local-only mode when `database_access_enabled` is false
- gallery capture/import with title and description stored on-device and queued for cloud sync
- server-side mobile bearer tokens and gallery metadata API

## Local setup

```bash
cd mobile
npm install
npm run build
npx cap add android
# Run the next command on macOS when preparing iOS:
npx cap add ios
npm run assets
npm run cap:sync
```

Use `VITE_PITMETRIC_API_URL` to override the default `https://pitmetric.it/api/mobile` endpoint.

For Face ID on iOS add `NSFaceIDUsageDescription` to the generated iOS app Info.plist, for example: `PitMetric uses Face ID to unlock your saved mobile session.`

The biometric prompt only unlocks the locally stored session; it never replaces server authentication.
