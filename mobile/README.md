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
- GPS weather on demand (Android native geolocation / PWA browser geolocation) with MET Norway forecasts via the existing PitMetric API, including permission/error handling; the last forecast (including its rounded coordinates and capture time) is saved locally in SQLite on Android for offline consultation; no background location tracking
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
- The PWA starts without native SQLite or secure-storage plugins. Guest records stay separate from authenticated team records. Team snapshots, records and the outbox use IndexedDB, isolated by user and workspace. The bearer token and account metadata are stored in browser storage; passwords are never stored.
- The GPS weather screen requests browser location **only after the user taps Update** and requires internet to refresh forecasts. The most recent forecast (including its rounded coordinates and capture time) is retained in IndexedDB for offline consultation across app restarts. A connection banner and saved-data badge distinguish stored predictions from a new update; no automatic background weather request is made. The mobile weather API is available without logging into the cloud (including PWA beta); coordinates are sent to PitMetric for the MET Norway lookup but not stored against user accounts or sessions.
- Open **Altro → Account** and sign in with the existing PitMetric account while online. Successful login sends that account/team's pending operations and then downloads updated vehicles, drivers, configurations, events, sessions, lap times and setups. These catalogs remain available after closing and reopening offline.
- PWA sync runs **only after a successful online login or an explicit Sincronizza ora**. Reopening the app, restoring a saved login or reconnecting to the network does not send operations. No background sync is enabled.
- To send a new session to the team, select a downloaded configuration before saving. If selecting a weekend, select its driver/entry too. Sessions without these links stay on-device and are clearly distinguished from queued team records.
- In **Timer**, select an existing team session or an offline session just recorded to queue lap times and separate **cold/hot tyre readings**. Offline session UUIDs are resolved server-side before dependent records are created. A saved session's optional best lap is recorded in the timing archive too.
- Notes linked to a weekend and setups linked to a kart are queued; unlinked notes/setups and maintenance reminders stay on-device. Kart setup supports camber/caster in degrees, rear track/axle measurements and pinion/crown reduction. The full web In pista page retains configurable geometry units and side-specific readings.
- States distinguish **Salvato sul dispositivo**, **Da inviare** and **Sincronizzato**. Records and queue entries are saved atomically. Only an explicit server confirmation removes an operation; retrying its stable UUID cannot create a second server record. Connection/auth/permission errors retain pending data and the last downloaded snapshot.
- Logout hides the previous account's team data without deleting its queue; sign back in with the same account to recover it. Guest records are not automatically attached to the next account. Synced history is retained and cannot be deleted from this local quick-record screen.
- Gallery capture/sync is still native-only; use the web gallery on iPhone. Clearing site/browser data can erase unsent records: confirm **0 da inviare** before clearing data or changing devices.

Native Android/iOS application features and storage remain unchanged by this separate build mode.

