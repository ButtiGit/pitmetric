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

## PWA beta for iPhone and Android

```bash
npm run test:pwa
npm run build:pwa
```

The PWA build writes a separate static app into Laravel's `public/mobile/`, served at `https://pitmetric.it/mobile/`. Native builds still use `mobile/dist/` with Capacitor's root asset paths. `public/mobile/` is generated, not committed; **build it on the web server or include the command in the deployment process**:

```bash
# From the root of the deployed PitMetric checkout:
npm --prefix mobile install
npm --prefix mobile run build:pwa
```

`npm ci` is not supported until a `mobile/package-lock.json` is committed; the mobile package currently uses `npm install` in CI as well.

The `/app` marketing page displays the PWA install action next to the Android APK. To avoid sending visitors to a missing URL, its PWA button enables **only after** `public/mobile/index.html` and `public/mobile/sw.js` exist on the server. Deployment of the Laravel application alone does not create this generated folder.

- iOS: open `/mobile/` in Safari, then **Share → Add to Home Screen**.
- Android: open `/mobile/` in Chrome and use **Install app / Add to Home Screen**.
- The PWA has a scoped service worker for offline app-shell loading after the first successful visit. Reload it online after deployments to update its files.
- The web beta starts without native SQLite or secure-storage plugins and supports local lap times, timer, sessions, notes, setups and maintenance reminders using browser storage.
- Cloud login/sync, gallery, garage and synced events are **not available in the web beta**. Do not use browser-only records as the sole copy of important trackside data; clearing site data can erase them.

Native Android/iOS application features and storage remain unchanged by this separate build mode.

