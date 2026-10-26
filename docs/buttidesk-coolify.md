# ButtiDesk-Pro — collegamento autonomo e rilascio protetto su Coolify

Il modulo storico ButtiDesk-Pro condivide il login, i workspace e il trasporto email di PitMetric; il suo codice è in `app/Modules/ButtiDeskPro`, le viste in `resources/views/buttidesk`, i dati in `desk_*` e le route in `routes/buttidesk.php`.

## Procedura sicura

1. Esegui il deploy del commit con `BUTTIDESK_ENABLED=false` (valore di default).
2. Dal terminale del **container applicativo** esegui `cd /app && php artisan migrate:status`.
3. Poi esegui `php artisan migrate --force --no-interaction`, controllando l'esito prima di proseguire.
4. Verifica che tutte e sei le tabelle `desk_*` esistano e che la migrazione `2026_10_14_160000_create_buttidesk_tables` sia segnata come eseguita.
5. Solo dopo, imposta `BUTTIDESK_ENABLED=true` nelle variabili di ambiente **runtime** di Coolify e riavvia/redeploya l'app, secondo le modalità della tua installazione.
6. Dentro il nuovo container esegui `cd /app && php artisan optimize:clear`, poi `php artisan route:list --path=buttidesk` e verifica `/buttidesk` da un account con accesso al DB.

## Disattivazione d'emergenza

Imposta `BUTTIDESK_ENABLED=false` in Coolify, applica la modifica riavviando/redeployando l'applicazione e svuota le cache con `php artisan optimize:clear`. Nessuna tabella va cancellata, nessuna migrazione va invertita e il sito principale resta indipendente dal modulo.

Non usare `migrate:fresh`, `db:wipe`, `migrate:rollback` o `key:generate` in produzione. Non eseguire `composer setup` sul server esistente. Non pubblicare credenziali o token di calendario nei log.


## Nuova applicazione separata: collegamento dalla sidebar

La repository `ButtiGit/ButtiDesk-Pro` contiene ora l’app Laravel/React autonoma. Il collegamento usa un bridge privato con codice monouso di 60 secondi, state/PKCE e chiamate server firmate HMAC. Password, `APP_KEY`, cookie e database non vengono condivisi. Il modulo storico e la sua posta non sono spostati né migrati da questo rilascio.

### Accesso

- Gli editor (`manage-updates`) hanno diritto all’accesso; gli altri richiedono il nuovo flag `users.buttidesk_access_enabled`, gestito in **Studio → Utenti → ButtiDesk-Pro**.
- L’accesso database generale non concede ButtiDesk e il flag ButtiDesk non concede accesso al database PitMetric.
- Email verificata e membership attiva sono richieste anche nel bridge. I ruoli del workspace determinano lettura/scrittura nell’app separata.
- Le richieste ButtiDesk ricontrollano utente, flag e membership tramite il bridge. La disattivazione ha effetto alla richiesta successiva; il logout PitMetric invalida i collegamenti creati da quella sessione.
- Il menu PitMetric controlla solo configurazione e attributi già caricati: non effettua richieste a ButtiDesk. React viene caricato soltanto nell’app separata.

### Ordine di attivazione

1. Distribuisci ButtiDesk-Pro come **nuova risorsa Coolify**, con il `Dockerfile` di produzione nella sua repository, porta **8080**, un hostname dedicato con HTTPS e un **database PostgreSQL proprio e inizialmente vuoto**. Non usare `compose.yaml` di sviluppo in produzione e non importare il database demo.
2. Nell’app ButtiDesk configura `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` con il suo URL HTTPS, `DESK_IDENTITY_PROVIDER=pitmetric`, `DESK_DEMO_ENABLED=false`, `PITMETRIC_URL` con l’origine HTTPS di PitMetric, `BUTTIDESK_BRIDGE_SECRET` e le sue variabili `DB_*`. Crea **solo per la nuova app** una propria `APP_KEY`; non copiarla né rigenerarla in PitMetric. Cookie host-only, nome sessione distinto.
3. Genera una chiave dedicata casuale di almeno 32 byte, per esempio con `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. Inserisci **lo stesso valore** come `BUTTIDESK_BRIDGE_SECRET` nei due servizi, esclusivamente nelle variabili runtime Coolify.
4. Esegui le migrazioni additive della nuova app con `php artisan migrate --force --no-interaction`. Nessun `db:seed` o `desk:prepare` in produzione.
5. Distribuisci il commit PitMetric con `BUTTIDESK_BRIDGE_ENABLED=false`. Esegui `php artisan migrate --force --no-interaction` nel suo container per aggiungere il flag utenti. La colonna parte da `false`; gli editor restano riconosciuti dal gate esistente. Sidebar e gestione utenti restano utilizzabili anche prima di questa migrazione.
6. In PitMetric imposta `BUTTIDESK_PRO_URL` con l’origine HTTPS della nuova app e `BUTTIDESK_BRIDGE_ENABLED=true`. `BUTTIDESK_ENABLED` è il flag del **vecchio modulo**: può restare `false`; il bridge nuovo non ne dipende.
7. Aggiorna la configurazione runtime di entrambe le risorse, poi usa `php artisan config:clear` e il normale riavvio/redeploy Coolify. Verifica il nuovo collegamento nella sidebar da editor; attiva un secondo utente da Studio → Utenti e verifica anche disattivazione e ruolo osservatore.

URL delle app: origini dedicate (schema, host e porta opzionale), senza prefissi di percorso, query o credenziali. Il ritorno dalla sidebar ButtiDesk apre la dashboard PitMetric. Per cambiare team, selezionalo in PitMetric e riapri ButtiDesk: il contesto viene autorizzato di nuovo.

### Rotte del bridge

- Browser: `GET /apps/buttidesk`, `GET /apps/buttidesk/authorize`, `GET /apps/buttidesk/return` in PitMetric.
- Server: `POST /api/buttidesk/v1/token`, `/context`, `/revoke`, firma HMAC con timestamp entro 30 secondi e nonce monouso.
- ButtiDesk: `/auth/pitmetric/start`, `/callback`, `/problem` e `POST /auth/pitmetric/logout`.

Tutte le repliche di PitMetric devono usare la stessa cache Laravel con supporto ai lock. La cache conserva solo grant effimeri; svuotarla interrompe le sessioni collegate senza modificare i dati. L’URL della nuova app e la chiave non sono preimpostati in repository: senza configurazione il collegamento rimane nascosto, mentre PitMetric continua a funzionare.

### Disattivazione del collegamento nuovo

Imposta `BUTTIDESK_BRIDGE_ENABLED=false` in PitMetric, aggiorna la configurazione e riavvia la risorsa. Il link scompare e le nuove richieste del bridge vengono rifiutate. Nessun rollback di schema o cancellazione di dati è necessario.
