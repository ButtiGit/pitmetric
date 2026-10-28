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
- GPS weather on demand (Android native geolocation / PWA browser geolocation) with MET Norway forecasts via the existing PitMetric API, including permission/error handling; no background location tracking
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

Android requires precise and approximate location permissions for on-demand weather. The `npm run cap:sync` command injects these permissions into the generated `AndroidManifest.xml`; the GitHub Actions APK workflow applies them too, since `mobile/android` is not committed. The user must approve location access in Android settings. No continuous/background geolocation permission is requested.

For an independently generated native iOS shell, configure `NSLocationWhenInUseUsageDescription` in the generated Info.plist before shipping; the PWA on iPhone requests browser permission through Safari instead.

## PWA beta for iPhone and Android

```bash
npm run test:pwa
npm run build:pwa
```

The PWA build writes a separate static app into Laravel's `public/mobile/`, served at `https://pitmetric.it/mobile/`. Native builds still use `mobile/dist/` with Capacitor's root asset paths. `public/mobile/` is generated, not committed. **The Nixpacks image build installs the mobile dependencies and generates this directory automatically**. For manual/local testing, run:

```bash
# From the root of the deployed PitMetric checkout:
npm --prefix mobile install
npm --prefix mobile run build:pwa
```

`npm ci` is not supported until a `mobile/package-lock.json` is committed; the mobile package currently uses `npm install` in CI as well.

The `/app` marketing page displays the PWA install action next to the Android APK. To avoid sending visitors to a missing URL, its PWA button enables **only after** `public/mobile/index.html` and `public/mobile/sw.js` exist on the server. A new Nixpacks deployment includes both generated files and an explicit Nginx route for `/mobile/`; an existing running container must be redeployed to gain them permanently.

- iOS: open `/mobile/` in Safari, then **Share → Add to Home Screen**.
- Android: open `/mobile/` in Chrome and use **Install app / Add to Home Screen**.
- The PWA has a scoped service worker for offline app-shell loading after the first successful visit. Reload it online after deployments to update its files.
- The web beta starts without native SQLite or secure-storage plugins and supports local lap times, timer, sessions, notes, setups and maintenance reminders using browser storage.
- The GPS weather screen requests browser location **only after the user taps Update** and requires an internet connection for forecasts. The mobile weather API is available without logging into the cloud (including PWA beta); coordinates are sent to PitMetric for the MET Norway lookup but not stored against user accounts or sessions.
- The browser has an IndexedDB storage layer for downloaded team snapshots and a durable, UUID-keyed outbox, isolated by authenticated user ID and workspace ID. This is a **storage foundation only**: the PWA does not yet authenticate or send these operations to the cloud; those connections will be added in later steps of the PN Rent pilot.
- Cloud login/sync, gallery, garage and synced events are **not available in the web beta**. Do not use browser-only records as the sole copy of important trackside data; clearing site data can erase them.

Native Android/iOS application features and storage remain unchanged by this separate build mode.

