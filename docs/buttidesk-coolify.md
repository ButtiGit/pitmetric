# ButtiDesk-Pro — rilascio protetto su Coolify

ButtiDesk-Pro condivide il login, i workspace e il trasporto email di PitMetric, ma il suo codice è in `app/Modules/ButtiDeskPro`, le viste in `resources/views/buttidesk`, i dati in `desk_*` e le route in `routes/buttidesk.php`.

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
