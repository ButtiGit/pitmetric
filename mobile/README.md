# PitMetric Mobile

App trackside nativa di PitMetric costruita con Ionic Vue 9 e Capacitor 8.

## Funzioni incluse

- Login e registrazione contro l'API PitMetric.
- Login biometrico nativo con Face ID, Touch ID, impronta o biometria Android, con token custodito in Keychain/Keystore.
- Modalità offline-first: note, problemi, giri, pressioni, cambi componente e foto vengono scritti prima sul dispositivo.
- Sync automatico quando torna la connessione e quando l'app rientra in foreground.
- Idempotenza lato server per evitare duplicati durante retry/interruzioni di rete.
- Account senza `database_access_enabled`: l'app rimane completamente utilizzabile in locale e spiega che il Cloud DB non è attivo; la coda resta pronta per una futura attivazione.
- Galleria tecnica con fotocamera/libreria, titolo e descrizione.
- Brand e icona PitMetric inclusi.

## Avvio web

```bash
cd mobile
npm install
npm run dev
```

L'API predefinita è `https://pitmetric.it`. In sviluppo puoi creare `mobile/.env.local` con:

```env
VITE_API_URL=http://127.0.0.1:8000
```

## Android

```bash
cd mobile
npm install
npm run native:android
npx cap open android
```

`native:android` compila il frontend, crea il progetto Android se manca, aggiunge il permesso biometrico, sincronizza Capacitor e genera le icone/splash dal logo PitMetric.

## iOS

Richiede macOS + Xcode.

```bash
cd mobile
npm install
npm run native:ios
npx cap open ios
```

Lo script inserisce automaticamente le descrizioni privacy per Face ID, fotocamera e libreria foto e genera icone/splash.

## Sicurezza

La password non viene salvata per il login biometrico. PitMetric salva un token mobile revocabile. Quando la biometria è attiva il token viene protetto dall'hardware tramite Keychain/Keystore e viene letto con `getSecureData`, che richiede autenticazione biometrica nativa.
